<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CashflowEntry;
use App\Support\AdminCache;
use Illuminate\Http\Request;

class CashflowController extends Controller
{
    use ApiResponse;

    public function storeExpense(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return $this->unauthorizedResponse();
        }

        $request->merge([
            'amount' => $request->input('amount', $request->input('nominal')),
            'source' => $request->input('source', $request->input('category')),
        ]);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'source' => 'required|string|max:120',
            'note' => 'nullable|string|max:255',
            'entry_date' => 'nullable|date',
        ]);

        $roleName = strtolower(trim((string) optional($user->role)->name));
        $canUseCustomDate = in_array($roleName, ['admin', 'owner'], true);
        $entryDate = $canUseCustomDate
            ? ($validated['entry_date'] ?? now()->toDateString())
            : now()->toDateString();

        $entry = CashflowEntry::query()->create([
            'entry_date' => $entryDate,
            'type' => 'expense',
            'amount' => (float) $validated['amount'],
            'source' => (string) $validated['source'],
            'note' => $validated['note'] ?? null,
            'created_by' => (int) $user->id,
        ]);

        AdminCache::bumpCashflow();

        return $this->successResponse('Pengeluaran operasional berhasil disimpan.', [
            'id' => $entry->id,
            'entry_date' => $entry->entry_date?->toDateString(),
            'amount' => (float) $entry->amount,
            'source' => $entry->source,
            'note' => $entry->note,
        ], 201);
    }
}
