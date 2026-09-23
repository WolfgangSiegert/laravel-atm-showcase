<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminResourceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_operator;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'string', Rule::in(['active', 'blocked'])]];
    }
}
