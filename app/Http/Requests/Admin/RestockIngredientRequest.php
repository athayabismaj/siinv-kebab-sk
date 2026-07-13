<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
}
