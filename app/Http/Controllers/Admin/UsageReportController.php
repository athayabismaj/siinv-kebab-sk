<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockLog;
use App\Services\ReportExportDispatchService;
use App\Support\AdminCache;
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
    public function __construct(
        private readonly ReportExportDispatchService $exportDispatch
    ) {
    }

    public function index(Request $request)
    {
        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);
        $rangeStart = $dateFrom->copy()->startOfDay();
        $rangeEnd = $dateTo->copy()->endOfDay();

        $runtimeError = null;

        try {
            $baseQuery = $this->baseUsageAggregateQuery($rangeStart, $rangeEnd);

            $usageItems = (clone $baseQuery)
                ->orderByDesc('total_quantity')
                ->paginate(10)
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

            $summary = $this->summary($type, $dateFrom->toDateString(), $dateTo->toDateString(), $rangeStart, $rangeEnd);
        } catch (Throwable $e) {
            Log::error('Usage report failed to load', [
                'message' => $e->getMessage(),
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'type' => $type,
            ]);

            $runtimeError = 'Laporan pemakaian gagal dimuat sementara. Coba lagi beberapa saat.';
            $usageItems = new LengthAwarePaginator(
                new Collection(),
                0,
                10,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
            );
            $summary = [
                'ingredients_count' => 0,
                'logs_count' => 0,
                'total_base_quantity' => 0.0,
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
            'month'
        ));
    }

    public function export(Request $request)
    {
        $isOwnerScope = $request->routeIs('owner.*');

        try {
            $scope = $isOwnerScope ? 'owner' : 'admin';
            $exportType = $isOwnerScope ? 'owner.usage' : 'admin.usage';

            $export = $this->exportDispatch->dispatch(
                $request->user(),
                $scope,
                $exportType,
                $request->query()
            );

            $message = 'Export pemakaian bahan masuk antrian. ID: #' . $export->id;
            if ($export->scheduled_for) {
                $message .= ' Diproses setelah jam operasional (' . $export->scheduled_for->format('d/m/Y H:i') . ').';
            }

            return redirect()
                ->route($isOwnerScope ? 'owner.exports.index' : 'admin.exports.index')
                ->with('success', $message);
        } catch (\Throwable) {
            return redirect()
                ->route($isOwnerScope ? 'owner.exports.index' : 'admin.exports.index')
                ->with('error', 'Export gagal diproses. Pastikan migrasi dan worker queue sudah aktif.');
        }
    }

    private function baseUsageAggregateQuery(Carbon $rangeStart, Carbon $rangeEnd)
    {
        return StockLog::query()
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->whereIn('stock_logs.type', ['out', 'daily_usage'])
            ->whereBetween('stock_logs.created_at', [$rangeStart, $rangeEnd])
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
    }

    private function summary(string $type, string $from, string $to, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $summaryKey = AdminCache::key('usage', 'summary:' . md5(json_encode([
            'type' => $type,
            'from' => $from,
            'to' => $to,
        ])));

        return Cache::remember($summaryKey, now()->addSeconds(120), function () use ($rangeStart, $rangeEnd) {
            $summaryBaseQuery = StockLog::query()
                ->whereIn('type', ['out', 'daily_usage'])
                ->whereBetween('created_at', [$rangeStart, $rangeEnd]);

            return [
                'ingredients_count' => (int) (clone $summaryBaseQuery)->distinct('ingredient_id')->count('ingredient_id'),
                'logs_count' => (int) (clone $summaryBaseQuery)->count(),
                'total_base_quantity' => (float) ((clone $summaryBaseQuery)->selectRaw('COALESCE(SUM(ABS(quantity)), 0) as total')->value('total') ?? 0),
            ];
        });
    }
}


