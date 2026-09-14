<?php

namespace App\Actions;

use App\Models\Account;
use App\Models\Atm;
use App\Models\Card;
use App\Models\CashInventory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class ResetDemoData
{
    public function execute(bool $onlyIfDue = false): bool
    {
        if (! config('demo.enabled')) {
            throw new LogicException('Der öffentliche Demo-Modus ist nicht aktiviert.');
        }

        $lastReset = DB::table('demo_reset_state')->where('id', 1)->value('last_reset_at');
        if ($onlyIfDue && $lastReset && Carbon::parse($lastReset)->addSeconds(config('demo.reset_seconds'))->isFuture()) {
            return false;
        }
        if (! $lastReset) {
            DB::table('demo_reset_state')->insertOrIgnore(['id' => 1, 'last_reset_at' => now()]);
        }

        return DB::transaction(function () use ($onlyIfDue) {
            $state = DB::table('demo_reset_state')->where('id', 1)->lockForUpdate()->first();
            if ($onlyIfDue && Carbon::parse($state->last_reset_at)->addSeconds(config('demo.reset_seconds'))->isFuture()) {
                return false;
            }

            // Same Account -> Card -> ATM -> Inventory lock order as money movements.
            $accounts = Account::whereIn('reference', array_keys(config('demo.cards')))->orderBy('id')->lockForUpdate()->get();
            $cards = Card::whereIn('account_id', $accounts->modelKeys())->orderBy('id')->lockForUpdate()->get();
            $atm = Atm::where('code', config('atm.code'))->lockForUpdate()->first();

            // Intentional Query Builder deletion: only this opt-in maintenance path
            // may discard fictional history. No web route can invoke a forced reset.
            DB::table('audit_events')->whereIn('account_id', $accounts->modelKeys())->delete();
            DB::table('transactions')->whereIn('account_id', $accounts->modelKeys())->delete();
            foreach ($accounts as $account) {
                $account->update(['balance_minor' => 0, 'status' => 'active']);
            }
            foreach ($cards as $card) {
                $card->forceFill([
                    'pin_hash' => config('demo.cards.'.$card->demo_reference, $card->pin_hash),
                    'status' => 'active', 'expires_at' => null, 'failed_attempts' => 0,
                    'locked_until' => null, 'session_version' => $card->session_version + 1,
                ])->save();
            }
            if ($atm) {
                $atm->update(['status' => 'active']);
                $inventories = CashInventory::where('atm_id', $atm->id)->orderByDesc('denomination_minor')->lockForUpdate()->get();
                foreach ($inventories as $inventory) {
                    $quantity = config('atm.initial_cash_quantities')[$inventory->denomination_minor] ?? null;
                    if ($quantity !== null) {
                        $inventory->update(['quantity' => $quantity]);
                    }
                }
            }

            // A seven-day ceiling also bounds login/operator audit growth.
            DB::table('audit_events')->where('created_at', '<', now()->subDays(7))->delete();
            DB::table('demo_reset_state')->where('id', 1)->update(['last_reset_at' => now()]);

            return true;
        }, 3);
    }
}
