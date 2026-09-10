<?php

use App\Models\Card;
use App\Models\Transaction;
use Database\Seeders\DemoCustomerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DemoCustomerSeeder::class);
    $this->card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $this->withSession(['atm_card_id' => $this->card->id, 'atm_last_activity' => now()->timestamp]);
    $this->payload = ['amount' => '25,50', 'idempotency_key' => (string) Str::uuid()];
});

it('books exact cents and shows the resulting balance and history', function () {
    $response = $this->post('/atm/deposits', $this->payload);
    expect($this->card->account->fresh()->balance_minor)->toBe(2550);
    $transaction = Transaction::firstOrFail();
    $response->assertRedirect(route('atm.receipt', $transaction->receipt_reference));
    expect($transaction->receipt_reference)->toStartWith('ATM-')->toHaveLength(30);
    $this->assertDatabaseHas('transactions', ['account_id' => $this->card->account_id, 'card_id' => $this->card->id, 'amount_minor' => 2550, 'balance_after_minor' => 2550, 'type' => 'deposit']);
    $this->get('/atm/session')->assertInertia(fn (Assert $page) => $page
        ->where('balanceMinor', 2550)->has('transactions.data', 1)
        ->where('transactions.data.0.amount_minor', 2550)
        ->where('transactions.data.0.receipt_reference', $transaction->receipt_reference)
        ->missing('transactions.data.0.idempotency_key'));
});

it('accepts supported decimal formats without floating point arithmetic', function (string $amount, int $expected) {
    $this->post('/atm/deposits', [...$this->payload, 'amount' => $amount])->assertSessionHasNoErrors();
    expect($this->card->account->fresh()->balance_minor)->toBe($expected);
})->with([['0,01', 1], ['0.10', 10], ['1,2', 120], ['10', 1000], ['10000,00', 1000000]]);

it('rejects invalid or out of range amounts without changing the account', function (mixed $amount) {
    $this->post('/atm/deposits', [...$this->payload, 'amount' => $amount])->assertSessionHasErrors('amount');
    expect(Transaction::count())->toBe(0)->and($this->card->account->fresh()->balance_minor)->toBe(0);
})->with(['0', '-1', '1.001', '1e3', '10000,01', '999999999999999999', 'NaN', 25.5, '1.000,00']);

it('books a repeated request only once', function () {
    $this->post('/atm/deposits', $this->payload)->assertSessionHasNoErrors();
    $this->post('/atm/deposits', $this->payload)->assertSessionHasNoErrors();
    expect(Transaction::count())->toBe(1)->and($this->card->account->fresh()->balance_minor)->toBe(2550);
});

it('rejects reusing a request key for a different amount', function () {
    $this->post('/atm/deposits', $this->payload);
    $this->post('/atm/deposits', [...$this->payload, 'amount' => '30'])->assertSessionHasErrors('amount');
    expect(Transaction::count())->toBe(1)->and($this->card->account->fresh()->balance_minor)->toBe(2550);
});

it('ignores submitted account IDs and scopes history to the authenticated card', function () {
    $other = Card::where('demo_reference', 'DEMO-002')->firstOrFail();
    $this->post('/atm/deposits', [...$this->payload, 'account_id' => $other->account_id, 'card_id' => $other->id]);
    expect($other->account->fresh()->balance_minor)->toBe(0)->and($this->card->account->fresh()->balance_minor)->toBe(2550);
    $this->withSession(['atm_card_id' => $other->id, 'atm_last_activity' => now()->timestamp]);
    $this->get('/atm/session')->assertInertia(fn (Assert $page) => $page->where('balanceMinor', 0)->has('transactions.data', 0));
});

it('rejects a deposit when the session has expired', function () {
    $this->travel(config('atm.idle_seconds'))->seconds();
    $this->post('/atm/deposits', $this->payload)->assertRedirect(route('atm.cards'));
    expect(Transaction::count())->toBe(0)->and($this->card->account->fresh()->balance_minor)->toBe(0);
});

it('rejects blocked accounts and unauthenticated deposits', function () {
    $this->card->account->update(['status' => 'blocked']);
    $this->post('/atm/deposits', $this->payload)->assertRedirect(route('atm.cards'));
    $this->post('/atm/deposits', $this->payload)->assertRedirect(route('atm.cards'));
    expect(Transaction::count())->toBe(0);
});

it('rolls back the balance when creating the booking fails', function () {
    Event::listen('eloquent.creating: '.Transaction::class, fn () => throw new RuntimeException('Simulated storage failure'));
    $this->withoutExceptionHandling();
    expect(fn () => $this->post('/atm/deposits', $this->payload))->toThrow(RuntimeException::class, 'Simulated storage failure');
    expect(Transaction::count())->toBe(0)->and($this->card->account->fresh()->balance_minor)->toBe(0);
});

it('enforces the configured account balance ceiling', function () {
    config(['atm.max_balance_minor' => 2500]);
    $this->post('/atm/deposits', $this->payload)->assertSessionHasErrors('amount');
    expect(Transaction::count())->toBe(0)->and($this->card->account->fresh()->balance_minor)->toBe(0);
});

it('does not allow changing or deleting a booking through the model', function () {
    $this->post('/atm/deposits', $this->payload);
    $booking = Transaction::firstOrFail();
    expect(fn () => $booking->update(['amount_minor' => 99]))->toThrow(LogicException::class);
    expect(fn () => $booking->delete())->toThrow(LogicException::class);
    expect($booking->fresh()->amount_minor)->toBe(2550);
});

it('paginates the account history with newest bookings first', function () {
    for ($i = 0; $i < 11; $i++) {
        $this->post('/atm/deposits', ['amount' => '1', 'idempotency_key' => (string) Str::uuid()]);
    }
    $this->get('/atm/session')->assertInertia(fn (Assert $page) => $page->has('transactions.data', 10)->where('transactions.last_page', 2)->where('transactions.data.0.balance_after_minor', 1100));
    $this->get('/atm/session?page=2')->assertInertia(fn (Assert $page) => $page->has('transactions.data', 1)->where('transactions.data.0.balance_after_minor', 100));
});

it('requires a valid idempotency key', function () {
    $this->post('/atm/deposits', ['amount' => '1', 'idempotency_key' => 'invalid'])->assertSessionHasErrors('idempotency_key');
    expect(Transaction::count())->toBe(0);
});

it('records a pre-existing balance as an opening entry without changing it', function () {
    $migration = require database_path('migrations/2026_09_10_000001_create_transactions_table.php');
    $migration->down();
    $this->card->account->update(['balance_minor' => 12345]);
    $migration->up();
    expect($this->card->account->fresh()->balance_minor)->toBe(12345);
    $this->assertDatabaseHas('transactions', ['account_id' => $this->card->account_id, 'type' => 'opening', 'amount_minor' => 12345, 'card_id' => null]);
});
