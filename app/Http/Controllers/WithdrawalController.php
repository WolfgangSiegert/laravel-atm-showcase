<?php

namespace App\Http\Controllers;

use App\Actions\WithdrawMoney;
use App\Http\Requests\WithdrawalRequest;
use Illuminate\Http\RedirectResponse;

class WithdrawalController extends Controller
{
    public function __invoke(WithdrawalRequest $request, WithdrawMoney $withdraw): RedirectResponse
    {
        $transaction = $withdraw->execute(
            $request->attributes->get('atm_card'),
            $request->amountMinor(),
            $request->validated('idempotency_key'),
        );

        return to_route('atm.receipt', $transaction->receipt_reference)
            ->with('notice', 'Auszahlung erfolgreich gebucht.');
    }
}
