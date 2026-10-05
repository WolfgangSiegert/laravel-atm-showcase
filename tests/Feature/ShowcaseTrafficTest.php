<?php

use App\Models\PortfolioTrafficEvent;
use App\Models\ShowcaseTrafficDaily;
use App\Models\User;
use App\Support\ShowcaseTraffic;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

const JOINSPLIT_ORIGIN = 'https://joinsplit.tiny-bits.org';
const JOINSPLIT_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1';

beforeEach(function () {
    $this->withoutVite();
    config([
        'showcase_traffic.enabled' => true,
        'showcase_traffic.rate_limit_per_minute' => 120,
        'showcase_traffic.retention_days' => 90,
    ]);
    RateLimiter::clear(app(ShowcaseTraffic::class)->rateLimitKey());
});

function joinSplitRequest(array $overrides = [], ?string $origin = JOINSPLIT_ORIGIN, string $userAgent = JOINSPLIT_USER_AGENT, string $ip = '203.0.113.30')
{
    $request = test()
        ->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeader('User-Agent', $userAgent)
        ->withHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');

    if ($origin !== null) {
        $request->withHeader('Origin', $origin);
    }

    return $request->post('/api/showcase-traffic', [
        'version' => '1',
        'site' => 'joinsplit',
        'path' => '/app',
        ...$overrides,
    ]);
}

function showcaseUser(bool $operator, string $role = User::OPERATOR_ROLE_SUPERADMIN): User
{
    return User::create([
        'name' => 'Showcase Test',
        'email' => Str::uuid().'@example.test',
        'password' => 'test-password',
        'is_operator' => $operator,
        'operator_role' => $role,
    ]);
}

it('accepts the exact JoinSplit contract and stores only an aggregated Berlin daily counter', function () {
    CarbonImmutable::setTestNow('2026-10-01 22:30:00 UTC');

    joinSplitRequest()->assertNoContent()->assertHeaderMissing('Set-Cookie');
    joinSplitRequest(ip: '198.51.100.44')->assertNoContent();

    $counter = ShowcaseTrafficDaily::firstOrFail();
    expect($counter->visit_date->toDateString())->toBe('2026-10-02')
        ->and($counter->site)->toBe('joinsplit')
        ->and($counter->path)->toBe('/app')
        ->and($counter->view_count)->toBe(2)
        ->and(Schema::getColumnListing('showcase_traffic_daily'))->toBe([
            'visit_date', 'site', 'path', 'view_count',
        ]);

    $serialized = $counter->toJson();
    expect($serialized)->not->toContain('203.0.113.30')
        ->not->toContain('198.51.100.44')
        ->not->toContain('Mozilla')
        ->not->toContain('visitor')
        ->not->toContain('hash');
});

it('rejects missing and foreign origins', function (?string $origin) {
    joinSplitRequest(origin: $origin)->assertForbidden();
    expect(ShowcaseTrafficDaily::count())->toBe(0);
})->with([null, 'https://attacker.invalid', 'https://joinsplit.tiny-bits.org.evil.invalid']);

it('rejects invalid JoinSplit payload fields', function (array $payload) {
    joinSplitRequest($payload)->assertUnprocessable();
    expect(ShowcaseTrafficDaily::count())->toBe(0);
})->with([
    'version' => [['version' => '2']],
    'site' => [['site' => 'portfolio']],
    'path' => [['path' => '/']],
    'concrete route' => [['path' => '/app/groups/123']],
    'extra field' => [['account' => '123']],
]);

it('returns not found when central collection is disabled', function () {
    config(['showcase_traffic.enabled' => false]);
    joinSplitRequest()->assertNotFound();
    expect(ShowcaseTrafficDaily::count())->toBe(0);
});

it('returns no content for obvious bots without incrementing the counter', function () {
    joinSplitRequest(userAgent: 'Googlebot/2.1 (+http://www.google.com/bot.html)')->assertNoContent();
    expect(ShowcaseTrafficDaily::count())->toBe(0);
});

it('uses one short lived shared rate limit instead of a visitor key', function () {
    config(['showcase_traffic.rate_limit_per_minute' => 2]);

    joinSplitRequest(ip: '203.0.113.31')->assertNoContent();
    joinSplitRequest(ip: '203.0.113.32')->assertNoContent();
    joinSplitRequest(ip: '203.0.113.33')->assertTooManyRequests();

    expect(ShowcaseTrafficDaily::sum('view_count'))->toBe(2);
});

it('opportunistically prunes expired daily counters and keeps the current retention boundary', function () {
    CarbonImmutable::setTestNow('2026-10-01 12:00:00 Europe/Berlin');
    ShowcaseTrafficDaily::insert([
        ['visit_date' => '2026-07-03', 'site' => 'joinsplit', 'path' => '/app', 'view_count' => 3],
        ['visit_date' => '2026-07-04', 'site' => 'joinsplit', 'path' => '/app', 'view_count' => 4],
    ]);

    joinSplitRequest()->assertNoContent();

    expect(ShowcaseTrafficDaily::whereDate('visit_date', '2026-07-03')->exists())->toBeFalse()
        ->and(ShowcaseTrafficDaily::whereDate('visit_date', '2026-07-04')->value('view_count'))->toBe(4)
        ->and(ShowcaseTrafficDaily::whereDate('visit_date', '2026-10-01')->value('view_count'))->toBe(1);
});

it('reports filtered JoinSplit daily views without unique or returning metrics', function () {
    CarbonImmutable::setTestNow('2026-10-05 12:00:00 Europe/Berlin');
    ShowcaseTrafficDaily::insert([
        ['visit_date' => '2026-10-01', 'site' => 'joinsplit', 'path' => '/app', 'view_count' => 4],
        ['visit_date' => '2026-10-03', 'site' => 'joinsplit', 'path' => '/app', 'view_count' => 7],
        ['visit_date' => '2026-10-04', 'site' => 'joinsplit', 'path' => '/app', 'view_count' => 20],
    ]);
    $superadmin = showcaseUser(true);

    $this->actingAs($superadmin)->get('/admin/portfolio-traffic?project=joinsplit&from=2026-10-01&to=2026-10-03')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Operator/PortfolioTraffic')
            ->where('report.project', 'joinsplit')
            ->where('report.totals.views', 11)
            ->missing('report.totals.unique')
            ->missing('report.totals.returning')
            ->has('report.daily', 3)
            ->where('report.daily.1.views', 0)
            ->missing('report.daily.0.unique')
            ->where('report.paths.0.source', 'JoinSplit')
            ->where('report.paths.0.path', '/app'));
});

it('adds only views in the combined report', function () {
    CarbonImmutable::setTestNow('2026-10-05 12:00:00 Europe/Berlin');
    ShowcaseTrafficDaily::insert(['visit_date' => '2026-10-05', 'site' => 'joinsplit', 'path' => '/app', 'view_count' => 5]);
    PortfolioTrafficEvent::create(['occurred_at' => now(), 'site' => 'portfolio', 'path' => '/', 'visitor_hash' => str_repeat('a', 64)]);
    $superadmin = showcaseUser(true);

    $this->actingAs($superadmin)->get('/admin/portfolio-traffic?project=all&from=2026-10-05&to=2026-10-05')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.totals.views', 6)
            ->missing('report.totals.unique')
            ->missing('report.daily.0.unique'));
});

it('keeps the report private to superadmins for every project selection', function () {
    $this->get('/admin/portfolio-traffic?project=joinsplit')->assertRedirect(route('operator.login'));
    $this->actingAs(showcaseUser(false))->get('/admin/portfolio-traffic?project=joinsplit')->assertForbidden();
    $this->actingAs(showcaseUser(true, User::OPERATOR_ROLE_VIEWER))->get('/admin/portfolio-traffic?project=joinsplit')->assertForbidden();
    $this->actingAs(showcaseUser(true))->get('/admin/portfolio-traffic?project=joinsplit')->assertOk();
});
