<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminResourceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageAdministration();
    }

    public function rules(): array
    {
        return ['status' => ['required', 'string', Rule::in(['active', 'blocked'])]];
    }
}
