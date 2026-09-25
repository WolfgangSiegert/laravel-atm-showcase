<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    protected $redirectRoute = 'atm.session';

    public function authorize(): bool
    {
        return $this->attributes->has('atm_card');
    }

    public function rules(): array
    {
        return [
            'withdrawal_amount' => ['required', 'string', 'regex:/\A[0-9]{1,4}\z/'],
            'purpose' => ['nullable', 'string', 'max:140', 'regex:/\A[^\p{C}]*\z/u'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'withdrawal_amount.*' => __('Bitte einen ganzen Eurobetrag ohne Trennzeichen eingeben.'),
            'purpose.*' => __('Der Verwendungszweck darf höchstens 140 Zeichen enthalten und keine Steuerzeichen verwenden.'),
            'idempotency_key.*' => __('Bitte lade die Seite neu und versuche es erneut.'),
        ];
    }

    public function amountMinor(): int
    {
        return ((int) $this->validated('withdrawal_amount')) * 100;
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
