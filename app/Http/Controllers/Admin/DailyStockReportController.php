<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\DirectExportResponse;
use App\Http\Controllers\Controller;
use App\Models\DailyStockSession;
use App\Services\Admin\DailyStockReportQueryService;
use App\Support\ReportBrand;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyStockReportController extends Controller
{
    use DirectExportResponse;

    public function __construct(
        private readonly DailyStockReportQueryService $reportQuery
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewReport', DailyStockSession::class);

        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);

        $runtimeError = null;

        try {
            $sessions = $this->reportQuery->paginated($dateFrom, $dateTo, 10);
            $summary = $this->reportQuery->summary($dateFrom, $dateTo);
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
                'by_unit' => [],
                'total_value' => 0,
                'total_revenue' => 0,
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
        $format = (string) $request->query('format', 'excel');

        return $this->exportDirect($request, $format);
    }

    private function exportDirect(Request $request, string $format)
    {
        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);

        $rows = $this->reportQuery->rows($dateFrom, $dateTo);
        $summary = $this->reportQuery->summary($dateFrom, $dateTo);

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
            'sessions' => $rows,
            'periode' => $periodeLabel,
            'periodLabel' => $periodLabelText,
            'summary' => $summary,
            'logoDataUri' => ReportBrand::logoDataUri(),
            'isExcel' => $format === 'excel',
        ];

        $fileName = 'laporan-stok-harian-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString();

        return $this->exportByFormat(
            $format,
            'exports.daily_stock_professional',
            $viewData,
            $fileName,
            fn () => \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\DailyStockReportExport($rows, $periodeLabel, $summary, $periodLabelText, ReportBrand::logoPath()),
                $fileName . '.xlsx'
            )
        );
    }
}
