<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyStockSession;
use App\Services\ReportExportDispatchService;
use App\Support\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
            $query = DailyStockSession::query()
                ->with('cashier:id,name')
                ->withSum('items as total_opening', 'opening_qty')
                ->withSum('items as total_remaining', 'remaining_qty')
                ->withSum('items as total_used', 'used_qty')
                ->withCount('items')
                ->whereBetween('session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->orderByDesc('session_date')
                ->orderByDesc('id');

            $sessions = (clone $query)
                ->paginate(10)
                ->withQueryString();

            $summarySource = (clone $query)->get();
            $summary = [
                'sessions_count' => $summarySource->count(),
                'items_count' => (int) $summarySource->sum('items_count'),
                'total_opening' => (float) $summarySource->sum('total_opening'),
                'total_remaining' => (float) $summarySource->sum('total_remaining'),
                'total_used' => (float) $summarySource->sum('total_used'),
                // Nilai sementara: gunakan basis qty terpakai.
                'total_value' => (float) $summarySource->sum('total_used'),
            ];

            $sessions->setCollection(
                $sessions->getCollection()->map(function (DailyStockSession $session) {
                    $session->total_opening = (float) ($session->total_opening ?? 0);
                    $session->total_remaining = (float) ($session->total_remaining ?? 0);
                    $session->total_used = (float) ($session->total_used ?? 0);
                    $session->total_value = (float) ($session->total_used ?? 0);

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
}
