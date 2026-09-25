<?php

namespace App\Http\Controllers;

use App\Actions\WithdrawMoney;
use App\Http\Requests\WithdrawalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function __invoke(WithdrawalRequest $request, WithdrawMoney $withdraw): RedirectResponse
    {
        try {
            $transaction = $withdraw->execute(
                $request->attributes->get('atm_card'),
                $request->amountMinor(),
                $request->purpose(),
                $request->validated('idempotency_key'),
            );
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('atm.session'));
        }

        return to_route('atm.receipt', $transaction->receipt_reference)
            ->with('notice', __('Auszahlung erfolgreich gebucht.'));
    }
}
