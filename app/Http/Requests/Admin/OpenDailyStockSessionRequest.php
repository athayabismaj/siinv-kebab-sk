<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenDailyStockSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'cashier_id' => ['required', Rule::exists((new User())->getTable(), 'id')],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
