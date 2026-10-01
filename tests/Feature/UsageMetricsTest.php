<?php

use App\Models\Card;
use App\Models\Transaction;
use App\Models\User;
use App\Support\UsageMetrics;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    config(['usage_metrics.enabled' => true]);
});

it('counts successful public views in daily aggregates without request fingerprints', function () {
    $this->get('/atm')->assertOk();
    $this->get('/atm/cards')->assertOk();

    expect(DB::table('usage_metrics')->where('metric_name', UsageMetrics::PUBLIC_PAGE_VIEW)->value('count'))->toBe(2)
        ->and(DB::table('usage_metrics')->where('metric_name', UsageMetrics::PUBLIC_LANDING_VIEW)->value('count'))->toBe(1)
        ->and(Schema::hasColumn('usage_metrics', 'ip_address'))->toBeFalse()
        ->and(Schema::hasColumn('usage_metrics', 'user_agent'))->toBeFalse()
        ->and(Schema::hasColumn('usage_metrics', 'session_id'))->toBeFalse()
        ->and(Schema::hasColumn('usage_metrics', 'url'))->toBeFalse();
});

it('uses atomic increments and keeps separate calendar days', function () {
    $metrics = app(UsageMetrics::class);
    foreach (range(1, 25) as $_) {
        $metrics->increment(UsageMetrics::PUBLIC_PAGE_VIEW);
    }
    $this->travel(1)->day();
    $metrics->increment(UsageMetrics::PUBLIC_PAGE_VIEW);

    expect(DB::table('usage_metrics')->where('metric_name', UsageMetrics::PUBLIC_PAGE_VIEW)->count())->toBe(2)
        ->and((int) DB::table('usage_metrics')->where('metric_name', UsageMetrics::PUBLIC_PAGE_VIEW)->sum('count'))->toBe(26);
});

it('prunes daily aggregates outside the configured retention period', function () {
    config(['usage_metrics.retention_days' => 400]);
    DB::table('usage_metrics')->insert([
        ['metric_date' => now()->subDays(400)->toDateString(), 'metric_name' => UsageMetrics::PUBLIC_PAGE_VIEW, 'count' => 4],
        ['metric_date' => now()->subDays(399)->toDateString(), 'metric_name' => UsageMetrics::PUBLIC_PAGE_VIEW, 'count' => 3],
    ]);

    app(UsageMetrics::class)->increment(UsageMetrics::PUBLIC_LANDING_VIEW);

    expect(DB::table('usage_metrics')->where('metric_date', now()->subDays(400)->toDateString())->exists())->toBeFalse()
        ->and(DB::table('usage_metrics')->where('metric_date', now()->subDays(399)->toDateString())->exists())->toBeTrue();
});

it('excludes prefetches admin pages health checks and failed responses', function () {
    $this->get('/atm', ['Purpose' => 'prefetch'])->assertOk();
    $this->get('/atm', ['Sec-Purpose' => 'prefetch;prerender'])->assertOk();
    $this->get('/atm/cards', ['X-Inertia-Prefetch' => 'true'])->assertOk();
    $this->get('/admin/login')->assertOk();
    $this->get('/up')->assertOk();
    $this->get('/atm/does-not-exist')->assertNotFound();

    expect(DB::table('usage_metrics')->sum('count'))->toBe(0);
});

it('counts only successful atm sign-ins', function () {
    $this->seed(DatabaseSeeder::class);
    RateLimiter::clear('atm-login:127.0.0.1');
    $card = Card::where('demo_reference', 'DEMO-002')->firstOrFail();

    $this->post('/atm/session', ['card_id' => $card->id, 'pin' => '9999'])->assertSessionHasErrors('pin');
    expect(DB::table('usage_metrics')->where('metric_name', UsageMetrics::SUCCESSFUL_ATM_LOGIN)->sum('count'))->toBe(0);

    $this->post('/atm/session', ['card_id' => $card->id, 'pin' => '0042'])->assertRedirect(route('atm.session'));
    expect(DB::table('usage_metrics')->where('metric_name', UsageMetrics::SUCCESSFUL_ATM_LOGIN)->value('count'))->toBe(1);
});

it('does not double count idempotent deposits or withdrawals', function () {
    $this->seed(DatabaseSeeder::class);
    $card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $card->account->update(['balance_minor' => 50000]);
    $this->withSession(['atm_card_id' => $card->id, 'atm_last_activity' => now()->timestamp]);
    $deposit = ['amount' => '25', 'idempotency_key' => (string) Str::uuid()];
    $withdrawal = ['withdrawal_amount' => '10', 'idempotency_key' => (string) Str::uuid()];

    $this->post('/atm/deposits', $deposit)->assertRedirect();
    $this->post('/atm/deposits', $deposit)->assertRedirect();
    $this->post('/atm/withdrawals', $withdrawal)->assertRedirect();
    $this->post('/atm/withdrawals', $withdrawal)->assertRedirect();

    expect(Transaction::where('type', 'deposit')->count())->toBe(1)
        ->and(Transaction::where('type', 'withdrawal')->count())->toBe(1)
        ->and(DB::table('usage_metrics')->where('metric_name', UsageMetrics::SUCCESSFUL_DEPOSIT)->value('count'))->toBe(1)
        ->and(DB::table('usage_metrics')->where('metric_name', UsageMetrics::SUCCESSFUL_WITHDRAWAL)->value('count'))->toBe(1);
});

it('keeps requests and bookings successful when metrics storage fails', function () {
    $this->seed(DatabaseSeeder::class);
    $card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $this->withSession(['atm_card_id' => $card->id, 'atm_last_activity' => now()->timestamp]);
    Schema::drop('usage_metrics');

    $this->get('/atm')->assertOk();
    $this->post('/atm/deposits', ['amount' => '10', 'idempotency_key' => (string) Str::uuid()])->assertRedirect();

    expect(Transaction::where('type', 'deposit')->count())->toBe(1)
        ->and($card->account->fresh()->balance_minor)->toBe(1000);
});

it('shows aggregate usage only in the private superadmin dashboard', function () {
    $this->seed(DatabaseSeeder::class);
    DB::table('usage_metrics')->insert([
        ['metric_date' => now()->toDateString(), 'metric_name' => UsageMetrics::PUBLIC_PAGE_VIEW, 'count' => 10],
        ['metric_date' => now()->toDateString(), 'metric_name' => UsageMetrics::SUCCESSFUL_DEPOSIT, 'count' => 3],
    ]);
    $operator = User::where('email', config('atm.demo_operator_email'))->firstOrFail();

    $this->actingAs($operator)->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('usageMetrics.enabled', true)
        ->where('usageMetrics.totals.public_page_view', 10)
        ->where('usageMetrics.totals.successful_deposit', 3)
        ->has('usageMetrics.daily', 1));
});

it('can disable usage metrics without exposing a consent endpoint', function () {
    config(['usage_metrics.enabled' => false]);

    $this->get('/atm')->assertOk();
    $this->post('/analytics/consent', ['choice' => 'accepted'])->assertNotFound();

    expect(DB::table('usage_metrics')->count())->toBe(0);
});
