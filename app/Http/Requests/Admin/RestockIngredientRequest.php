<?php

namespace App\Http\Requests\Admin;

use App\Models\Ingredient;
use App\Support\IngredientUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RestockIngredientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'input_unit' => ['nullable', 'string', 'in:pack,pcs'],
            'note' => ['nullable', 'string', 'max:255'],
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

                $quantity = (float) $this->input('quantity', 0);
                $baseQuantity = $this->input('input_unit') === 'pcs'
                    ? $quantity
                    : $quantity * max(1, (int) $ingredient->pack_size);

                if (! IngredientUnit::isValidBaseQuantity('pcs', $baseQuantity)) {
                    $validator->errors()->add(
                        'quantity',
                        'Jumlah harus menghasilkan PCS utuh. Gunakan satuan PCS untuk menambahkan stok satuan.'
                    );
                }
            },
        ];
    }
}
