<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DailyTarget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DailyTargetController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $this->resolveDate((string) $request->input('date', now()->toDateString()));

        if (!Schema::hasTable('daily_targets')) {
            return view('owner.targets.daily', [
                'selectedDate' => $selectedDate,
                'target' => null,
                'targetRevenue' => 0.0,
                'targetTransactions' => 0,
                'actualRevenue' => 0.0,
                'actualTransactions' => 0,
                'revenueGap' => 0.0,
                'transactionGap' => 0,
            ])->with('error', 'Fitur target harian belum aktif. Jalankan migrasi tabel daily_targets terlebih dahulu.');
        }

        $target = DailyTarget::query()
            ->with('setBy:id,name')
            ->whereDate('target_date', '<=', $selectedDate->toDateString())
            ->orderByDesc('target_date')
            ->first();

        $actual = Transaction::query()
            ->whereBetween('created_at', [
                $selectedDate->copy()->startOfDay(),
                $selectedDate->copy()->endOfDay(),
            ])
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        $actualRevenue = (float) ($actual->total_revenue ?? 0);
        $actualTransactions = (int) ($actual->total_transactions ?? 0);
        $targetRevenue = (float) ($target->target_revenue ?? 0);
        $targetTransactions = (int) ($target->target_transactions ?? 0);

        return view('owner.targets.daily', [
            'selectedDate' => $selectedDate,
            'target' => $target,
            'targetRevenue' => $targetRevenue,
            'targetTransactions' => $targetTransactions,
            'actualRevenue' => $actualRevenue,
            'actualTransactions' => $actualTransactions,
            'revenueGap' => $actualRevenue - $targetRevenue,
            'transactionGap' => $actualTransactions - $targetTransactions,
        ]);
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('daily_targets')) {
            return redirect()
                ->route('owner.targets.index')
                ->with('error', 'Simpan target gagal karena tabel daily_targets belum tersedia. Jalankan migrasi dulu.');
        }

        $request->merge([
            'target_revenue' => $this->normalizeNumericInput($request->input('target_revenue')),
            'target_transactions' => $this->normalizeIntegerInput($request->input('target_transactions')),
        ]);

        $validated = $request->validate([
            'target_date' => 'required|date',
            'target_revenue' => 'required|numeric|min:0',
            'target_transactions' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ], [
            'target_date.required' => 'Tanggal target wajib diisi.',
            'target_date.date' => 'Tanggal target tidak valid.',
            'target_revenue.required' => 'Target omzet harian wajib diisi.',
            'target_revenue.numeric' => 'Target omzet harian harus berupa angka.',
            'target_revenue.min' => 'Target omzet harian tidak boleh kurang dari 0.',
            'target_transactions.required' => 'Target jumlah transaksi wajib diisi.',
            'target_transactions.integer' => 'Target jumlah transaksi harus berupa angka bulat.',
            'target_transactions.min' => 'Target jumlah transaksi tidak boleh kurang dari 0.',
            'notes.max' => 'Catatan target maksimal 500 karakter.',
        ]);

        $effectiveDate = Carbon::parse((string) $validated['target_date'])->startOfDay();

        DailyTarget::query()->updateOrCreate(
            ['target_date' => $effectiveDate->toDateString()],
            [
                'target_revenue' => (float) $validated['target_revenue'],
                'target_transactions' => (int) $validated['target_transactions'],
                'notes' => $validated['notes'] ?? null,
                'set_by_user_id' => auth()->id(),
            ]
        );

        return redirect()
            ->route('owner.targets.index', ['date' => $effectiveDate->toDateString()])
            ->with('success', 'Target default berhasil disimpan dan akan berlaku sampai diubah.');
    }

    private function resolveDate(string $dateInput): Carbon
    {
        try {
            return Carbon::parse($dateInput)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function normalizeNumericInput(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) ((float) $value);
        }

        $normalized = preg_replace('/[^\d.-]/', '', (string) $value);

        return $normalized === '' ? $value : $normalized;
    }

    private function normalizeIntegerInput(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_numeric($value) && floor((float) $value) === (float) $value) {
            return (string) ((int) $value);
        }

        return $value;
    }
}
