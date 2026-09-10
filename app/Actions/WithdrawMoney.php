<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\Atm;
use App\Models\Card;
use App\Models\CashInventory;
use App\Models\Transaction;
use App\Support\CashCombination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawMoney
{
    public function __construct(private CashCombination $cashCombination) {}

    public function execute(Card $sessionCard, int $amount, ?string $purpose, string $key): Transaction
    {
        if ($amount < config('atm.min_withdrawal_minor') || $amount > config('atm.max_withdrawal_minor')) {
            throw ValidationException::withMessages(['withdrawal_amount' => 'Der Betrag liegt außerhalb des erlaubten Auszahlungsbereichs.']);
        }

        return DB::transaction(function () use ($sessionCard, $amount, $purpose, $key) {
            $account = Account::lockForUpdate()->findOrFail($sessionCard->account_id);
            $card = Card::lockForUpdate()->findOrFail($sessionCard->id);
            $atm = Atm::where('code', config('atm.code'))->lockForUpdate()->first();

            if (! $atm) {
                throw ValidationException::withMessages(['withdrawal_amount' => 'Der Demo-Automat ist nicht eingerichtet.']);
            }

            $existing = Transaction::where('account_id', $account->id)
                ->where('idempotency_key', $key)
                ->first();

            if ($existing) {
                if ($existing->amount_minor !== $amount
                    || $existing->purpose !== $purpose
                    || $existing->card_id !== $card->id
                    || $existing->atm_id !== $atm->id
                    || $existing->type !== 'withdrawal') {
                    throw ValidationException::withMessages(['withdrawal_amount' => 'Diese Anfrage wurde bereits mit anderen Daten verwendet. Bitte lade die Seite neu.']);
                }

                return $existing;
            }

            $card->setRelation('account', $account);
            if (! $card->isUsable() || $atm->status !== 'active' || $account->currency !== $atm->currency) {
                throw ValidationException::withMessages(['withdrawal_amount' => 'Karte, Konto oder Automat sind nicht für eine Auszahlung verfügbar.']);
            }
            if ($account->balance_minor < $amount) {
                throw ValidationException::withMessages(['withdrawal_amount' => 'Das Demo-Guthaben reicht für diese Auszahlung nicht aus.']);
            }

            $inventories = CashInventory::where('atm_id', $atm->id)
                ->whereIn('denomination_minor', config('atm.withdrawal_denominations_minor'))
                ->orderByDesc('denomination_minor')
                ->lockForUpdate()
                ->get();
            $available = $inventories->pluck('quantity', 'denomination_minor')->all();
            $breakdown = $this->cashCombination->find($amount, $available);

            if (! $breakdown) {
                throw ValidationException::withMessages(['withdrawal_amount' => 'Der Automat kann diesen Betrag mit seinem aktuellen Scheinbestand nicht auszahlen.']);
            }

            foreach ($inventories as $inventory) {
                $quantity = $breakdown[$inventory->denomination_minor] ?? 0;
                if ($quantity > 0) {
                    $inventory->quantity -= $quantity;
                    $inventory->save();
                }
            }

            $account->balance_minor -= $amount;
            $account->save();

            return Transaction::create([
                'account_id' => $account->id,
                'card_id' => $card->id,
                'atm_id' => $atm->id,
                'type' => 'withdrawal',
                'purpose' => $purpose,
                'amount_minor' => $amount,
                'currency' => $account->currency,
                'balance_after_minor' => $account->balance_minor,
                'cash_breakdown' => $breakdown,
                'idempotency_key' => $key,
            ]);
        }, 3);
    }
}
