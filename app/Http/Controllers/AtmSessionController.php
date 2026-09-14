<?php

namespace App\Http\Controllers;

use App\Models\Atm;
use App\Models\Card;
use App\Models\Transaction;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AtmSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Atm/SignIn', [
            'cards' => Card::orderBy('demo_reference')->get(['id', 'demo_reference']),
            'pinLength' => config('atm.pin_length'),
            'demoAccess' => config('demo.enabled') ? config('demo.cards') : null,
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->session()->forget(['atm_card_id', 'atm_last_activity', 'atm_session_version']);
        $key = 'atm-login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, config('atm.requests_per_minute'))) {
            RateLimiter::attempt($key.':audit', 1, fn () => $audit->record('atm_session.login', 'rejected', reasonCode: 'rate_limited'), 60);
            throw ValidationException::withMessages(['pin' => 'Zu viele Versuche. Bitte warte eine Minute.'])
                ->redirectTo(route('atm.cards'));
        }
        RateLimiter::hit($key, 60);
        try {
            $data = $request->validate([
                'card_id' => ['required', 'integer'],
                'pin' => ['required', 'string', 'regex:/\A[0-9]{'.config('atm.pin_length').'}\z/'],
            ], [
                'card_id.required' => 'Bitte wähle eine Demo-Karte.',
                'card_id.integer' => 'Bitte wähle eine gültige Demo-Karte.',
                'pin.required' => 'Bitte gib deine PIN ein.',
                'pin.regex' => 'Die PIN muss aus '.config('atm.pin_length').' Ziffern bestehen.',
                'pin.string' => 'Bitte gib deine PIN als Ziffernfolge ein.',
            ]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('atm.cards'));
        }

        $card = DB::transaction(function () use ($data): ?Card {
            $card = Card::with('account')->lockForUpdate()->find($data['card_id']);
            if (! $card || ! $card->isUsable()) {
                return null;
            }
            if ($card->locked_until) {
                $card->failed_attempts = 0;
                $card->locked_until = null;
            }
            if (! Hash::check($data['pin'], $card->pin_hash)) {
                $card->failed_attempts++;
                if ($card->failed_attempts >= config('atm.max_pin_attempts')) {
                    $card->locked_until = now()->addSeconds(config('atm.lock_seconds'));
                }
                $card->save();

                return null;
            }
            $card->failed_attempts = 0;
            $card->locked_until = null;
            $card->save();

            return $card;
        }, 3);

        if (! $card) {
            $auditCard = Card::find($data['card_id']);
            $audit->record('atm_session.login', 'rejected', card: $auditCard, reasonCode: 'invalid_credentials_or_card');
            // Throw outside the transaction so failed attempts remain persisted.
            throw ValidationException::withMessages([
                'pin' => 'Anmeldung nicht möglich. Prüfe Karte und PIN. Eine gesperrte oder abgelaufene Karte kann nicht verwendet werden.',
            ])->redirectTo(route('atm.cards'));
        }
        $request->session()->regenerate(true);
        $request->session()->put([
            'atm_card_id' => $card->id,
            'atm_session_version' => $card->session_version,
            'atm_last_activity' => now()->timestamp,
        ]);
        Inertia::clearHistory();
        $audit->record('atm_session.login', 'success', card: $card);

        return to_route('atm.session');
    }

    public function show(Request $request): Response
    {
        $card = $request->attributes->get('atm_card');
        $atm = Atm::with('cashInventories')->where('code', config('atm.code'))->first();
        $type = in_array($request->query('type'), ['deposit', 'withdrawal', 'opening'], true) ? $request->query('type') : null;
        $sort = in_array($request->query('sort'), ['date', 'amount'], true) ? $request->query('sort') : 'date';
        $direction = in_array($request->query('direction'), ['asc', 'desc'], true) ? $request->query('direction') : 'desc';
        $sortColumn = $sort === 'amount' ? 'amount_minor' : 'created_at';
        $transactions = Transaction::where('account_id', $card->account_id)
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderBy($sortColumn, $direction)
            ->orderBy('id', $direction)
            ->paginate(10, ['id', 'receipt_reference', 'type', 'purpose', 'amount_minor', 'balance_after_minor', 'cash_breakdown', 'currency', 'created_at'])
            ->withQueryString();

        return Inertia::render('Atm/Session', [
            'balanceMinor' => $card->account->balance_minor,
            'currency' => $card->account->currency,
            'depositKey' => (string) Str::uuid(),
            'withdrawalKey' => (string) Str::uuid(),
            'maxDepositMinor' => config('atm.max_deposit_minor'),
            'minWithdrawalMinor' => config('atm.min_withdrawal_minor'),
            'maxWithdrawalMinor' => config('atm.max_withdrawal_minor'),
            'denominationsMinor' => $atm?->cashInventories->where('quantity', '>', 0)->sortBy('denomination_minor')->pluck('denomination_minor')->values() ?? [],
            'atmAvailable' => $atm?->status === 'active',
            'transactions' => $transactions,
            'historyFilters' => [
                'type' => $type ?? '',
                'sort' => $sort,
                'direction' => $direction,
            ],
            'customerName' => $card->account->customer->display_name,
            'cardReference' => $card->demo_reference,
            'accountReference' => $card->account->reference,
            'expiresAt' => now()->addSeconds(config('atm.idle_seconds'))->toIso8601String(),
            'idleSeconds' => config('atm.idle_seconds'),
        ]);
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $card = Card::find($request->session()->get('atm_card_id'));
        if ($card) {
            $audit->record('atm_session.logout', 'success', card: $card);
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return to_route('atm.cards')->with('notice', 'Deine Sitzung wurde beendet.');
    }
}
