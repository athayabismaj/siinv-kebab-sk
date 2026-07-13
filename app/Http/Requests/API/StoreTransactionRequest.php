<?php

namespace App\Http\Requests\API;

use App\Models\PaymentMethod;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $paymentMethodId = $this->input('payment_method_id');
        if (is_string($paymentMethodId) && is_numeric($paymentMethodId)) {
            $this->merge(['payment_method_id' => (int) $paymentMethodId]);
            return;
        }

        if (! empty($paymentMethodId)) {
            return;
        }

        $paymentMethodValue = $this->input('payment_method');
        if (is_array($paymentMethodValue)) {
            $paymentMethodObjectId = Arr::get($paymentMethodValue, 'id');
            if (is_numeric($paymentMethodObjectId)) {
                $this->merge(['payment_method_id' => (int) $paymentMethodObjectId]);
                return;
            }
        }

        $paymentMethodName = trim((string) (
            $this->input('payment_method_name')
            ?? $this->input('payment_method')
            ?? $this->input('payment_method_label')
            ?? Arr::get($this->input('payment_method'), 'name')
        ));

        if ($paymentMethodName === '') {
            return;
        }

        $methodId = PaymentMethod::query()
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(name) = ?', [strtolower($paymentMethodName)])
            ->value('id');

        if ($methodId) {
            $this->merge(['payment_method_id' => (int) $methodId]);
        }
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
            'note' => 'nullable|string|max:255',
        ];
    }

    protected function validationMessage(): string
    {
        return 'Validasi transaksi tidak valid.';
    }
}
