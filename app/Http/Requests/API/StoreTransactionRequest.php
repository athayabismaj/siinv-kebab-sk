<?php

namespace App\Http\Requests\API;

use App\Services\ApiTransactionService;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        app(ApiTransactionService::class)->normalizePaymentMethod($this);
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')->whereNull('deleted_at'),
            ],
            'paid_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:menu_variants,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ];
    }

    protected function validationMessage(): string
    {
        return 'Validasi transaksi tidak valid.';
    }
}
