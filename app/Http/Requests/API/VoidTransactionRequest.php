<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;

class VoidTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_session_id' => 'required|integer',
            'reason' => 'required|string|in:restock,waste',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan (reason) wajib diisi.',
            'reason.in' => 'Alasan pembatalan harus berupa "restock" atau "waste".',
            'current_session_id.required' => 'ID sesi kasir aktif wajib dikirim.',
            'current_session_id.integer' => 'ID sesi kasir harus berupa angka.',
        ];
    }
}
