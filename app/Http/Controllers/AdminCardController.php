<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminCardRequest;
use App\Http\Requests\UpdateAdminResourceStatusRequest;
use App\Models\Account;
use App\Models\Card;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCardController extends Controller
{
    public function store(StoreAdminCardRequest $request, Account $account, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $account, $audit): void {
            $card = Card::create([
                'account_id' => $account->id,
                'demo_reference' => $request->validated('card_reference'),
                'pin_hash' => $request->validated('pin'),
                'status' => 'active',
                'expires_at' => $request->validated('expires_at'),
            ]);
            $audit->record('card.created', 'success', card: $card, account: $account, actor: $request->user());
        });

        return to_route('operator.dashboard')->with('notice', 'Neue Karte wurde angelegt.');
    }

    public function updateStatus(UpdateAdminResourceStatusRequest $request, Card $card, AuditLogger $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $card, $audit): void {
            $lockedCard = Card::lockForUpdate()->findOrFail($card->id);
            $before = $lockedCard->status;
            $after = $request->validated('status');
            if ($before === $after) {
                return;
            }

            $lockedCard->status = $after;
            $lockedCard->session_version++;
            $lockedCard->save();
            $audit->record('card.status_changed', 'success', card: $lockedCard, actor: $request->user(), context: [
                'before' => $before,
                'after' => $after,
            ]);
        });

        return to_route('operator.dashboard')->with('notice', 'Kartenstatus wurde aktualisiert.');
    }

    public function resetLock(Request $request, Card $card, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->is_operator, 403);

        DB::transaction(function () use ($request, $card, $audit): void {
            $lockedCard = Card::lockForUpdate()->findOrFail($card->id);
            $attempts = $lockedCard->failed_attempts;
            $hadTimeLock = $lockedCard->locked_until !== null;
            $lockedCard->failed_attempts = 0;
            $lockedCard->locked_until = null;
            $lockedCard->session_version++;
            $lockedCard->save();
            $audit->record('card.lock_reset', 'success', card: $lockedCard, actor: $request->user(), context: [
                'failed_attempts_before' => $attempts,
                'had_time_lock' => $hadTimeLock,
            ]);
        });

        return to_route('operator.dashboard')->with('notice', 'PIN-Fehlversuche und Zeitsperre wurden zurückgesetzt.');
    }
}
