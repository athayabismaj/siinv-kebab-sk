<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\DirectExportResponse;
use App\Http\Controllers\Controller;
use App\Models\StockLog;
use App\Support\AdminCache;
use App\Support\BranchScope;
use App\Support\ReportBrand;
use App\Support\ReportPeriod;
use App\Support\UsageQuantityFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class UsageReportController extends Controller
{
    use DirectExportResponse;

    private const ITEMS_PER_PAGE = 10;

    public function index(Request $request)
    {
        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);
        $rangeStart = $dateFrom->copy()->startOfDay();
        $rangeEnd = $dateTo->copy()->endOfDay();
        $branchId = $this->selectedBranchId($request);
        $branchOptions = request()->routeIs('owner.*') ? BranchScope::options() : collect();

        $runtimeError = null;

        try {
            $baseQuery = $this->baseUsageAggregateQuery($rangeStart, $rangeEnd, $branchId);

            $usageItems = (clone $baseQuery)
                ->orderByDesc('total_quantity')
                ->paginate(self::ITEMS_PER_PAGE)
                ->withQueryString();

            $usageItems->setCollection(
                $usageItems->getCollection()->map(function ($item) {
                    $parts = UsageQuantityFormatter::parts(
                        (float) $item->total_quantity,
                        (string) ($item->base_unit ?? ''),
                        (string) ($item->display_unit ?? ''),
                        (int) ($item->pack_size ?? 1)
                    );

                    $lastUsedAt = $item->last_used_at ? Carbon::parse($item->last_used_at) : null;

                    $item->quantity_label = $parts['quantity'];
                    $item->pack_label = $parts['pack'];
                    $item->last_used_date = $lastUsedAt ? $lastUsedAt->translatedFormat('d M Y') : '-';
                    $item->last_used_time = $lastUsedAt ? $lastUsedAt->format('H:i') : '-';
                    $item->last_used_mobile = $lastUsedAt ? $lastUsedAt->translatedFormat('d M, H:i') : '-';

                    return $item;
                })
            );

            $summary = $this->summary($type, $dateFrom->toDateString(), $dateTo->toDateString(), $rangeStart, $rangeEnd, $branchId);
        } catch (Throwable $e) {
            Log::error('Usage report failed to load', [
                'message' => $e->getMessage(),
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'type' => $type,
                'branch_id' => $branchId,
            ]);

            $runtimeError = 'Laporan pemakaian gagal dimuat sementara. Coba lagi beberapa saat.';
            $usageItems = new LengthAwarePaginator(
                new Collection(),
                0,
                self::ITEMS_PER_PAGE,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
            );
            $summary = [
                'ingredients_count' => 0,
                'logs_count' => 0,
                'by_unit' => [],
            ];
        }

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            ReportPeriod::buildNavigator($type, $dateFrom);

        $today = now()->toDateString();
        $week = now()->subDays(6)->toDateString();
        $month = now()->startOfMonth()->toDateString();

        return view('admin.reports.usage', compact(
            'usageItems',
            'summary',
            'dateFrom',
            'dateTo',
            'type',
            'prevFrom',
            'prevTo',
            'nextFrom',
            'nextTo',
            'isFuture',
            'inputValue',
            'inputType',
            'runtimeError',
            'today',
            'week',
            'month',
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
        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);
        $rangeStart = $dateFrom->copy()->startOfDay();
        $rangeEnd = $dateTo->copy()->endOfDay();
        $branchId = $this->selectedBranchId($request);

        $rows = $this->baseUsageAggregateQuery($rangeStart, $rangeEnd, $branchId)
            ->orderByDesc('total_quantity')
            ->get();

        $summary = $this->summary($type, $dateFrom->toDateString(), $dateTo->toDateString(), $rangeStart, $rangeEnd, $branchId);
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

        $branchName = 'Semua Cabang';
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            if ($branch) {
                $branchName = $branch->name;
            }
        }

        $viewData = [
            'items' => $rows,
            'periode' => $periodeLabel,
            'periodLabel' => $periodLabelText,
            'summary' => $summary,
            'branchName' => $branchName,
            'logoDataUri' => ReportBrand::logoDataUri(),
            'isExcel' => $format === 'excel',
        ];

        $dateSuffix = $dateFrom->isSameDay($dateTo)
            ? $dateFrom->format('dMY')
            : $dateFrom->format('dM') . '-' . $dateTo->format('dMY');
        $fileName = 'Pemakaian_Bahan_' . $dateSuffix;

        return $this->exportByFormat(
            $format,
            'exports.usage_professional',
            $viewData,
            $fileName,
            fn () => \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\UsageReportExport($rows, $periodeLabel, $summary, $periodLabelText, ReportBrand::logoPath()),
                $fileName . '.xlsx'
            )
        );
    }

    private function baseUsageAggregateQuery(Carbon $rangeStart, Carbon $rangeEnd, ?int $branchId = null)
    {
        return $this->baseSuccessfulUsageLogQuery($rangeStart, $rangeEnd, $branchId)
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->selectRaw(
                'stock_logs.ingredient_id,
                ingredients.name as ingredient_name,
                ingredients.base_unit,
                ingredients.display_unit,
                ingredients.pack_size,
                ingredients.stock as current_stock,
                ingredients.minimum_stock,
                SUM(ABS(stock_logs.quantity)) as total_quantity,
                COUNT(*) as usage_count,
                MAX(stock_logs.created_at) as last_used_at'
            )
            ->groupBy(
                'stock_logs.ingredient_id',
                'ingredients.name',
                'ingredients.base_unit',
                'ingredients.display_unit',
                'ingredients.pack_size',
                'ingredients.stock',
                'ingredients.minimum_stock'
            );
    }

    private function baseSuccessfulUsageLogQuery(Carbon $rangeStart, Carbon $rangeEnd, ?int $selectedBranchId = null)
    {
        $branchId = $selectedBranchId ?? BranchScope::scopedBranchIdFor(auth()->user());

        return StockLog::query()
            ->join('transactions', 'transactions.id', '=', 'stock_logs.reference_id')
            ->where('stock_logs.type', 'daily_usage')
            ->whereBetween('stock_logs.created_at', [$rangeStart, $rangeEnd])
            ->when($branchId, fn ($query, $branchId) => $query->where('transactions.branch_id', $branchId))
            ->where(function ($query) {
                $query->whereNull('transactions.status')
                    ->orWhereRaw('LOWER(transactions.status) = ?', ['success']);
            });
    }

    private function summary(string $type, string $from, string $to, Carbon $rangeStart, Carbon $rangeEnd, ?int $branchId = null): array
    {
        $summaryKey = AdminCache::key('usage', 'summary:' . md5(json_encode([
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'source' => 'successful_daily_usage_v2',
            'branch_id' => $branchId ?? BranchScope::scopedBranchIdFor(auth()->user()),
        ])));

        return Cache::remember($summaryKey, now()->addSeconds(120), function () use ($rangeStart, $rangeEnd, $branchId) {
            $summaryBaseQuery = $this->baseSuccessfulUsageLogQuery($rangeStart, $rangeEnd, $branchId);

            // Kelompokkan total pemakaian per satuan dasar (base_unit)
            $unitRows = $this->baseSuccessfulUsageLogQuery($rangeStart, $rangeEnd, $branchId)
                ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
                ->selectRaw('LOWER(COALESCE(NULLIF(ingredients.base_unit, \'\'), \'unit\')) as unit_label, SUM(ABS(stock_logs.quantity)) as total')
                ->groupBy('unit_label')
                ->orderByDesc('total')
                ->get();

            $unitTotals = ['g' => 0.0, 'ml' => 0.0, 'pcs' => 0.0];
            foreach ($unitRows as $row) {
                $unitKey = $this->normalizeSummaryUnit((string) $row->unit_label);
                $unitTotals[$unitKey] = ($unitTotals[$unitKey] ?? 0.0) + (float) $row->total;
            }

            $byUnit = collect($unitTotals)
                ->map(fn (float $total, string $unit) => [
                    'unit' => $this->summaryUnitLabel($unit),
                    'total' => round($total, 2),
                ])
                ->values()
                ->toArray();

            return [
                'ingredients_count' => (int) (clone $summaryBaseQuery)->distinct('ingredient_id')->count('ingredient_id'),
                'logs_count' => (int) (clone $summaryBaseQuery)->count(),
                'by_unit' => $byUnit,
            ];
        });
    }

    private function normalizeSummaryUnit(string $unit): string
    {
        $unit = strtolower(trim($unit));

        return match ($unit) {
            'g', 'gr', 'gram' => 'g',
            'ml', 'milliliter', 'mililiter' => 'ml',
            'pcs', 'pc', 'piece', 'pieces' => 'pcs',
            'pack', 'pak' => 'pak',
            default => $unit !== '' ? $unit : 'unit',
        };
    }

    private function summaryUnitLabel(string $unit): string
    {
        return match ($unit) {
            'g' => 'Gram',
            'ml' => 'Mililiter',
            'pcs' => 'Pcs',
            'pak' => 'Pak',
            default => ucfirst($unit),
        };
    }

    private function selectedBranchId(Request $request): ?int
    {
        return $request->routeIs('owner.*')
            ? BranchScope::requestBranchId((int) $request->input('branch_id'))
            : null;
    }
}
