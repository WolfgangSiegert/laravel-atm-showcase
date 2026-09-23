<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_operator;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'card_reference' => mb_strtoupper(trim((string) $this->input('card_reference'))),
            'expires_at' => $this->input('expires_at') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'card_reference' => ['required', 'string', 'min:4', 'max:32', 'regex:/\A[A-Z0-9][A-Z0-9-]*\z/', Rule::unique('cards', 'demo_reference')],
            'pin' => ['required', 'string', 'regex:/\A[0-9]{'.config('atm.pin_length').'}\z/'],
            'expires_at' => ['nullable', 'date_format:Y-m-d', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_reference.unique' => 'Diese Kartenreferenz ist bereits vergeben.',
            'card_reference.regex' => 'Die Kartenreferenz darf nur Großbuchstaben, Ziffern und Bindestriche enthalten.',
            'pin.regex' => 'Die PIN muss aus genau '.config('atm.pin_length').' Ziffern bestehen.',
            'expires_at.*' => 'Das Ablaufdatum muss in der Zukunft liegen.',
        ];
    }
}
