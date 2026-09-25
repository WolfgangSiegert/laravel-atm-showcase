<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustCashInventoryRequest;
use App\Http\Requests\UpdateAtmStatusRequest;
use App\Models\Account;
use App\Models\Atm;
use App\Models\AuditEvent;
use App\Models\Card;
use App\Models\CashInventory;
use App\Models\Customer;
use App\Models\Transaction;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OperatorDashboardController extends Controller
{
    public function show(Request $request): Response
    {
        $atm = Atm::with(['cashInventories' => fn ($query) => $query->orderByDesc('denomination_minor')])
            ->where('code', config('atm.code'))->firstOrFail();

        $transactions = Transaction::query()
            ->with(['account.customer', 'card'])
            ->latest('id')
            ->limit(50)
            ->get();
        $accounts = Account::query()
            ->with(['customer', 'cards'])
            ->orderBy('reference')
            ->get();
        $activityStart = now()->startOfDay()->subDays(6);
        $activity = Transaction::query()
            ->where('created_at', '>=', $activityStart)
            ->get(['type', 'amount_minor', 'created_at'])
            ->groupBy(fn (Transaction $transaction) => $transaction->created_at->toDateString());

        return Inertia::render('Operator/Dashboard', [
            'operatorName' => $request->user()->name,
            'operatorRole' => $request->user()->operator_role,
            'canManage' => $request->user()->canManageAdministration(),
            'metrics' => [
                'accounts' => $accounts->count(),
                'activeCards' => Card::where('status', 'active')->count(),
                'transactions' => Transaction::count(),
                'todayVolumeMinor' => Transaction::where('created_at', '>=', now()->startOfDay())->sum('amount_minor'),
            ],
            'atm' => [
                'code' => $atm->code,
                'label' => $atm->label,
                'status' => $atm->status,
                'currency' => $atm->currency,
                'totalMinor' => $atm->cashInventories->sum(fn ($item) => $item->denomination_minor * $item->quantity),
                'inventory' => $atm->cashInventories->map(fn ($item) => [
                    'id' => $item->id,
                    'denominationMinor' => $item->denomination_minor,
                    'quantity' => $item->quantity,
                ])->values(),
            ],
            'activity' => collect(range(0, 6))->map(function (int $offset) use ($activity, $activityStart) {
                $date = $activityStart->copy()->addDays($offset);
                $items = $activity->get($date->toDateString(), collect());

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->translatedFormat('D'),
                    'count' => $items->count(),
                    'amountMinor' => $items->sum('amount_minor'),
                ];
            }),
            'accounts' => $accounts->map(fn (Account $account) => [
                'id' => $account->id,
                'reference' => $account->reference,
                'customer' => $account->customer->display_name,
                'currency' => $account->currency,
                'balanceMinor' => $account->balance_minor,
                'status' => $account->status,
                'cards' => $account->cards->map(fn (Card $card) => [
                    'id' => $card->id,
                    'reference' => $card->demo_reference,
                    'status' => $card->status,
                    'failedAttempts' => $card->failed_attempts,
                    'lockedUntil' => $card->locked_until?->toIso8601String(),
                    'expiresAt' => $card->expires_at?->toDateString(),
                ])->values(),
            ]),
            'customers' => Customer::orderBy('display_name')->get(['id', 'display_name'])->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->display_name,
            ]),
            'transactions' => $transactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'receiptReference' => $transaction->receipt_reference,
                'accountReference' => $transaction->account->reference,
                'customer' => $transaction->account->customer->display_name,
                'cardReference' => $transaction->card?->demo_reference,
                'type' => $transaction->type,
                'purpose' => $transaction->purpose,
                'amountMinor' => $transaction->amount_minor,
                'balanceAfterMinor' => $transaction->balance_after_minor,
                'currency' => $transaction->currency,
                'createdAt' => $transaction->created_at->toIso8601String(),
            ]),
            'auditEvents' => AuditEvent::latest('id')->limit(50)->get([
                'id', 'event_type', 'outcome', 'reason_code', 'context', 'created_at',
            ]),
        ]);
    }

    public function updateStatus(UpdateAtmStatusRequest $request, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit): void {
            $atm = Atm::where('code', config('atm.code'))->lockForUpdate()->firstOrFail();
            $before = $atm->status;
            $after = $request->validated('status');

            if ($before === $after) {
                return;
            }

            $atm->update(['status' => $after]);
            $audit->record('atm.status_changed', 'success', atm: $atm, actor: $request->user(), context: [
                'before' => $before,
                'after' => $after,
            ]);
        });

        return to_route('operator.dashboard')->with('notice', __('Automatenstatus aktualisiert.'));
    }

    public function adjustInventory(AdjustCashInventoryRequest $request, CashInventory $cashInventory, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $cashInventory, $audit): void {
            $inventory = CashInventory::with('atm')->lockForUpdate()->findOrFail($cashInventory->id);
            abort_unless($inventory->atm->code === config('atm.code'), 404);
            $adjustment = (int) $request->validated('adjustment');
            $before = $inventory->quantity;
            $after = $before + $adjustment;

            if ($after < 0) {
                throw ValidationException::withMessages(['adjustment' => __('Der Bestand darf nicht negativ werden.')]);
            }

            $inventory->update(['quantity' => $after]);
            $audit->record('atm.inventory_adjusted', 'success', atm: $inventory->atm, actor: $request->user(), context: [
                'denomination_minor' => $inventory->denomination_minor,
                'adjustment' => $adjustment,
                'before' => $before,
                'after' => $after,
            ]);
        });

        return to_route('operator.dashboard')->with('notice', __('Bargeldbestand aktualisiert.'));
    }
}
