<?php

namespace App\Http\Controllers\Owner;

use App\Exports\StockLogsReportExport;
use App\Http\Controllers\Concerns\DirectExportResponse;
use App\Http\Controllers\Controller;
use App\Models\StockLog;
use App\Support\BranchScope;
use App\Support\ReportBrand;
use App\Support\StockLogTypeMap;
use App\Support\StockLogView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockLogController extends Controller
{
    use DirectExportResponse;

    public function index(Request $request)
    {
        $period = StockLogView::normalizePeriod($request->input('period'));
        $selectedDate = StockLogView::parseSelectedDate($request->input('date'));
        [$rangeStart, $rangeEnd] = StockLogView::resolveRange($period, $selectedDate);
        $typeFilter = $request->input('type');
        $branchId = BranchScope::ownerBranchId((int) $request->input('branch_id'));
        $branchOptions = BranchScope::options();

        $summary = $this->summary($rangeStart, $rangeEnd, $typeFilter, $branchId);
        $summaryCards = StockLogView::summaryCards($summary);

        $logs = $this->buildStockLogsQuery($rangeStart, $rangeEnd, $typeFilter, $branchId)
            ->paginate(10)
            ->withQueryString();

        $logs->setCollection(
            $logs->getCollection()->map(fn (StockLog $log) => StockLogView::decorate($log))
        );

        $groupedLogs = $logs->getCollection()
            ->groupBy('group_date')
            ->map(function ($items) {
                /** @var StockLog $first */
                $first = $items->first();
                $groupLabel = $first->created_at->translatedFormat('d F Y');

                if ($first->created_at->isToday()) {
                    $groupLabel = 'Hari ini - ' . $groupLabel;
                } elseif ($first->created_at->isYesterday()) {
                    $groupLabel = 'Kemarin - ' . $groupLabel;
                }

                return [
                    'label' => $groupLabel,
                    'items' => $items,
                ];
            });

        $baseParams = array_filter([
            'period' => $period,
            'type' => $typeFilter,
            'branch_id' => $branchId,
        ], fn ($value) => $value !== null && $value !== '');

        $prevDate = StockLogView::navigationDate($period, $selectedDate, 'prev');
        $nextDate = StockLogView::navigationDate($period, $selectedDate, 'next');

        $prevParams = array_merge($baseParams, ['date' => $prevDate->toDateString()]);
        $nextParams = array_merge($baseParams, ['date' => $nextDate->toDateString()]);
        $isNextDisabled = $nextDate->startOfDay()->gt(now()->startOfDay());
        $dateDisplay = StockLogView::dateDisplay($period, $selectedDate, $rangeStart, $rangeEnd);
        $typeTabs = collect(StockLogView::typeTabs($typeFilter))
            ->map(function (array $tab) {
                $params = request()->query();

                if ($tab['key'] === null) {
                    unset($params['type']);
                } else {
                    $params['type'] = $tab['key'];
                }

                $tab['href'] = route('owner.stock-logs.index', $params);
                return $tab;
            })
            ->values();

        return view('owner.stock_logs.index', compact(
            'logs',
            'groupedLogs',
            'summary',
            'summaryCards',
            'period',
            'selectedDate',
            'rangeStart',
            'rangeEnd',
            'prevParams',
            'nextParams',
            'isNextDisabled',
            'dateDisplay',
            'typeFilter',
            'typeTabs',
            'branchOptions',
            'branchId'
        ));
    }

    public function export(Request $request)
    {
        $format = $request->query('format');
        return $this->exportDirect($request, in_array($format, ['html', 'pdf', 'excel'], true) ? $format : 'excel');
    }

    private function exportDirect(Request $request, string $format)
    {
        $this->raiseMemoryLimit();

        $period = StockLogView::normalizePeriod($request->input('period'));
        $selectedDate = StockLogView::parseSelectedDate($request->input('date'));
        [$rangeStart, $rangeEnd] = StockLogView::resolveRange($period, $selectedDate);
        $typeFilter = $request->input('type');
        $branchId = BranchScope::ownerBranchId((int) $request->input('branch_id'));

        $logs = $this->buildStockLogsQuery($rangeStart, $rangeEnd, $typeFilter, $branchId)
            ->get()
            ->map(fn (StockLog $log) => StockLogView::decorate($log));

        $summary = $this->summary($rangeStart, $rangeEnd, $typeFilter, $branchId);
        $dateDisplay = StockLogView::dateDisplay($period, $selectedDate, $rangeStart, $rangeEnd);
        $typeLabel = StockLogTypeMap::tabLabel($typeFilter);
        $dateSuffix = $rangeStart->isSameDay($rangeEnd)
            ? $rangeStart->format('dMY')
            : $rangeStart->format('dM') . '-' . $rangeEnd->format('dMY');
        $fileName = 'Riwayat_Stok_' . $dateSuffix;

        $periodLabels = [
            'daily' => 'HARIAN',
            'weekly' => 'MINGGUAN',
            'monthly' => 'BULANAN',
        ];
        $periodLabel = $periodLabels[$period] ?? strtoupper($period);

        $branchName = 'Semua Cabang';
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) {
                $branchName = $branch->name;
            }
        }

        $viewData = [
            'logs' => $logs,
            'summary' => $summary,
            'periode' => $dateDisplay,
            'periodLabel' => $periodLabel,
            'typeLabel' => $typeLabel,
            'branchName' => $branchName,
            'logoDataUri' => ReportBrand::logoDataUri(),
            'isExcel' => $format === 'excel',
        ];

        return $this->exportByFormat(
            $format,
            'exports.stock_logs_professional',
            $viewData,
            $fileName,
            fn () => \Maatwebsite\Excel\Facades\Excel::download(
                new StockLogsReportExport($logs, $summary, $dateDisplay, $periodLabel, $typeLabel, ReportBrand::logoPath()),
                $fileName . '.xlsx'
            )
        );
    }

    private function summary($rangeStart, $rangeEnd, ?string $typeFilter, ?int $branchId = null): array
    {
        $summaryQuery = StockLog::query()->whereBetween('created_at', [$rangeStart, $rangeEnd]);
        BranchScope::apply($summaryQuery, $branchId, 'branch_id');
        $this->applyStockLogTypeFilter($summaryQuery, $typeFilter);

        $row = $summaryQuery
            ->selectRaw(
                'COUNT(*) as total,
                 ' . StockLogTypeMap::restockCaseSql() . ',
                 ' . StockLogTypeMap::usageCaseSql() . ',
                 ' . StockLogTypeMap::returnCaseSql() . ',
                 SUM(CASE WHEN type = ? THEN 1 ELSE 0 END) as adjustment',
                ['adjustment']
            )
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'restock' => (int) ($row->restock ?? 0),
            'usage' => (int) ($row->usage ?? 0),
            'return' => (int) ($row->stock_return ?? 0),
            'adjustment' => (int) ($row->adjustment ?? 0),
        ];
    }

    private function applyStockLogTypeFilter($query, ?string $typeFilter): void
    {
        if (! in_array($typeFilter, StockLogTypeMap::allowedTabs(), true)) {
            return;
        }

        $query->whereIn('type', StockLogTypeMap::tabTypes($typeFilter));
    }

    private function buildStockLogsQuery($rangeStart, $rangeEnd, ?string $typeFilter, ?int $branchId = null)
    {
        $query = StockLog::with([
                'ingredient:id,name,display_unit,base_unit,pack_size',
                'referenceTransaction:id,transaction_code',
            ])
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->latest();

        BranchScope::apply($query, $branchId, 'branch_id');
        $this->applyStockLogTypeFilter($query, $typeFilter);

        return $query;
    }

    private function raiseMemoryLimit(): void
    {
        $currentLimit = ini_get('memory_limit');

        if ($currentLimit === false || $currentLimit === '-1') {
            return;
        }

        if ($this->memoryLimitToBytes($currentLimit) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
    }

    private function memoryLimitToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
