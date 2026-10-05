<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreShowcaseTrafficRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', Rule::in(['1'])],
            'site' => ['required', Rule::in(['joinsplit'])],
            'path' => ['required', 'string', Rule::in([(string) config('showcase_traffic.joinsplit.path')])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (array_diff(array_keys($this->all()), ['version', 'site', 'path']) !== []) {
                    $validator->errors()->add('payload', 'Unexpected showcase traffic fields are not allowed.');
                }
            },
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Invalid showcase traffic payload.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
