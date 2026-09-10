<?php

use App\Models\Atm;
use App\Models\Card;
use App\Models\CashInventory;
use App\Models\Transaction;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAtmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    $this->card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $this->card->account->update(['balance_minor' => 100000]);
    $this->atm = Atm::where('code', config('atm.code'))->firstOrFail();
    $this->withSession(['atm_card_id' => $this->card->id, 'atm_last_activity' => now()->timestamp]);
    $this->payload = ['withdrawal_amount' => '130', 'idempotency_key' => (string) Str::uuid()];
});

it('dispenses available notes and debits the account atomically', function () {
    $before = CashInventory::where('atm_id', $this->atm->id)->pluck('quantity', 'denomination_minor');

    $response = $this->post('/atm/withdrawals', $this->payload);

    $booking = Transaction::firstOrFail();
    $response->assertRedirect(route('atm.receipt', $booking->receipt_reference));
    expect($this->card->account->fresh()->balance_minor)->toBe(87000)
        ->and($booking->type)->toBe('withdrawal')
        ->and($booking->amount_minor)->toBe(13000)
        ->and($booking->balance_after_minor)->toBe(87000)
        ->and($booking->cash_breakdown)->toBe(['10000' => 1, '2000' => 1, '1000' => 1])
        ->and(CashInventory::where('atm_id', $this->atm->id)->where('denomination_minor', 10000)->value('quantity'))->toBe($before[10000] - 1)
        ->and(CashInventory::where('atm_id', $this->atm->id)->where('denomination_minor', 2000)->value('quantity'))->toBe($before[2000] - 1)
        ->and(CashInventory::where('atm_id', $this->atm->id)->where('denomination_minor', 1000)->value('quantity'))->toBe($before[1000] - 1);
});

it('stores the optional purpose on a withdrawal and its receipt', function () {
    $this->post('/atm/withdrawals', [...$this->payload, 'purpose' => '  Reisekasse  '])->assertSessionHasNoErrors();
    $booking = Transaction::firstOrFail();

    expect($booking->purpose)->toBe('Reisekasse');
    $this->get(route('atm.receipt', $booking->receipt_reference))->assertInertia(fn (Assert $page) => $page
        ->where('receipt.purpose', 'Reisekasse'));
});

it('returns the same withdrawal for a repeated request', function () {
    $this->post('/atm/withdrawals', $this->payload)->assertSessionHasNoErrors();
    $inventory = CashInventory::where('atm_id', $this->atm->id)->pluck('quantity', 'denomination_minor')->all();

    $this->post('/atm/withdrawals', $this->payload)->assertSessionHasNoErrors();

    expect(Transaction::count())->toBe(1)
        ->and($this->card->account->fresh()->balance_minor)->toBe(87000)
        ->and(CashInventory::where('atm_id', $this->atm->id)->pluck('quantity', 'denomination_minor')->all())->toBe($inventory);
});

it('rejects reusing a request key for a different withdrawal', function () {
    $this->post('/atm/withdrawals', $this->payload);
    $this->post('/atm/withdrawals', [...$this->payload, 'withdrawal_amount' => '140'])
        ->assertSessionHasErrors('withdrawal_amount');

    expect(Transaction::count())->toBe(1)
        ->and($this->card->account->fresh()->balance_minor)->toBe(87000);
});

it('rejects reusing a withdrawal key for a different purpose', function () {
    $this->post('/atm/withdrawals', [...$this->payload, 'purpose' => 'Erster Zweck']);
    $this->post('/atm/withdrawals', [...$this->payload, 'purpose' => 'Anderer Zweck'])
        ->assertSessionHasErrors('withdrawal_amount');

    expect(Transaction::count())->toBe(1)
        ->and(Transaction::firstOrFail()->purpose)->toBe('Erster Zweck')
        ->and($this->card->account->fresh()->balance_minor)->toBe(87000);
});

it('rejects invalid withdrawal formats and limits without changing state', function (mixed $amount) {
    $inventory = CashInventory::where('atm_id', $this->atm->id)->pluck('quantity', 'denomination_minor')->all();

    $this->post('/atm/withdrawals', [...$this->payload, 'withdrawal_amount' => $amount])
        ->assertSessionHasErrors('withdrawal_amount');

    expect(Transaction::count())->toBe(0)
        ->and($this->card->account->fresh()->balance_minor)->toBe(100000)
        ->and(CashInventory::where('atm_id', $this->atm->id)->pluck('quantity', 'denomination_minor')->all())->toBe($inventory);
})->with(['0', '9', '25', '1001', '-10', '10,00', '1e2', 50]);

it('rejects an amount above the account balance', function () {
    $this->card->account->update(['balance_minor' => 12000]);

    $this->post('/atm/withdrawals', $this->payload)->assertSessionHasErrors([
        'withdrawal_amount' => 'Das Demo-Guthaben reicht für diese Auszahlung nicht aus.',
    ]);

    expect(Transaction::count())->toBe(0)->and($this->card->account->fresh()->balance_minor)->toBe(12000);
});

it('rejects an amount the current inventory cannot represent', function () {
    CashInventory::where('atm_id', $this->atm->id)->update(['quantity' => 0]);
    CashInventory::where('atm_id', $this->atm->id)->where('denomination_minor', 5000)->update(['quantity' => 1]);

    $this->post('/atm/withdrawals', [...$this->payload, 'withdrawal_amount' => '60'])
        ->assertSessionHasErrors('withdrawal_amount');

    expect(Transaction::count())->toBe(0)->and($this->card->account->fresh()->balance_minor)->toBe(100000);
});

it('finds a non-greedy combination with limited inventory', function () {
    CashInventory::where('atm_id', $this->atm->id)->update(['quantity' => 0]);
    CashInventory::where('atm_id', $this->atm->id)->where('denomination_minor', 5000)->update(['quantity' => 1]);
    CashInventory::where('atm_id', $this->atm->id)->where('denomination_minor', 2000)->update(['quantity' => 3]);

    $this->post('/atm/withdrawals', [...$this->payload, 'withdrawal_amount' => '60'])->assertSessionHasNoErrors();

    expect(Transaction::firstOrFail()->cash_breakdown)->toBe(['2000' => 3]);
});

it('rolls back balance and inventory when booking storage fails', function () {
    $inventory = CashInventory::where('atm_id', $this->atm->id)->pluck('quantity', 'denomination_minor')->all();
    Event::listen('eloquent.creating: '.Transaction::class, fn () => throw new RuntimeException('Simulated storage failure'));
    $this->withoutExceptionHandling();

    expect(fn () => $this->post('/atm/withdrawals', $this->payload))
        ->toThrow(RuntimeException::class, 'Simulated storage failure');
    expect(Transaction::count())->toBe(0)
        ->and($this->card->account->fresh()->balance_minor)->toBe(100000)
        ->and(CashInventory::where('atm_id', $this->atm->id)->pluck('quantity', 'denomination_minor')->all())->toBe($inventory);
});

it('rejects inactive ATMs and expired sessions', function () {
    $this->atm->update(['status' => 'maintenance']);
    $this->post('/atm/withdrawals', $this->payload)->assertSessionHasErrors('withdrawal_amount');

    $this->atm->update(['status' => 'active']);
    $this->travel(config('atm.idle_seconds'))->seconds();
    $this->post('/atm/withdrawals', $this->payload)->assertRedirect(route('atm.cards'));

    expect(Transaction::count())->toBe(0);
});

it('ignores submitted account ATM and card IDs', function () {
    $other = Card::where('demo_reference', 'DEMO-002')->firstOrFail();
    $this->post('/atm/withdrawals', [
        ...$this->payload,
        'account_id' => $other->account_id,
        'card_id' => $other->id,
        'atm_id' => 999999,
    ])->assertSessionHasNoErrors();

    expect($other->account->fresh()->balance_minor)->toBe(0)
        ->and($this->card->account->fresh()->balance_minor)->toBe(87000)
        ->and(Transaction::firstOrFail()->card_id)->toBe($this->card->id)
        ->and(Transaction::firstOrFail()->atm_id)->toBe($this->atm->id);
});

it('shows withdrawals and their note breakdown only on the session account', function () {
    $this->post('/atm/withdrawals', $this->payload);

    $this->get('/atm/session')->assertInertia(fn (Assert $page) => $page
        ->where('balanceMinor', 87000)
        ->where('atmAvailable', true)
        ->where('denominationsMinor', [1000, 2000, 5000, 10000])
        ->where('transactions.data.0.type', 'withdrawal')
        ->where('transactions.data.0.cash_breakdown.10000', 1));
});

it('does not reset depleted inventory when seeded again', function () {
    CashInventory::where('atm_id', $this->atm->id)->where('denomination_minor', 10000)->update(['quantity' => 1]);

    $this->seed(DemoAtmSeeder::class);

    expect(Atm::count())->toBe(1)
        ->and(CashInventory::where('atm_id', $this->atm->id)->count())->toBe(4)
        ->and(CashInventory::where('atm_id', $this->atm->id)->where('denomination_minor', 10000)->value('quantity'))->toBe(1);
});
