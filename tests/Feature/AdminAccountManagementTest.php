<?php

use App\Models\Account;
use App\Models\AuditEvent;
use App\Models\Card;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    $this->operator = User::where('is_operator', true)->firstOrFail();
});

it('creates a customer account and first card without exposing the PIN', function () {
    $this->actingAs($this->operator)->post('/admin/accounts', [
        'customer_name' => 'Kim Muster',
        'account_reference' => 'konto-100',
        'card_reference' => 'karte-100',
        'pin' => '9876',
    ])->assertRedirect(route('operator.dashboard'))->assertSessionHas('notice');

    $customer = Customer::where('display_name', 'Kim Muster')->firstOrFail();
    $account = Account::where('reference', 'KONTO-100')->firstOrFail();
    $card = Card::where('demo_reference', 'KARTE-100')->firstOrFail();
    expect($account->customer_id)->toBe($customer->id)
        ->and($account->balance_minor)->toBe(0)
        ->and($account->status)->toBe('active')
        ->and($card->account_id)->toBe($account->id)
        ->and(Hash::check('9876', $card->pin_hash))->toBeTrue()
        ->and(AuditEvent::where('event_type', 'account.created')->firstOrFail()->account_id)->toBe($account->id)
        ->and(AuditEvent::all()->toJson())->not->toContain('9876');
});

it('creates accounts for existing customers and rejects duplicate references', function () {
    $customer = Customer::firstOrFail();
    $this->actingAs($this->operator)->post('/admin/accounts', [
        'customer_id' => $customer->id,
        'account_reference' => 'SECOND-001',
        'card_reference' => 'CARD-SECOND-001',
        'pin' => '1111',
    ])->assertSessionHasNoErrors();

    $this->post('/admin/accounts', [
        'customer_id' => $customer->id,
        'account_reference' => 'SECOND-001',
        'card_reference' => 'CARD-SECOND-001',
        'pin' => '1111',
    ])->assertSessionHasErrors(['account_reference', 'card_reference']);

    expect($customer->accounts()->where('reference', 'SECOND-001')->count())->toBe(1);
});

it('blocks and reactivates an account while invalidating every card session', function () {
    $account = Account::with('cards')->firstOrFail();
    $versions = $account->cards->pluck('session_version', 'id');

    $this->actingAs($this->operator)->patch("/admin/accounts/{$account->id}/status", ['status' => 'blocked'])
        ->assertSessionHasNoErrors();

    expect($account->fresh()->status)->toBe('blocked');
    foreach ($account->cards()->get() as $card) {
        expect($card->session_version)->toBe($versions[$card->id] + 1);
    }
    $event = AuditEvent::where('event_type', 'account.status_changed')->firstOrFail();
    expect($event->account_id)->toBe($account->id)
        ->and($event->context)->toBe(['before' => 'active', 'after' => 'blocked']);

    $this->patch("/admin/accounts/{$account->id}/status", ['status' => 'active'])->assertSessionHasNoErrors();
    expect($account->fresh()->status)->toBe('active');
});

it('adds blocks and unlocks a card with complete audit coverage', function () {
    $account = Account::firstOrFail();
    $this->actingAs($this->operator)->post("/admin/accounts/{$account->id}/cards", [
        'card_reference' => 'EXTRA-100',
        'pin' => '2468',
        'expires_at' => now()->addYear()->toDateString(),
    ])->assertSessionHasNoErrors();

    $card = Card::where('demo_reference', 'EXTRA-100')->firstOrFail();
    expect(Hash::check('2468', $card->pin_hash))->toBeTrue()
        ->and($card->expires_at?->toDateString())->toBe(now()->addYear()->toDateString());

    $this->patch("/admin/cards/{$card->id}/status", ['status' => 'blocked'])->assertSessionHasNoErrors();
    expect($card->fresh()->status)->toBe('blocked')
        ->and($card->fresh()->session_version)->toBe(1);

    $card->failed_attempts = 4;
    $card->locked_until = now()->addMinutes(10);
    $card->save();
    $this->post("/admin/cards/{$card->id}/reset-lock")->assertSessionHasNoErrors();
    expect($card->fresh()->failed_attempts)->toBe(0)
        ->and($card->fresh()->locked_until)->toBeNull()
        ->and($card->fresh()->session_version)->toBe(2)
        ->and(AuditEvent::where('event_type', 'card.created')->count())->toBe(1)
        ->and(AuditEvent::where('event_type', 'card.status_changed')->count())->toBe(1)
        ->and(AuditEvent::where('event_type', 'card.lock_reset')->count())->toBe(1)
        ->and(AuditEvent::all()->toJson())->not->toContain('2468');
});

it('hides blocked accounts and cards from the public card picker', function () {
    $account = Account::firstOrFail();
    $blockedCard = $account->cards()->firstOrFail();
    $blockedCard->update(['status' => 'blocked']);

    $activeCard = Card::where('id', '!=', $blockedCard->id)->firstOrFail();
    $activeCard->account->update(['status' => 'blocked']);

    $this->get('/atm/cards')->assertOk()->assertInertia(fn ($page) => $page->has('cards', 0));
});

it('rejects management actions from non-operators', function () {
    $user = User::create(['name' => 'Normal', 'email' => 'normal@example.test', 'password' => 'password']);
    $account = Account::firstOrFail();

    $this->actingAs($user)->post('/admin/accounts', [])->assertForbidden();
    $this->patch("/admin/accounts/{$account->id}/status", ['status' => 'blocked'])->assertForbidden();
});
