<?php

namespace App\Http\Requests\Admin;

use App\Models\Ingredient;
use App\Support\IngredientUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdjustIngredientStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_stock' => ['required', 'numeric', 'min:0'],
            'input_unit' => ['nullable', 'string', 'in:pack,pcs'],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $ingredient = $this->route('ingredient');
                if (! $ingredient instanceof Ingredient || (string) $ingredient->base_unit !== 'pcs') {
                    return;
                }

                $quantity = (float) $this->input('new_stock', 0);
                $baseQuantity = $this->input('input_unit') === 'pcs'
                    ? $quantity
                    : $quantity * max(1, (int) $ingredient->pack_size);

                if (! IngredientUnit::isValidBaseQuantity('pcs', $baseQuantity)) {
                    $validator->errors()->add(
                        'new_stock',
                        'Stok baru harus menghasilkan PCS utuh. Gunakan satuan PCS untuk stok satuan.'
                    );
                }
            },
        ];
    }
}
