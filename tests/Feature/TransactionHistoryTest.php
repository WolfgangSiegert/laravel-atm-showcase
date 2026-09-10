<?php

use App\Models\Card;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(DatabaseSeeder::class);
    $this->card = Card::where('demo_reference', 'DEMO-001')->firstOrFail();
    $this->card->account->update(['balance_minor' => 100000]);
    $this->withSession(['atm_card_id' => $this->card->id, 'atm_last_activity' => now()->timestamp]);

    $this->post('/atm/deposits', ['amount' => '10', 'purpose' => 'Klein', 'idempotency_key' => (string) Str::uuid()]);
    $this->post('/atm/deposits', ['amount' => '30', 'purpose' => 'Groß', 'idempotency_key' => (string) Str::uuid()]);
    $this->post('/atm/withdrawals', ['withdrawal_amount' => '20', 'purpose' => 'Bar', 'idempotency_key' => (string) Str::uuid()]);
});

it('filters the account history by booking type', function () {
    $this->get('/atm/session?type=withdrawal')->assertInertia(fn (Assert $page) => $page
        ->where('historyFilters.type', 'withdrawal')
        ->has('transactions.data', 1)
        ->where('transactions.data.0.type', 'withdrawal')
        ->where('transactions.data.0.purpose', 'Bar'));
});

it('sorts the account history by amount in both directions', function () {
    $this->get('/atm/session?sort=amount&direction=asc')->assertInertia(fn (Assert $page) => $page
        ->where('historyFilters.sort', 'amount')
        ->where('historyFilters.direction', 'asc')
        ->where('transactions.data.0.amount_minor', 1000)
        ->where('transactions.data.2.amount_minor', 3000));

    $this->get('/atm/session?sort=amount&direction=desc')->assertInertia(fn (Assert $page) => $page
        ->where('transactions.data.0.amount_minor', 3000)
        ->where('transactions.data.2.amount_minor', 1000));
});

it('sorts equal timestamps deterministically by booking order', function () {
    $this->get('/atm/session?sort=date&direction=asc')->assertInertia(fn (Assert $page) => $page
        ->where('transactions.data.0.purpose', 'Klein')
        ->where('transactions.data.2.purpose', 'Bar'));

    $this->get('/atm/session?sort=date&direction=desc')->assertInertia(fn (Assert $page) => $page
        ->where('transactions.data.0.purpose', 'Bar')
        ->where('transactions.data.2.purpose', 'Klein'));
});

it('falls back to safe defaults for unsupported query values', function () {
    $this->get('/atm/session?type=transfer&sort=balance&direction=sideways')->assertInertia(fn (Assert $page) => $page
        ->where('historyFilters.type', '')
        ->where('historyFilters.sort', 'date')
        ->where('historyFilters.direction', 'desc')
        ->has('transactions.data', 3));
});

it('keeps filters and sorting in pagination URLs', function () {
    for ($i = 0; $i < 9; $i++) {
        $this->post('/atm/deposits', ['amount' => (string) ($i + 1), 'idempotency_key' => (string) Str::uuid()]);
    }

    $this->get('/atm/session?type=deposit&sort=amount&direction=asc')->assertInertia(fn (Assert $page) => $page
        ->where('transactions.next_page_url', fn ($url) => str_contains($url, 'type=deposit')
            && str_contains($url, 'sort=amount')
            && str_contains($url, 'direction=asc')));
});
