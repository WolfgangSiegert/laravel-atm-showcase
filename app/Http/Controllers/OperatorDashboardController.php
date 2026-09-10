<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustCashInventoryRequest;
use App\Http\Requests\UpdateAtmStatusRequest;
use App\Models\Atm;
use App\Models\AuditEvent;
use App\Models\CashInventory;
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

        return Inertia::render('Operator/Dashboard', [
            'operatorName' => $request->user()->name,
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

        return to_route('operator.dashboard')->with('notice', 'Automatenstatus aktualisiert.');
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
                throw ValidationException::withMessages(['adjustment' => 'Der Bestand darf nicht negativ werden.']);
            }

            $inventory->update(['quantity' => $after]);
            $audit->record('atm.inventory_adjusted', 'success', atm: $inventory->atm, actor: $request->user(), context: [
                'denomination_minor' => $inventory->denomination_minor,
                'adjustment' => $adjustment,
                'before' => $before,
                'after' => $after,
            ]);
        });

        return to_route('operator.dashboard')->with('notice', 'Bargeldbestand aktualisiert.');
    }
}
