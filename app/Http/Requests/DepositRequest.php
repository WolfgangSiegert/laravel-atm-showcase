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
            'purpose' => ['nullable', 'string', 'max:140', 'regex:/\A[^\p{C}]*\z/u'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.*' => 'Bitte einen Betrag mit höchstens zwei Nachkommastellen eingeben, zum Beispiel 25,50.',
            'purpose.*' => 'Der Verwendungszweck darf höchstens 140 Zeichen enthalten und keine Steuerzeichen verwenden.',
            'idempotency_key.*' => 'Bitte lade die Seite neu und versuche es erneut.',
        ];
    }

    public function amountMinor(): int
    {
        $parts = explode('.', str_replace(',', '.', $this->validated('amount')));

        return ((int) $parts[0]) * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public function purpose(): ?string
    {
        $value = $this->validated('purpose');
        if (! is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return $normalized === '' ? null : $normalized;
    }
}
