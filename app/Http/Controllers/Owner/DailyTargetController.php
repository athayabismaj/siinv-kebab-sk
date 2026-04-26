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

        $validated = $request->validate([
            'target_date' => 'required|date',
            'target_revenue' => 'required|numeric|min:0',
            'target_transactions' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
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
}
