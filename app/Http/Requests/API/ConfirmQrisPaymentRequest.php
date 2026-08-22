<?php

namespace App\Http\Requests\API;

class ConfirmQrisPaymentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer', 'min:1'],
            'qris_reference' => ['required', 'string', 'max:40', 'regex:/^QRS-[A-Z0-9]{20}$/'],
        ];
    }

    protected function validationMessage(): string
    {
        return 'Referensi konfirmasi QRIS tidak valid.';
    }
}
