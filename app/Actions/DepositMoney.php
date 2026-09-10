<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\Card;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepositMoney
{
    public function execute(Card $sessionCard, int $amount, ?string $purpose, string $key): Transaction
    {
        if ($amount < 1 || $amount > config('atm.max_deposit_minor')) {
            throw ValidationException::withMessages(['amount' => 'Der Betrag liegt außerhalb des erlaubten Einzahlungsbereichs.']);
        }

        return DB::transaction(function () use ($sessionCard, $amount, $purpose, $key) {
            $account = Account::lockForUpdate()->findOrFail($sessionCard->account_id);
            $card = Card::lockForUpdate()->findOrFail($sessionCard->id);
            $card->setRelation('account', $account);
            if ($card->account_id !== $account->id || ! $card->isUsable() || $account->currency !== 'EUR') {
                throw ValidationException::withMessages(['amount' => 'Karte oder Konto sind nicht für eine Einzahlung verfügbar.']);
            }
            $existing = Transaction::where('account_id', $account->id)->where('idempotency_key', $key)->first();
            if ($existing) {
                if ($existing->amount_minor !== $amount || $existing->purpose !== $purpose || $existing->card_id !== $card->id || $existing->type !== 'deposit') {
                    throw ValidationException::withMessages(['amount' => 'Diese Anfrage wurde bereits mit anderen Daten verwendet. Bitte lade die Seite neu.']);
                }

                return $existing;
            }
            if ($account->balance_minor > config('atm.max_balance_minor') - $amount) {
                throw ValidationException::withMessages(['amount' => 'Das Guthabenlimit des Demo-Kontos würde überschritten.']);
            }
            $account->balance_minor += $amount;
            $account->save();

            return Transaction::create([
                'account_id' => $account->id,
                'card_id' => $card->id,
                'type' => 'deposit',
                'purpose' => $purpose,
                'amount_minor' => $amount,
                'currency' => 'EUR',
                'balance_after_minor' => $account->balance_minor,
                'idempotency_key' => $key,
            ]);
        }, 3);
    }
}
