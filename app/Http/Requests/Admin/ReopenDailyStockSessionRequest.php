<?php

namespace App\Http\Requests\Admin;

use App\Models\DailyStockSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReopenDailyStockSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', Rule::exists((new DailyStockSession())->getTable(), 'id')],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
