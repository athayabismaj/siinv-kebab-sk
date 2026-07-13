<?php

namespace App\Services\Exports;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SalesReportExportQuery
{
    public function transactionHistory(Carbon $start, Carbon $end, ?int $branchId): Builder
    {
        $query = Transaction::query()
            ->with(['user:id,name', 'paymentMethod:id,name'])
            ->withCount('details')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->latest('created_at')
            ->latest('id');

        BranchScope::apply($query, $branchId, 'branch_id');

        return $query;
    }

    public function summary(Carbon $start, Carbon $end, ?int $branchId): array
    {
        $aggregate = Transaction::query()
            ->successful()
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue');

        BranchScope::apply($aggregate, $branchId, 'branch_id');
        $aggregate = $aggregate->first();

        $totalTransactions = (int) ($aggregate->total_transactions ?? 0);
        $totalRevenue = (float) ($aggregate->total_revenue ?? 0);

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgTransaction' => $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0,
        ];
    }

    public function breakdown(Carbon $start, Carbon $end, ?int $branchId, string $type): array
    {
        if ($type !== 'daily') {
            return $this->dailyBreakdown($start, $end, $branchId);
        }

        $query = TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('menus', 'menus.id', '=', 'transaction_details.menu_id')
            ->leftJoin('menu_variants', 'menu_variants.id', '=', 'transaction_details.menu_variant_id')
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'menus.category_id')
            ->whereBetween('transactions.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->whereRaw("UPPER(COALESCE(transactions.status, '')) = ?", ['SUCCESS'])
            ->where(function (Builder $query) {
                $query->whereNull('menu_categories.id')
                    ->orWhere('menu_categories.is_addon', false);
            })
            ->selectRaw('menus.name as menu_name, menu_variants.name as variant_name, SUM(transaction_details.quantity) as total_qty, SUM(transaction_details.subtotal) as total_sales')
            ->groupBy('menus.name', 'menu_variants.name')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_sales')
            ->limit(10);

        BranchScope::apply($query, $branchId, 'transactions.branch_id');

        return $query->get()->map(fn ($row) => [
            'label' => $this->menuLabel((string) $row->menu_name, $row->variant_name === null ? null : (string) $row->variant_name),
            'count' => (int) $row->total_qty,
            'revenue' => (float) $row->total_sales,
        ])->all();
    }

    private function dailyBreakdown(Carbon $start, Carbon $end, ?int $branchId): array
    {
        $query = Transaction::query()
            ->successful()
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as trx_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date');

        BranchScope::apply($query, $branchId, 'branch_id');

        return $query->get()->map(fn ($row) => [
            'label' => Carbon::parse($row->date)->translatedFormat('d F Y'),
            'count' => (int) $row->trx_count,
            'revenue' => (float) $row->revenue,
        ])->all();
    }

    private function menuLabel(string $menuName, ?string $variantName): string
    {
        $menuName = trim($menuName);
        $variantName = trim((string) $variantName);

        if ($variantName === '' || str_starts_with(strtolower($variantName), strtolower($menuName))) {
            return $variantName === '' ? $menuName : $variantName;
        }

        return trim($menuName . ' ' . $variantName);
    }
}
