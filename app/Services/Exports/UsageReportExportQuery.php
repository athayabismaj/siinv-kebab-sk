<?php

namespace App\Services\Exports;

use App\Models\StockLog;
use Carbon\Carbon;

class UsageReportExportQuery
{
    public function build(Carbon $start, Carbon $end, ?int $branchId)
    {
        return StockLog::query()->join('transactions', 'transactions.id', '=', 'stock_logs.reference_id')
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->where('stock_logs.type', 'daily_usage')
            ->whereBetween('stock_logs.created_at', [$start, $end])
            ->when($branchId, fn ($query) => $query->where('transactions.branch_id', $branchId))
            ->whereRaw("UPPER(COALESCE(transactions.status, '')) = ?", ['SUCCESS'])
            ->selectRaw('stock_logs.ingredient_id, ingredients.name as ingredient_name, ingredients.base_unit, ingredients.display_unit, ingredients.pack_size, SUM(ABS(stock_logs.quantity)) as total_quantity, COUNT(*) as usage_count, MAX(stock_logs.created_at) as last_used_at')
            ->groupBy('stock_logs.ingredient_id', 'ingredients.name', 'ingredients.base_unit', 'ingredients.display_unit', 'ingredients.pack_size')
            ->orderByDesc('total_quantity');
    }
}
