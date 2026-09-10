<?php

namespace App\Http\Controllers;

use App\Actions\DepositMoney;
use App\Http\Requests\DepositRequest;
use Illuminate\Http\RedirectResponse;

class DepositController extends Controller
{
    public function __invoke(DepositRequest $request, DepositMoney $deposit): RedirectResponse
    {
        $transaction = $deposit->execute(
            $request->attributes->get('atm_card'),
            $request->amountMinor(),
            $request->purpose(),
            $request->validated('idempotency_key'),
        );

        return to_route('atm.receipt', $transaction->receipt_reference)
            ->with('notice', 'Einzahlung erfolgreich gebucht.');
    }
}
