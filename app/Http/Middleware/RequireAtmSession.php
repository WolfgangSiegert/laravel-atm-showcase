<?php

namespace App\Http\Middleware;

use App\Models\Card;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RequireAtmSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $card = Card::with('account.customer')->find($request->session()->get('atm_card_id'));
        $lastActivity = (int) $request->session()->get('atm_last_activity', 0);
        if (! $card || ! $card->isUsable() || now()->timestamp - $lastActivity >= config('atm.idle_seconds')) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Inertia::clearHistory();

            return to_route('atm.cards')->with('notice', 'Bitte melde dich an. Deine Sitzung ist abgelaufen oder nicht mehr gültig.');
        }
        $request->session()->put('atm_last_activity', now()->timestamp);
        $request->attributes->set('atm_card', $card);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
