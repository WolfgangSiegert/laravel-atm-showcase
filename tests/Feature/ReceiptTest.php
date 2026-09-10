<?php

use App\Models\Card;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    $this->card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $this->withSession(['atm_card_id' => $this->card->id, 'atm_last_activity' => now()->timestamp]);
});

it('shows a scoped printable receipt with masked references', function () {
    $this->post('/atm/deposits', ['amount' => '25,50', 'idempotency_key' => (string) Str::uuid()]);
    $transaction = Transaction::firstOrFail();

    $this->get(route('atm.receipt', $transaction->receipt_reference))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Atm/Receipt')
        ->where('receipt.reference', $transaction->receipt_reference)
        ->where('receipt.type', 'deposit')
        ->where('receipt.amountMinor', 2550)
        ->where('receipt.balanceAfterMinor', 2550)
        ->where('receipt.accountReference', '••••-001')
        ->where('receipt.cardReference', '••••-001')
        ->where('receipt.atmLabel', null)
        ->where('receipt.cashBreakdown', null));
});

it('includes the ATM and note breakdown on a withdrawal receipt', function () {
    $this->card->account->update(['balance_minor' => 50000]);
    $this->post('/atm/withdrawals', ['withdrawal_amount' => '130', 'idempotency_key' => (string) Str::uuid()]);
    $transaction = Transaction::firstOrFail();

    $this->get(route('atm.receipt', $transaction->receipt_reference))->assertInertia(fn (Assert $page) => $page
        ->where('receipt.type', 'withdrawal')
        ->where('receipt.atmLabel', 'Berlin Lern-Automat')
        ->where('receipt.cashBreakdown.10000', 1)
        ->where('receipt.cashBreakdown.2000', 1)
        ->where('receipt.cashBreakdown.1000', 1));
});

it('does not expose another accounts receipt', function () {
    $this->post('/atm/deposits', ['amount' => '10', 'idempotency_key' => (string) Str::uuid()]);
    $transaction = Transaction::firstOrFail();
    $other = Card::where('demo_reference', 'DEMO-002')->firstOrFail();

    $this->withSession(['atm_card_id' => $other->id, 'atm_last_activity' => now()->timestamp])
        ->get(route('atm.receipt', $transaction->receipt_reference))
        ->assertNotFound();
});

it('requires an active ATM session for receipts', function () {
    $this->post('/atm/deposits', ['amount' => '10', 'idempotency_key' => (string) Str::uuid()]);
    $transaction = Transaction::firstOrFail();

    $this->flushSession()->get(route('atm.receipt', $transaction->receipt_reference))
        ->assertRedirect(route('atm.cards'));
});

it('keeps the same receipt reference for an idempotent retry', function () {
    $payload = ['amount' => '10', 'idempotency_key' => (string) Str::uuid()];
    $first = $this->post('/atm/deposits', $payload);
    $reference = Transaction::firstOrFail()->receipt_reference;
    $second = $this->post('/atm/deposits', $payload);

    $first->assertRedirect(route('atm.receipt', $reference));
    $second->assertRedirect(route('atm.receipt', $reference));
    expect(Transaction::count())->toBe(1);
});
