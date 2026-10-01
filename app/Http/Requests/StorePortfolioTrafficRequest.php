<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePortfolioTrafficRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', Rule::in(['1'])],
            'site' => ['required', Rule::in(['portfolio'])],
            'path' => ['required', 'string', Rule::in(config('portfolio_traffic.paths'))],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Invalid portfolio traffic payload.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
