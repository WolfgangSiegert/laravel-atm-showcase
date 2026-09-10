<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceiptController extends Controller
{
    public function __invoke(Request $request, string $receiptReference): Response
    {
        $sessionCard = $request->attributes->get('atm_card');
        $transaction = Transaction::with(['account', 'card', 'atm'])
            ->where('account_id', $sessionCard->account_id)
            ->where('receipt_reference', $receiptReference)
            ->firstOrFail();

        return Inertia::render('Atm/Receipt', [
            'receipt' => [
                'reference' => $transaction->receipt_reference,
                'type' => $transaction->type,
                'purpose' => $transaction->purpose,
                'amountMinor' => $transaction->amount_minor,
                'balanceAfterMinor' => $transaction->balance_after_minor,
                'currency' => $transaction->currency,
                'cashBreakdown' => $transaction->cash_breakdown,
                'createdAt' => $transaction->created_at->toIso8601String(),
                'cardReference' => $this->mask($transaction->card?->demo_reference),
                'accountReference' => $this->mask($transaction->account->reference),
                'atmLabel' => $transaction->atm?->label,
            ],
        ]);
    }

    private function mask(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return str_repeat('•', max(4, mb_strlen($value) - 4)).mb_substr($value, -4);
    }
}
