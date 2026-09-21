<?php

namespace App\Http\Controllers;

use App\Actions\DepositMoney;
use App\Http\Requests\DepositRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class DepositController extends Controller
{
    public function __invoke(DepositRequest $request, DepositMoney $deposit): RedirectResponse
    {
        try {
            $transaction = $deposit->execute(
                $request->attributes->get('atm_card'),
                $request->amountMinor(),
                $request->purpose(),
                $request->validated('idempotency_key'),
            );
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('atm.session'));
        }

        return to_route('atm.receipt', $transaction->receipt_reference)
            ->with('notice', 'Einzahlung erfolgreich gebucht.');
    }
}
