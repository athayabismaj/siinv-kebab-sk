<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
}
