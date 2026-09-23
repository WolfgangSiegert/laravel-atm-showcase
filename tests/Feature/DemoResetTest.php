<?php

use App\Actions\DepositMoney;
use App\Actions\ResetDemoData;
use App\Actions\WithdrawMoney;
use App\Models\Account;
use App\Models\Atm;
use App\Models\Card;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DemoOperatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed();
    $this->card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
});

it('requires both an opt-in and explicit confirmation for a forced reset', function () {
    expect(fn () => app(ResetDemoData::class)->execute())->toThrow(LogicException::class);
    $this->artisan('atm:demo-reset', ['--force' => true])->assertExitCode(1);
    config(['demo.enabled' => true]);
    $this->artisan('atm:demo-reset')->assertExitCode(1);
    expect(fn () => app(ResetDemoData::class)->execute())->not->toThrow(LogicException::class);
});

it('refuses public provisioning on production SQLite and never seeds a known operator', function () {
    config(['demo.enabled' => true]);
    app()->instance('env', 'production');
    $this->artisan('atm:demo-provision')->assertExitCode(1);
    expect(fn () => (new DemoOperatorSeeder)->run())->toThrow(RuntimeException::class);
    $this->artisan('atm:deployment-check')->assertExitCode(1);
});

it('bounds audit retention only in the opted-in demo maintenance path', function () {
    config(['demo.enabled' => true]);
    DB::table('audit_events')->insert([
        ['event_type' => 'operator.login', 'outcome' => 'success', 'created_at' => now()->subDays(8)],
        ['event_type' => 'operator.login', 'outcome' => 'success', 'created_at' => now()->subDay()],
    ]);
    app(ResetDemoData::class)->execute();
    expect(DB::table('audit_events')->count())->toBe(1);
});

it('resets only demo accounts and invalidates their existing sessions', function () {
    config(['demo.enabled' => true]);
    $foreign = Account::create(['customer_id' => $this->card->account->customer_id, 'reference' => 'OTHER', 'balance_minor' => 777, 'currency' => 'EUR']);
    Transaction::create(['account_id' => $foreign->id, 'type' => 'opening', 'amount_minor' => 777, 'balance_after_minor' => 777, 'currency' => 'EUR', 'idempotency_key' => (string) Str::uuid()]);
    app(DepositMoney::class)->execute($this->card, 5000, 'Demo', (string) Str::uuid());
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '1234'])->assertRedirect(route('atm.session'));
    $this->card->forceFill(['failed_attempts' => 5, 'locked_until' => now()->addHour()])->save();
    Atm::first()->update(['status' => 'maintenance']);
    Atm::first()->cashInventories()->update(['quantity' => 0]);
    $this->artisan('atm:demo-reset', ['--force' => true])->assertExitCode(0);
    expect($this->card->account->fresh()->balance_minor)->toBe(0)
        ->and($this->card->fresh()->failed_attempts)->toBe(0)
        ->and($this->card->fresh()->locked_until)->toBeNull()
        ->and($this->card->fresh()->session_version)->toBe(1)
        ->and($foreign->fresh()->balance_minor)->toBe(777)
        ->and(Transaction::where('account_id', $foreign->id)->count())->toBe(1)
        ->and(Transaction::where('account_id', $this->card->account_id)->count())->toBe(0)
        ->and(User::where('is_operator', true)->count())->toBe(1)
        ->and(Atm::first()->status)->toBe('active')
        ->and(Atm::first()->cashInventories()->where('denomination_minor', 1000)->value('quantity'))->toBe(40);
    $this->get('/atm/session')->assertRedirect(route('atm.cards'))->assertSessionMissing('atm_card_id');
});

it('rejects money movements loaded before a reset even after new deposits', function () {
    config(['demo.enabled' => true]);
    $oldCard = $this->card;
    app(ResetDemoData::class)->execute();
    app(DepositMoney::class)->execute($oldCard->fresh(), 5000, null, (string) Str::uuid());
    expect(fn () => app(DepositMoney::class)->execute($oldCard, 100, null, (string) Str::uuid()))->toThrow(ValidationException::class)
        ->and(fn () => app(WithdrawMoney::class)->execute($oldCard, 1000, null, (string) Str::uuid()))->toThrow(ValidationException::class)
        ->and($oldCard->account->fresh()->balance_minor)->toBe(5000)
        ->and(Transaction::count())->toBe(1);
});

it('performs an overdue reset on public traffic once and skips recent resets', function () {
    config(['demo.enabled' => true]);
    DB::table('demo_reset_state')->insert(['id' => 1, 'last_reset_at' => now()->subDay()]);
    $this->get('/atm/cards')->assertOk()->assertInertia(fn (Assert $page) => $page->where('demoAccess.DEMO-001', '1234'));
    $this->get('/atm/cards')->assertOk();
    expect($this->card->fresh()->session_version)->toBe(1);
    $this->travel(86400)->seconds();
    $this->get('/atm/cards')->assertOk();
    expect($this->card->fresh()->session_version)->toBe(2);
});

it('does not reset or publish PINs when public demo mode is disabled', function () {
    $this->card->account->update(['balance_minor' => 500]);
    $this->get('/atm/cards')->assertOk()->assertInertia(fn (Assert $page) => $page->where('demoAccess', null));
    expect($this->card->account->fresh()->balance_minor)->toBe(500);
});

it('limits public booking requests without creating extra transactions on retries', function () {
    config(['demo.enabled' => true, 'demo.booking_requests_per_minute' => 2]);
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '1234'])->assertRedirect(route('atm.session'));
    $payload = ['amount' => '10', 'idempotency_key' => (string) Str::uuid()];
    $this->post('/atm/deposits', $payload)->assertRedirect();
    $this->post('/atm/deposits', $payload)->assertRedirect();
    $this->post('/atm/withdrawals', ['withdrawal_amount' => '10', 'idempotency_key' => (string) Str::uuid()])->assertStatus(429);
    expect(Transaction::count())->toBe(1)->and($this->card->account->fresh()->balance_minor)->toBe(1000);
});

it('provisions only public identities without creating additional operators', function () {
    config(['demo.enabled' => true]);
    $this->artisan('atm:demo-provision')->assertExitCode(0);
    $this->artisan('atm:demo-provision')->assertExitCode(0);
    expect(Card::count())->toBe(2)->and(User::count())->toBe(1);
});

it('provisions the public admin guest only after explicit opt in', function () {
    config(['demo.enabled' => true, 'demo.admin_guest_enabled' => true]);
    $this->artisan('atm:demo-provision')->assertExitCode(0);
    $this->artisan('atm:demo-provision')->assertExitCode(0);

    $guest = User::where('email', config('demo.admin_guest_email'))->firstOrFail();
    expect($guest->is_operator)->toBeTrue()
        ->and($guest->operator_role)->toBe(User::OPERATOR_ROLE_VIEWER)
        ->and(User::where('email', config('demo.admin_guest_email'))->count())->toBe(1);

    config(['demo.admin_guest_enabled' => false]);
    $this->artisan('atm:demo-provision')->assertExitCode(0);
    expect($guest->fresh()->is_operator)->toBeFalse();
});
