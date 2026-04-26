<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyStockSession;
use App\Services\ReportExportDispatchService;
use App\Support\AdminCache;
use App\Support\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyStockReportController extends Controller
{
    public function __construct(
        private readonly ReportExportDispatchService $exportDispatch
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewReport', DailyStockSession::class);

        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);

        $runtimeError = null;

        try {
            // Sub-query correlated untuk menghitung estimasi nilai per sesi,
            // dengan konversi satuan yang benar — hanya 1 query, bukan N+1.
            $valueSubquery = \DB::table('daily_stock_items as dsi')
                ->join('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
                ->whereColumn('dsi.daily_stock_session_id', 'daily_stock_sessions.id')
                ->selectRaw('COALESCE(SUM(' . $this->buildValueExpression('dsi') . '), 0)');

            $query = DailyStockSession::query()
                ->with('cashier:id,name')
                ->withSum('items as total_opening', 'opening_qty')
                ->withSum('items as total_remaining', 'remaining_qty')
                ->withSum('items as total_used', 'used_qty')
                ->withCount('items')
                ->addSelect(['total_value' => $valueSubquery])
                ->whereBetween('session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->orderByDesc('session_date')
                ->orderByDesc('id');

            $sessions = (clone $query)
                ->paginate(10)
                ->withQueryString();

            $summary = $this->summary($dateFrom, $dateTo);

            $sessions->setCollection(
                $sessions->getCollection()->map(function (DailyStockSession $session) {
                    $session->total_opening   = (float) ($session->total_opening ?? 0);
                    $session->total_remaining = (float) ($session->total_remaining ?? 0);
                    $session->total_used      = (float) ($session->total_used ?? 0);
                    $session->total_value     = (float) ($session->total_value ?? 0);

                    return $session;
                })
            );
        } catch (Throwable $e) {
            Log::error('Daily stock report failed to load', [
                'message' => $e->getMessage(),
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'type' => $type,
            ]);

            $runtimeError = 'Laporan stok harian gagal dimuat sementara. Coba lagi beberapa saat.';
            $sessions = new LengthAwarePaginator(
                new Collection(),
                0,
                10,
                LengthAwarePaginator::resolveCurrentPage(),
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
            );
            $summary = [
                'sessions_count' => 0,
                'items_count' => 0,
                'total_opening' => 0,
                'total_remaining' => 0,
                'total_used' => 0,
                'total_value' => 0,
            ];
        }

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            ReportPeriod::buildNavigator($type, $dateFrom);

        return view('admin.reports.daily_stock.index', [
            'sessions' => $sessions,
            'summary' => $summary,
            'type' => $type,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'prevFrom' => $prevFrom,
            'prevTo' => $prevTo,
            'nextFrom' => $nextFrom,
            'nextTo' => $nextTo,
            'isFuture' => $isFuture,
            'inputValue' => $inputValue,
            'inputType' => $inputType,
            'runtimeError' => $runtimeError,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('viewReport', DailyStockSession::class);

        try {
            $export = $this->exportDispatch->dispatch(
                $request->user(),
                'admin',
                'admin.daily_stock',
                $request->query()
            );

            $message = 'Export laporan stok harian masuk antrian. ID: #' . $export->id;
            if ($export->scheduled_for) {
                $message .= ' Diproses pada ' . Carbon::parse($export->scheduled_for)->format('d/m/Y H:i:s') . '.';
            }

            return redirect()
                ->route('admin.exports.index')
                ->with('success', $message);
        } catch (\Throwable) {
            return redirect()
                ->route('admin.exports.index')
                ->with('error', 'Export gagal diproses. Pastikan migrasi dan worker queue sudah aktif.');
        }
    }

    private function summary(Carbon $dateFrom, Carbon $dateTo): array
    {
        $cacheKey = AdminCache::key(
            'daily_stock',
            'summary:' . md5($dateFrom->toDateString() . '|' . $dateTo->toDateString())
        );

        return Cache::remember($cacheKey, now()->addSeconds(120), function () use ($dateFrom, $dateTo) {
            $sessionsCount = DailyStockSession::query()
                ->whereBetween('session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->count();

            $aggregate = DailyStockSession::query()
                ->leftJoin('daily_stock_items as dsi', 'dsi.daily_stock_session_id', '=', 'daily_stock_sessions.id')
                ->whereBetween('daily_stock_sessions.session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->selectRaw(
                    'COALESCE(COUNT(dsi.id), 0) as items_count,
                     COALESCE(SUM(dsi.opening_qty), 0) as total_opening,
                     COALESCE(SUM(dsi.remaining_qty), 0) as total_remaining,
                     COALESCE(SUM(dsi.used_qty), 0) as total_used'
                )
                ->first();

            // Estimasi nilai total dengan konversi satuan per bahan
            $valueAggregate = DailyStockSession::query()
                ->leftJoin('daily_stock_items as dsi', 'dsi.daily_stock_session_id', '=', 'daily_stock_sessions.id')
                ->leftJoin('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
                ->whereBetween('daily_stock_sessions.session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->selectRaw('COALESCE(SUM(' . $this->buildValueExpression('dsi') . '), 0) as total_value')
                ->value('total_value');

            return [
                'sessions_count' => (int) $sessionsCount,
                'items_count'    => (int) ($aggregate->items_count ?? 0),
                'total_opening'  => (float) ($aggregate->total_opening ?? 0),
                'total_remaining'=> (float) ($aggregate->total_remaining ?? 0),
                'total_used'     => (float) ($aggregate->total_used ?? 0),
                'total_value'    => (float) ($valueAggregate ?? 0),
            ];
        });
    }
    /**
     * Bangun SQL expression untuk menghitung nilai terpakai dengan konversi satuan.
     *
     * selling_price selalu diartikan sebagai harga per satuan TAMPIL (display_unit):
     *   - kg  → qty disimpan dalam gram  → qty / 1000 * selling_price
     *   - l   → qty disimpan dalam ml    → qty / 1000 * selling_price
     *   - pcs → qty disimpan dalam pcs   → qty / pack_size * selling_price
     *   - g   → qty disimpan dalam gram  → qty * selling_price
     *   - ml  → qty disimpan dalam ml    → qty * selling_price
     */
    private function buildValueExpression(string $itemAlias = 'dsi'): string
    {
        $qty       = "{$itemAlias}.used_qty";
        $price     = 'ingredients.selling_price';
        $packSize  = 'GREATEST(COALESCE(ingredients.pack_size, 1), 1)';

        return "
            CASE ingredients.display_unit
                WHEN 'kg'  THEN ({$qty} / 1000.0) * {$price}
                WHEN 'l'   THEN ({$qty} / 1000.0) * {$price}
                WHEN 'pcs' THEN ({$qty} / {$packSize}) * {$price}
                ELSE            {$qty} * {$price}
            END
        ";
    }
}
