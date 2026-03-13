<?php

namespace App\Http\Requests\API;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $this->validationMessage(),
            'data' => [
                'errors' => $validator->errors(),
            ],
        ], 422));
    }

    protected function validationMessage(): string
    {
        return 'Validasi request tidak valid.';
    }
}
