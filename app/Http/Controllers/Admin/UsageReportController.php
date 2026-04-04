<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageReportController extends Controller
{
    public function index(Request $request)
    {
        $type = $this->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, $type);

        $baseQuery = StockLog::query()
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->where('stock_logs.type', 'out')
            ->whereDate('stock_logs.created_at', '>=', $dateFrom->toDateString())
            ->whereDate('stock_logs.created_at', '<=', $dateTo->toDateString())
            ->selectRaw(
                'stock_logs.ingredient_id,
                ingredients.name as ingredient_name,
                ingredients.base_unit,
                ingredients.display_unit,
                ingredients.pack_size,
                SUM(ABS(stock_logs.quantity)) as total_quantity,
                COUNT(*) as usage_count,
                MAX(stock_logs.created_at) as last_used_at'
            )
            ->groupBy(
                'stock_logs.ingredient_id',
                'ingredients.name',
                'ingredients.base_unit',
                'ingredients.display_unit',
                'ingredients.pack_size'
            );

        $usageItems = (clone $baseQuery)
            ->orderByDesc(DB::raw('SUM(ABS(stock_logs.quantity))'))
            ->paginate(10)
            ->withQueryString();

        $usageItems->setCollection(
            $usageItems->getCollection()->map(function ($item) {
                $parts = $this->formatUsageQuantityParts(
                    (float) $item->total_quantity,
                    (string) ($item->base_unit ?? ''),
                    (string) ($item->display_unit ?? ''),
                    (int) ($item->pack_size ?? 1)
                );

                $lastUsedAt = Carbon::parse($item->last_used_at);

                $item->quantity_label = $parts['quantity'];
                $item->pack_label = $parts['pack'];
                $item->last_used_date = $lastUsedAt->translatedFormat('d M Y');
                $item->last_used_time = $lastUsedAt->format('H:i');
                $item->last_used_mobile = $lastUsedAt->translatedFormat('d M, H:i');

                return $item;
            })
        );

        $totalsBase = (clone $baseQuery)->get();

        $summary = [
            'ingredients_count' => $totalsBase->count(),
            'logs_count' => (int) $totalsBase->sum('usage_count'),
            'total_base_quantity' => (float) $totalsBase->sum('total_quantity'),
        ];

        $todayDate = now()->startOfDay();

        if ($type === 'monthly') {
            $prevFrom = $dateFrom->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subMonth()->endOfMonth()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addMonth()->startOfMonth()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addMonth()->endOfMonth()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addMonth()->startOfMonth()->isAfter($todayDate);
            $inputValue = $dateFrom->format('Y-m');
            $inputType = 'month';
        } elseif ($type === 'weekly') {
            $prevFrom = $dateFrom->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->isAfter($todayDate);
            $inputValue = $dateFrom->format('Y-m-d');
            $inputType = 'date';
        } else {
            $prevFrom = $dateFrom->copy()->subDay()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subDay()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addDay()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addDay()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addDay()->isAfter($todayDate);
            $inputValue = $dateFrom->format('Y-m-d');
            $inputType = 'date';
        }

        $today = now()->toDateString();
        $week  = now()->subDays(6)->toDateString();
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
            'today',
            'week',
            'month'
        ));
    }

    public function export(Request $request)
    {
        $type = $this->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, $type);

        $query = StockLog::query()
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->where('stock_logs.type', 'out')
            ->whereDate('stock_logs.created_at', '>=', $dateFrom->toDateString())
            ->whereDate('stock_logs.created_at', '<=', $dateTo->toDateString())
            ->selectRaw(
                'ingredients.name as ingredient_name,
                SUM(ABS(stock_logs.quantity)) as total_quantity,
                ingredients.base_unit,
                ingredients.display_unit,
                ingredients.pack_size,
                COUNT(*) as usage_count,
                MAX(stock_logs.created_at) as last_used_at'
            )
            ->groupBy(
                'ingredients.name',
                'ingredients.base_unit',
                'ingredients.display_unit',
                'ingredients.pack_size'
            );

        $rows = $query->orderByDesc('total_quantity')->get();

        $filename = 'laporan-pemakaian-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return response()->streamDownload(function () use ($rows, $dateFrom, $dateTo) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['Laporan Pemakaian Bahan']);
            fputcsv($output, ['Periode', $dateFrom->toDateString() . ' s/d ' . $dateTo->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, [
                'Bahan',
                'Total Pemakaian',
                'Satuan',
                'Frekuensi',
                'Terakhir Digunakan',
            ]);

            foreach ($rows as $item) {
                $quantityLabel = $this->formatUsageQuantity(
                    (float) $item->total_quantity,
                    (string) ($item->base_unit ?? ''),
                    (string) ($item->display_unit ?? ''),
                    (int) ($item->pack_size ?? 1)
                );

                fputcsv($output, [
                    $item->ingredient_name,
                    $quantityLabel,
                    strtolower((string) ($item->display_unit ?? $item->base_unit ?? '')),
                    $item->usage_count,
                    $item->last_used_at,
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveDateRange(Request $request, string $type): array
    {
        $today = now()->startOfDay();
        if (! $request->filled('date_from') || ! $request->filled('date_to')) {
            if ($type === 'monthly') {
                $from = $today->copy()->startOfMonth();
                $to = $today->copy()->endOfMonth();
            } elseif ($type === 'weekly') {
                $from = $today->copy()->startOfWeek(Carbon::MONDAY);
                $to = $today->copy()->endOfWeek(Carbon::SUNDAY);
            } else {
                $from = $today->copy();
                $to = $today->copy();
            }

            if ($to->greaterThan($today)) {
                $to = $today->copy();
            }

            return [$from, $to];
        }

        $from = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : $today;

        $to = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->startOfDay()
            : $today;

        if ($type === 'weekly') {
            $from = $from->copy()->startOfWeek(Carbon::MONDAY);
            $to = $from->copy()->endOfWeek(Carbon::SUNDAY);
        } elseif ($type === 'monthly') {
            $from = $from->copy()->startOfMonth();
            $to = $from->copy()->endOfMonth();
        }

        if ($from->greaterThan($today)) $from = $today;
        if ($to->greaterThan($today))   $to   = $today;
        if ($from->greaterThan($to)) [$from, $to] = [$to, $from];

        return [$from, $to];
    }

    private function resolveType(string $type): string
    {
        return in_array($type, ['daily', 'weekly', 'monthly'], true)
            ? $type
            : 'daily';
    }

    private function formatUsageQuantity(float $totalQuantity, string $baseUnit, string $displayUnit, int $packSize): string
    {
        $parts = $this->formatUsageQuantityParts($totalQuantity, $baseUnit, $displayUnit, $packSize);

        return $parts['full'];
    }

    private function formatUsageQuantityParts(float $totalQuantity, string $baseUnit, string $displayUnit, int $packSize): array
    {
        $baseUnit = strtolower(trim($baseUnit));
        $displayUnit = strtolower(trim($displayUnit));

        if ($displayUnit === 'pcs') {
            $pcs = rtrim(rtrim(number_format($totalQuantity, 2, '.', ''), '0'), '.');
            if ($pcs === '') {
                $pcs = '0';
            }

            $packLabel = '';
            $packSize = max(1, $packSize);
            if ($packSize > 1) {
                $pack = $totalQuantity / $packSize;
                $packText = rtrim(rtrim(number_format($pack, 2, '.', ''), '0'), '.');
                if ($packText === '') {
                    $packText = '0';
                }
                $packLabel = $packText . ' pack';
            }

            return [
                'quantity' => $pcs . ' pcs',
                'pack' => $packLabel,
                'full' => $packLabel !== '' ? ($pcs . ' pcs (' . $packLabel . ')') : ($pcs . ' pcs'),
            ];
        }

        $converted = $totalQuantity;
        $unitLabel = $baseUnit;

        if (in_array($baseUnit, ['g', 'gr', 'gram'], true)) {
            if ($totalQuantity >= 1000) {
                $converted = $totalQuantity / 1000;
                $unitLabel = 'kg';
            } else {
                $unitLabel = 'g';
            }
        } elseif (in_array($baseUnit, ['ml', 'milliliter'], true)) {
            if ($totalQuantity >= 1000) {
                $converted = $totalQuantity / 1000;
                $unitLabel = 'l';
            } else {
                $unitLabel = 'ml';
            }
        }

        $value = rtrim(rtrim(number_format($converted, 2, '.', ''), '0'), '.');
        if ($value === '') {
            $value = '0';
        }

        return [
            'quantity' => $value . ' ' . $unitLabel,
            'pack' => '',
            'full' => $value . ' ' . $unitLabel,
        ];
    }
}
