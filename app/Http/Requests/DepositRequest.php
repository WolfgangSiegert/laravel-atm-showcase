<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('atm_card');
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'string', 'regex:/\A[0-9]{1,5}(?:[.,][0-9]{1,2})?\z/'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.*' => 'Bitte einen Betrag mit höchstens zwei Nachkommastellen eingeben, zum Beispiel 25,50.',
            'idempotency_key.*' => 'Bitte lade die Seite neu und versuche es erneut.',
        ];
    }

    public function amountMinor(): int
    {
        $parts = explode('.', str_replace(',', '.', $this->validated('amount')));

        return ((int) $parts[0]) * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }
}
