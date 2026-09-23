<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OperatorSessionController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()?->is_operator) {
            return to_route('operator.dashboard');
        }

        return Inertia::render('Operator/Login', [
            'guestAccessEnabled' => config('demo.admin_guest_enabled'),
        ]);
    }

    public function guestStore(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless(config('demo.admin_guest_enabled'), 404);

        $guest = User::query()
            ->where('email', config('demo.admin_guest_email'))
            ->where('is_operator', true)
            ->where('operator_role', User::OPERATOR_ROLE_VIEWER)
            ->firstOrFail();

        Auth::login($guest);
        $request->session()->regenerate();
        $audit->record('operator.guest_login', 'success', actor: $guest);

        return to_route('operator.dashboard');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'max:255'],
            ], [
                'email.*' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
                'password.*' => 'Bitte gib das Betreiberpasswort ein.',
            ]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('operator.login'));
        }
        $credentials['email'] = Str::lower($credentials['email']);
        $key = 'operator-login:'.hash('sha256', $credentials['email'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, config('atm.operator_requests_per_minute'))) {
            RateLimiter::attempt($key.':audit', 1, fn () => $audit->record('operator.login', 'rejected', reasonCode: 'rate_limited'), 60);
            throw ValidationException::withMessages(['email' => 'Zu viele Anmeldeversuche. Bitte warte eine Minute.'])
                ->redirectTo(route('operator.login'));
        }
        RateLimiter::hit($key, 60);

        if (! Auth::attempt([...$credentials, 'is_operator' => true])) {
            $audit->record('operator.login', 'rejected', reasonCode: 'invalid_credentials');
            throw ValidationException::withMessages(['email' => 'Die Betreiberanmeldung ist fehlgeschlagen.'])
                ->redirectTo(route('operator.login'));
        }

        $request->session()->regenerate();
        RateLimiter::clear($key);
        $audit->record('operator.login', 'success', actor: $request->user());

        return to_route('operator.dashboard');
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->record('operator.logout', 'success', actor: $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('operator.login')->with('notice', 'Betreibersitzung beendet.');
    }
}
