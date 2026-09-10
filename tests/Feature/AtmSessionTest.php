<?php

use App\Models\Account;
use App\Models\Card;
use App\Models\Customer;
use Database\Seeders\DemoCustomerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DemoCustomerSeeder::class);
    RateLimiter::clear('atm-login:127.0.0.1');
    $this->card = Card::where('demo_reference', 'DEMO-002')->firstOrFail();
});

it('offers only public card identifiers before authentication', function () {
    $this->get('/atm/cards')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Atm/SignIn')->has('cards', 2)
        ->has('cards.0', fn (Assert $card) => $card->has('id')->where('demo_reference', 'DEMO-001'))
        ->missing('customerName'));
    expect($this->card->toArray())->not->toHaveKey('pin_hash')
        ->and(Hash::check('0042', $this->card->pin_hash))->toBeTrue()
        ->and($this->card->pin_hash)->not->toBe('0042');
});

it('authenticates a PIN with a leading zero and rotates the session', function () {
    $this->withSession(['previous' => true]);
    $previousId = session()->getId();
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '0042'])
        ->assertRedirect(route('atm.session'))->assertSessionHas('atm_card_id', $this->card->id);
    expect(session()->getId())->not->toBe($previousId);
    $this->get('/atm/session')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(fn (Assert $page) => $page->component('Atm/Session')
            ->where('customerName', 'Sam Beispiel')->where('accountReference', 'DEMO-002')
            ->missing('pin_hash')->missing('balance_minor'));
});

it('persists failed attempts without flashing the PIN', function () {
    $this->from('/atm/cards')->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '9999'])
        ->assertRedirect('/atm/cards')->assertSessionHasErrors('pin')
        ->assertSessionMissing('_old_input.pin')->assertSessionMissing('atm_card_id');
    expect($this->card->fresh()->failed_attempts)->toBe(1);
});

it('blocks after five failures even with the correct PIN and recovers after the lock expires', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '9999'])->assertSessionHasErrors('pin');
    }
    expect($this->card->fresh()->locked_until)->not->toBeNull();
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '0042'])
        ->assertSessionHasErrors('pin')->assertSessionMissing('atm_card_id');
    $this->travel(config('atm.lock_seconds'))->seconds();
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '0042'])->assertRedirect(route('atm.session'));
    expect($this->card->fresh()->failed_attempts)->toBe(0)->and($this->card->fresh()->locked_until)->toBeNull();
});

it('rejects unavailable cards or accounts', function (string $condition) {
    match ($condition) {
        'card' => $this->card->update(['status' => 'blocked']),
        'account' => $this->card->account->update(['status' => 'blocked']),
        'expired' => $this->card->update(['expires_at' => now()->subSecond()]),
    };
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '0042'])
        ->assertSessionHasErrors('pin')->assertSessionMissing('atm_card_id');
})->with(['card', 'account', 'expired']);

it('rejects malformed PINs without storing them in old input', function (mixed $pin) {
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => $pin])
        ->assertSessionHasErrors('pin')->assertSessionMissing('_old_input.pin')
        ->assertSessionMissing('atm_card_id');
})->with(['123', '12345', 'abcd', 42]);

it('rejects an unknown card without authenticating', function () {
    $this->post('/atm/session', ['card_id' => 999999, 'pin' => '0042'])
        ->assertSessionHasErrors('pin')->assertSessionMissing('atm_card_id');
});

it('rate limits attempts across different cards from one IP', function () {
    config(['atm.requests_per_minute' => 2]);
    for ($i = 0; $i < 2; $i++) {
        $this->post('/atm/session', ['card_id' => 999999, 'pin' => '0000']);
    }
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '0042'])
        ->assertSessionHasErrors(['pin' => 'Zu viele Versuche. Bitte warte eine Minute.'])
        ->assertSessionMissing('atm_card_id');
});

it('requires authentication on the session page', function () {
    $this->get('/atm/session')->assertRedirect(route('atm.cards'));
});

it('expires at the inactivity boundary and clears the old session', function () {
    $this->withSession(['atm_card_id' => $this->card->id, 'atm_last_activity' => now()->timestamp - config('atm.idle_seconds'), 'private_marker' => 'remove']);
    $this->get('/atm/session')->assertRedirect(route('atm.cards'))
        ->assertSessionMissing('atm_card_id')->assertSessionMissing('private_marker');
});

it('rechecks card validity on an existing session', function () {
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '0042']);
    $this->card->update(['status' => 'blocked']);
    $this->get('/atm/session')->assertRedirect(route('atm.cards'))->assertSessionMissing('atm_card_id');
});

it('ends the session and prevents access to the protected page', function () {
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '0042']);
    $this->delete('/atm/session')->assertRedirect(route('atm.cards'))->assertSessionMissing('atm_card_id');
    $this->get('/atm/cards')->assertViewHas('page', fn ($page) => $page['clearHistory'] === true && $page['encryptHistory'] === true);
    $this->get('/atm/session')->assertRedirect(route('atm.cards'));
});

it('does not retain an authenticated session after a failed card switch', function () {
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '0042']);
    $this->post('/atm/session', ['card_id' => $this->card->id, 'pin' => '9999'])->assertSessionMissing('atm_card_id');
});

it('can seed twice without resetting security state or balances', function () {
    $this->card->failed_attempts = 3;
    $this->card->save();
    $this->card->account->update(['balance_minor' => 123]);
    $this->seed(DemoCustomerSeeder::class);
    expect(Card::count())->toBe(2)->and(Account::count())->toBe(2)->and(Customer::count())->toBe(2)
        ->and($this->card->fresh()->failed_attempts)->toBe(3)
        ->and($this->card->account->fresh()->balance_minor)->toBe(123);
});
