<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\DirectExportResponse;
use App\Models\Branch;
use App\Models\CashflowEntry;
use App\Models\Transaction;
use App\Services\Shared\PeriodFilterService;
use App\Support\AdminCache;
use App\Support\BranchScope;
use App\Support\ReportBrand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CashflowController extends Controller
{
    use DirectExportResponse;

    public function __construct(
        private readonly PeriodFilterService $periodFilter
    ) {}

    public function index(Request $request)
    {
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->periodFilter->resolveDateRange($request, $type);
        $branchId = BranchScope::requestBranchId((int) $request->input('branch_id'));
        $branchOptions = BranchScope::options();

        $baseQuery = $this->baseExpenseQuery($dateFrom->toDateString(), $dateTo->toDateString(), $branchId);
        $this->applySearch($baseQuery, $request);

        $entries = (clone $baseQuery)->paginate(10)->withQueryString();
        $groupedEntries = $entries->getCollection()->groupBy(fn ($entry) => $entry->entry_date->toDateString());

        $summary = $this->summary(
            $baseQuery,
            $dateFrom->toDateTimeString(),
            $dateTo->copy()->endOfDay()->toDateTimeString(),
            $request
        );
        $summary['branchName'] = $this->branchLabel($branchId);

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            $this->periodFilter->buildNavigator($type, $dateFrom);

        $salesRevenue = (float) ($summary['salesRevenue'] ?? 0);
        $expenseTotal = (float) ($summary['expenseTotal'] ?? 0);
        $expenseCount = (int) ($summary['expenseCount'] ?? 0);
        $hpp          = (float) ($summary['hpp'] ?? 0);
        $netCash      = (float) ($summary['netCash'] ?? 0);

        return view('owner.reports.expenses.index', compact(
            'entries',
            'groupedEntries',
            'type',
            'dateFrom',
            'dateTo',
            'prevFrom',
            'prevTo',
            'nextFrom',
            'nextTo',
            'isFuture',
            'inputValue',
            'inputType',
            'salesRevenue',
            'expenseTotal',
            'hpp',
            'netCash',
            'expenseCount',
            'branchOptions',
            'branchId'
        ));
    }

    public function export(Request $request)
    {
        $format = (string) $request->query('format', 'excel');
        return $this->exportDirect($request, $format);
    }

    private function exportDirect(Request $request, string $format)
    {
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->periodFilter->resolveDateRange($request, $type);
        $branchId = BranchScope::requestBranchId((int) $request->input('branch_id'));

        $baseQuery = $this->baseExpenseQuery($dateFrom->toDateString(), $dateTo->toDateString(), $branchId);
        $this->applySearch($baseQuery, $request);

        $entries = (clone $baseQuery)->get();
        $summary = $this->summary(
            $baseQuery,
            $dateFrom->toDateTimeString(),
            $dateTo->copy()->endOfDay()->toDateTimeString(),
            $request
        );
        $summary['branchName'] = $this->branchLabel($branchId);

        $periodeLabel = $dateFrom->translatedFormat('d F Y') . ' s/d ' . $dateTo->translatedFormat('d F Y');
        if ($dateFrom->toDateString() === $dateTo->toDateString()) {
            $periodeLabel = $dateFrom->translatedFormat('d F Y');
        }

        $periodLabels = [
            'daily' => 'HARIAN',
            'weekly' => 'MINGGUAN',
            'monthly' => 'BULANAN',
            'custom' => 'KUSTOM'
        ];
        $periodLabelText = $periodLabels[$type] ?? strtoupper($type);

        $viewData = [
            'entries' => $entries,
            'periode' => $periodeLabel,
            'periodLabel' => $periodLabelText,
            'summary' => $summary,
            'logoDataUri' => ReportBrand::logoDataUri(),
            'isExcel' => $format === 'excel',
        ];

        $dateSuffix = $dateFrom->isSameDay($dateTo)
            ? $dateFrom->format('dMY')
            : $dateFrom->format('dM') . '-' . $dateTo->format('dMY');
        $fileName = 'Pengeluaran_' . $dateSuffix;
        return $this->exportByFormat(
            $format,
            'exports.expense_professional',
            $viewData,
            $fileName,
            fn () => \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ExpenseReportExport($entries, $periodeLabel, $summary, $periodLabelText, ReportBrand::logoPath()),
                $fileName . '.xlsx'
            )
        );
    }

    private function baseExpenseQuery(string $dateFrom, string $dateTo, ?int $branchId = null): Builder
    {
        $query = CashflowEntry::query()
            ->with(['creator:id,name', 'branch:id,name'])
            ->where('type', 'expense')
            ->whereBetween('entry_date', [$dateFrom, $dateTo])
            ->latest('entry_date')
            ->latest('id');

        BranchScope::apply($query, $branchId, 'branch_id');

        return $query;
    }

    private function summary(Builder $baseQuery, string $trxFrom, string $trxTo, Request $request): array
    {
        $summaryKey = AdminCache::key('cashflow', 'owner:expense:summary:' . md5(json_encode([
            'from' => $trxFrom,
            'to' => $trxTo,
            'type' => (string) $request->input('type', 'daily'),
            'search' => trim((string) $request->input('search', '')),
            'branch_id' => (string) $request->input('branch_id', ''),
        ])));

        return Cache::remember($summaryKey, now()->addSeconds(90), function () use ($baseQuery, $trxFrom, $trxTo) {
            $branchId = BranchScope::requestBranchId((int) request('branch_id'));

            $salesQuery = Transaction::query()
                ->whereBetween('created_at', [$trxFrom, $trxTo]);
            BranchScope::apply($salesQuery, $branchId, 'branch_id');
            $salesRevenue = (float) $salesQuery->sum('total_amount');

            $expenseAggregate = (clone $baseQuery)
                ->reorder()
                ->selectRaw('COALESCE(SUM(amount), 0) as expense_total, COUNT(*) as expense_count')
                ->first();

            $expenseTotal = (float) ($expenseAggregate->expense_total ?? 0);
            $expenseCount = (int) ($expenseAggregate->expense_count ?? 0);

            // HPP: estimasi nilai bahan terpakai pada periode ini
            $dateFrom = substr($trxFrom, 0, 10);
            $dateTo   = substr($trxTo,   0, 10);

            $hpp = (float) \Illuminate\Support\Facades\DB::table('daily_stock_sessions as dss')
                ->join('daily_stock_items as dsi', 'dsi.daily_stock_session_id', '=', 'dss.id')
                ->join('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
                ->whereBetween('dss.session_date', [$dateFrom, $dateTo])
                ->where('dss.status', 'closed')
                ->when($branchId, fn ($query) => $query->where('dss.branch_id', $branchId))
                ->selectRaw("COALESCE(SUM(
                    CASE ingredients.display_unit
                        WHEN 'kg'  THEN (dsi.used_qty / 1000.0) * ingredients.selling_price
                        WHEN 'l'   THEN (dsi.used_qty / 1000.0) * ingredients.selling_price
                        WHEN 'pcs' THEN (dsi.used_qty / GREATEST(COALESCE(ingredients.pack_size, 1), 1)) * ingredients.selling_price
                        ELSE            dsi.used_qty * ingredients.selling_price
                    END
                ), 0) as hpp_total")
                ->value('hpp_total');

            return [
                'salesRevenue' => $salesRevenue,
                'expenseTotal' => $expenseTotal,
                'expenseCount' => $expenseCount,
                'hpp'          => $hpp,
                'netCash'      => $salesRevenue - $expenseTotal,
            ];
        });
    }

    private function applySearch(Builder $query, Request $request): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $search = trim((string) $request->input('search'));
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('source', 'like', "%{$search}%")
                ->orWhere('note', 'like', "%{$search}%")
                ->orWhereHas('creator', function (Builder $u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                });
        });
    }

    private function branchLabel(?int $branchId): string
    {
        if (($branchId ?? 0) <= 0) {
            return 'Semua Cabang';
        }

        return Branch::query()->whereKey($branchId)->value('name') ?: 'Cabang tidak ditemukan';
    }
}
