<?php

namespace App\Services\Admin;

use App\Models\DailyStockSession;
use App\Support\AdminCache;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DailyStockReportQueryService
{
    public function paginated(Carbon $dateFrom, Carbon $dateTo, int $perPage = 10): LengthAwarePaginator
    {
        $sessions = $this->baseQuery($dateFrom, $dateTo)
            ->paginate($perPage)
            ->withQueryString();

        $sessions->setCollection(
            $sessions->getCollection()->map(fn (DailyStockSession $session) => $this->decorateSession($session))
        );

        return $sessions;
    }

    public function rows(Carbon $dateFrom, Carbon $dateTo): Collection
    {
        return $this->baseQuery($dateFrom, $dateTo)
            ->get()
            ->map(fn (DailyStockSession $session) => $this->decorateSession($session));
    }

    public function summary(Carbon $dateFrom, Carbon $dateTo): array
    {
        $branchId = $this->scopedBranchId();

        $cacheKey = AdminCache::key(
            'daily_stock',
            'summary:' . md5($dateFrom->toDateString() . '|' . $dateTo->toDateString() . '|' . (string) ($branchId ?? 'all'))
        );

        return Cache::remember($cacheKey, now()->addSeconds(120), function () use ($dateFrom, $dateTo, $branchId) {
            $sessionsCount = DailyStockSession::query()
                ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                ->whereBetween('session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->count();

            $itemsCount = DailyStockSession::query()
                ->leftJoin('daily_stock_items as dsi', 'dsi.daily_stock_session_id', '=', 'daily_stock_sessions.id')
                ->when($branchId, fn ($query) => $query->where('daily_stock_sessions.branch_id', $branchId))
                ->whereBetween('daily_stock_sessions.session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->count('dsi.id');

            // Kelompokkan per-satuan (display_unit) agar tidak menjumlahkan PCS + Liter + kg
            $byUnit = DailyStockSession::query()
                ->leftJoin('daily_stock_items as dsi', 'dsi.daily_stock_session_id', '=', 'daily_stock_sessions.id')
                ->leftJoin('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
                ->when($branchId, fn ($query) => $query->where('daily_stock_sessions.branch_id', $branchId))
                ->whereBetween('daily_stock_sessions.session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->whereNotNull('dsi.id')
                ->selectRaw("
                    UPPER(COALESCE(NULLIF(ingredients.base_unit, ''), NULLIF(ingredients.display_unit, ''), 'unit')) as unit_label,
                    COALESCE(SUM(dsi.opening_qty), 0) as total_opening,
                    COALESCE(SUM(dsi.remaining_qty), 0) as total_remaining,
                    COALESCE(SUM(dsi.used_qty), 0) as total_used
                ")
                ->groupBy('unit_label')
                ->orderByDesc('total_used')
                ->get()
                ->map(fn ($row) => [
                    'unit'      => $row->unit_label,
                    'opening'   => round((float) $row->total_opening, 2),
                    'remaining' => round((float) $row->total_remaining, 2),
                    'used'      => round((float) $row->total_used, 2),
                ])
                ->values()
                ->toArray();

            $valueAggregate = DailyStockSession::query()
                ->leftJoin('daily_stock_items as dsi', 'dsi.daily_stock_session_id', '=', 'daily_stock_sessions.id')
                ->leftJoin('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
                ->when($branchId, fn ($query) => $query->where('daily_stock_sessions.branch_id', $branchId))
                ->whereBetween('daily_stock_sessions.session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->selectRaw('COALESCE(SUM(' . $this->buildValueExpression('dsi') . '), 0) as total_value')
                ->value('total_value');

            $revenueAggregate = DailyStockSession::query()
                ->leftJoin('daily_stock_items as dsi', 'dsi.daily_stock_session_id', '=', 'daily_stock_sessions.id')
                ->leftJoin('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
                ->when($branchId, fn ($query) => $query->where('daily_stock_sessions.branch_id', $branchId))
                ->whereBetween('daily_stock_sessions.session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->selectRaw('COALESCE(SUM(' . $this->buildRevenueExpression('dsi') . '), 0) as total_revenue')
                ->value('total_revenue');

            return [
                'sessions_count'  => (int) $sessionsCount,
                'items_count'     => (int) $itemsCount,
                'by_unit'         => $byUnit,
                'total_value'     => (float) ($valueAggregate ?? 0),
                'total_revenue'   => (float) ($revenueAggregate ?? 0),
            ];
        });
    }

    private function baseQuery(Carbon $dateFrom, Carbon $dateTo)
    {
        $valueSubquery = \DB::table('daily_stock_items as dsi')
            ->join('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
            ->whereColumn('dsi.daily_stock_session_id', 'daily_stock_sessions.id')
            ->selectRaw('COALESCE(SUM(' . $this->buildValueExpression('dsi') . '), 0)');

        $revenueSubquery = \DB::table('daily_stock_items as dsi')
            ->join('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
            ->whereColumn('dsi.daily_stock_session_id', 'daily_stock_sessions.id')
            ->selectRaw('COALESCE(SUM(' . $this->buildRevenueExpression('dsi') . '), 0)');

        return DailyStockSession::query()
            ->with('cashier:id,name')
            ->withSum('items as total_opening', 'opening_qty')
            ->withSum('items as total_remaining', 'remaining_qty')
            ->withSum('items as total_used', 'used_qty')
            ->withCount('items')
            ->addSelect(['total_value' => $valueSubquery])
            ->addSelect(['total_revenue' => $revenueSubquery])
            ->when($this->scopedBranchId(), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->whereBetween('session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderByDesc('session_date')
            ->orderByDesc('id');
    }

    private function decorateSession(DailyStockSession $session): DailyStockSession
    {
        $session->total_opening = (float) ($session->total_opening ?? 0);
        $session->total_remaining = (float) ($session->total_remaining ?? 0);
        $session->total_used = (float) ($session->total_used ?? 0);
        $session->total_value = (float) ($session->total_value ?? 0);
        $session->total_revenue = (float) ($session->total_revenue ?? 0);

        return $session;
    }

    private function buildValueExpression(string $itemAlias = 'dsi'): string
    {
        $qty = "{$itemAlias}.used_qty";
        $price = 'COALESCE(NULLIF(ingredients.cost_price, 0), 0)';
        $packSize = 'GREATEST(COALESCE(ingredients.pack_size, 1), 1)';

        return "
            CASE ingredients.display_unit
                WHEN 'kg'  THEN ({$qty} / 1000.0) * {$price}
                WHEN 'l'   THEN ({$qty} / 1000.0) * {$price}
                WHEN 'pcs' THEN ({$qty} / {$packSize}) * {$price}
                ELSE            {$qty} * {$price}
            END
        ";
    }

    private function buildRevenueExpression(string $itemAlias = 'dsi'): string
    {
        $qty = "{$itemAlias}.used_qty";
        $price = 'COALESCE(NULLIF(ingredients.selling_price, 0), 0)';
        $packSize = 'GREATEST(COALESCE(ingredients.pack_size, 1), 1)';

        return "
            CASE ingredients.display_unit
                WHEN 'kg'  THEN ({$qty} / 1000.0) * {$price}
                WHEN 'l'   THEN ({$qty} / 1000.0) * {$price}
                WHEN 'pcs' THEN ({$qty} / {$packSize}) * {$price}
                ELSE            {$qty} * {$price}
            END
        ";
    }

    private function scopedBranchId(): ?int
    {
        return BranchScope::scopedBranchIdFor(auth()->user());
    }
}
