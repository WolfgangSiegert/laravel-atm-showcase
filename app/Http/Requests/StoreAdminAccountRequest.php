<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_operator;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->input('customer_id') ?: null,
            'customer_name' => trim((string) $this->input('customer_name')) ?: null,
            'account_reference' => mb_strtoupper(trim((string) $this->input('account_reference'))),
            'card_reference' => mb_strtoupper(trim((string) $this->input('card_reference'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'customer_name' => ['nullable', 'required_without:customer_id', 'string', 'min:2', 'max:80'],
            'account_reference' => ['required', 'string', 'min:4', 'max:32', 'regex:/\A[A-Z0-9][A-Z0-9-]*\z/', Rule::unique('accounts', 'reference')],
            'card_reference' => ['required', 'string', 'min:4', 'max:32', 'regex:/\A[A-Z0-9][A-Z0-9-]*\z/', Rule::unique('cards', 'demo_reference')],
            'pin' => ['required', 'string', 'regex:/\A[0-9]{'.config('atm.pin_length').'}\z/'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required_without' => 'Bitte wähle eine bestehende Person oder gib einen neuen Namen ein.',
            'customer_name.*' => 'Der Name muss zwischen 2 und 80 Zeichen lang sein.',
            'account_reference.unique' => 'Diese Kontoreferenz ist bereits vergeben.',
            'account_reference.regex' => 'Die Kontoreferenz darf nur Großbuchstaben, Ziffern und Bindestriche enthalten.',
            'card_reference.unique' => 'Diese Kartenreferenz ist bereits vergeben.',
            'card_reference.regex' => 'Die Kartenreferenz darf nur Großbuchstaben, Ziffern und Bindestriche enthalten.',
            'pin.regex' => 'Die PIN muss aus genau '.config('atm.pin_length').' Ziffern bestehen.',
        ];
    }
}
