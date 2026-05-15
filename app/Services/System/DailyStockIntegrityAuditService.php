<?php

namespace App\Services\System;

use App\Models\DailyStockSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyStockIntegrityAuditService
{
    /**
     * @return array{
     *   scanned_sessions:int,
     *   findings_count:int,
     *   findings:array<int, array<string, mixed>>
     * }
     */
    public function audit(Carbon $dateFrom, Carbon $dateTo): array
    {
        $start = $dateFrom->copy()->startOfDay();
        $end = $dateTo->copy()->endOfDay();

        $sessions = DailyStockSession::query()
            ->whereDate('session_date', '>=', $start->toDateString())
            ->whereDate('session_date', '<=', $end->toDateString())
            ->with('items:id,daily_stock_session_id,ingredient_id,opening_qty,remaining_qty,used_qty')
            ->get(['id', 'session_date', 'cashier_id', 'status']);

        if ($sessions->isEmpty()) {
            return [
                'scanned_sessions' => 0,
                'findings_count' => 0,
                'findings' => [],
            ];
        }

        $cashierIds = $sessions->pluck('cashier_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();
        $usageRows = DB::table('stock_logs as sl')
            ->join('transactions as t', 't.id', '=', 'sl.reference_id')
            ->where('sl.type', 'daily_usage')
            ->whereIn('t.user_id', $cashierIds)
            ->whereBetween('t.created_at', [$start, $end])
            ->selectRaw('t.user_id, DATE(t.created_at) as usage_date, sl.ingredient_id, SUM(ABS(sl.quantity)) as used_total')
            ->groupBy('t.user_id', DB::raw('DATE(t.created_at)'), 'sl.ingredient_id')
            ->get();

        $usageMap = [];
        foreach ($usageRows as $row) {
            $key = $this->usageMapKey((int) $row->user_id, (string) $row->usage_date, (int) $row->ingredient_id);
            $usageMap[$key] = (float) $row->used_total;
        }

        $findings = [];
        foreach ($sessions as $session) {
            $sessionDate = Carbon::parse((string) $session->session_date)->toDateString();

            foreach ($session->items as $item) {
                $opening = (float) $item->opening_qty;
                $used = (float) $item->used_qty;
                $remaining = (float) $item->remaining_qty;

                $usageKey = $this->usageMapKey((int) $session->cashier_id, $sessionDate, (int) $item->ingredient_id);
                $usedFromTx = (float) ($usageMap[$usageKey] ?? 0);

                if (abs($used - $usedFromTx) > 0.009) {
                    $findings[] = [
                        'type' => 'used_vs_tx_mismatch',
                        'session_id' => (int) $session->id,
                        'session_date' => $sessionDate,
                        'cashier_id' => (int) $session->cashier_id,
                        'ingredient_id' => (int) $item->ingredient_id,
                        'used_qty' => $used,
                        'used_from_tx' => $usedFromTx,
                        'gap' => round($usedFromTx - $used, 2),
                    ];
                }

                if ($opening + 0.009 < $used) {
                    $findings[] = [
                        'type' => 'used_exceeds_opening',
                        'session_id' => (int) $session->id,
                        'session_date' => $sessionDate,
                        'cashier_id' => (int) $session->cashier_id,
                        'ingredient_id' => (int) $item->ingredient_id,
                        'opening_qty' => $opening,
                        'used_qty' => $used,
                    ];
                }

                if (abs(($opening - $used) - $remaining) > 0.009) {
                    $findings[] = [
                        'type' => 'opening_used_remaining_inconsistent',
                        'session_id' => (int) $session->id,
                        'session_date' => $sessionDate,
                        'cashier_id' => (int) $session->cashier_id,
                        'ingredient_id' => (int) $item->ingredient_id,
                        'opening_qty' => $opening,
                        'used_qty' => $used,
                        'remaining_qty' => $remaining,
                    ];
                }
            }
        }

        return [
            'scanned_sessions' => $sessions->count(),
            'findings_count' => count($findings),
            'findings' => $findings,
        ];
    }

    private function usageMapKey(int $cashierId, string $date, int $ingredientId): string
    {
        return "{$cashierId}|{$date}|{$ingredientId}";
    }
}
