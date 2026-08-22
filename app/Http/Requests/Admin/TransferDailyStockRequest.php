<?php

namespace App\Http\Requests\Admin;

use App\Models\DailyStockSession;
use App\Models\IngredientCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferDailyStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', Rule::exists((new DailyStockSession)->getTable(), 'id')],
            'transfers' => ['required', 'array'],
            'transfers.*.opening_quantity' => ['nullable', 'numeric', 'min:0'],
            'transfers.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'transfers.*.physical_quantity' => ['nullable', 'numeric', 'min:0'],
            'transfers.*.note' => ['nullable', 'string', 'max:255'],
            'transfers.*.transfer_unit' => ['nullable', 'in:pack,pcs,g,kg,ml,l'],
            'return_to' => ['nullable', 'in:index,transfer'],
            'return_page' => ['nullable', 'integer', 'min:1'],
            'return_category_id' => ['nullable', Rule::exists((new IngredientCategory)->getTable(), 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'session_id.required' => 'Sesi stok harian belum dipilih.',
            'session_id.exists' => 'Sesi stok harian tidak ditemukan atau sudah tidak aktif.',
            'transfers.required' => 'Pilih bahan dan isi jumlah transfer terlebih dahulu.',
            'transfers.array' => 'Data transfer tidak valid.',
            'transfers.*.quantity.min' => 'Jumlah tambahan bahan tidak boleh negatif.',
        ];
    }
}
