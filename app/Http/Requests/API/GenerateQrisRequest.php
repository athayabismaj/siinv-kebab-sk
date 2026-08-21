<?php

namespace App\Http\Requests\API;

class GenerateQrisRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function validationMessage(): string
    {
        return 'Transaction ID tidak valid.';
    }
}
