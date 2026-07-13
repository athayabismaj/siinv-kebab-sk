<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class CloseDailyStockSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remaining' => 'required|array',
            'notes' => 'nullable|string|max:255',
        ];
    }
}
