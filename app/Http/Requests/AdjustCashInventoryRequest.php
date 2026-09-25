<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustCashInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageAdministration();
    }

    public function rules(): array
    {
        return ['adjustment' => ['required', 'integer', 'between:-100,100', 'not_in:0']];
    }

    public function messages(): array
    {
        return ['adjustment.*' => __('Die Änderung muss zwischen −100 und 100 Scheinen liegen und darf nicht null sein.')];
    }
}
