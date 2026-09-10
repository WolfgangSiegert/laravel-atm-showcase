<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('atm_card');
    }

    public function rules(): array
    {
        return [
            'withdrawal_amount' => ['required', 'string', 'regex:/\A[0-9]{1,4}\z/'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'withdrawal_amount.*' => 'Bitte einen ganzen Eurobetrag ohne Trennzeichen eingeben.',
            'idempotency_key.*' => 'Bitte lade die Seite neu und versuche es erneut.',
        ];
    }

    public function amountMinor(): int
    {
        return ((int) $this->validated('withdrawal_amount')) * 100;
    }
}
