<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminAccountRequest;
use App\Http\Requests\UpdateAdminResourceStatusRequest;
use App\Models\Account;
use App\Models\Card;
use App\Models\Customer;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminAccountController extends Controller
{
    public function store(StoreAdminAccountRequest $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $audit): void {
            $customer = isset($data['customer_id'])
                ? Customer::findOrFail($data['customer_id'])
                : Customer::create(['display_name' => $data['customer_name']]);
            $account = Account::create([
                'customer_id' => $customer->id,
                'reference' => $data['account_reference'],
                'currency' => 'EUR',
                'balance_minor' => 0,
                'status' => 'active',
            ]);
            $card = Card::create([
                'account_id' => $account->id,
                'demo_reference' => $data['card_reference'],
                'pin_hash' => $data['pin'],
                'status' => 'active',
            ]);

            $audit->record('account.created', 'success', card: $card, account: $account, actor: $request->user(), context: [
                'customer_id' => $customer->id,
            ]);
        });

        return to_route('operator.dashboard')->with('notice', 'Konto und erste Karte wurden angelegt.');
    }

    public function updateStatus(UpdateAdminResourceStatusRequest $request, Account $account, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $account, $audit): void {
            $lockedAccount = Account::lockForUpdate()->findOrFail($account->id);
            $before = $lockedAccount->status;
            $after = $request->validated('status');
            if ($before === $after) {
                return;
            }

            $lockedAccount->update(['status' => $after]);
            Card::where('account_id', $lockedAccount->id)->increment('session_version');
            $audit->record('account.status_changed', 'success', account: $lockedAccount, actor: $request->user(), context: [
                'before' => $before,
                'after' => $after,
            ]);
        });

        return to_route('operator.dashboard')->with('notice', 'Kontostatus wurde aktualisiert.');
    }
}
