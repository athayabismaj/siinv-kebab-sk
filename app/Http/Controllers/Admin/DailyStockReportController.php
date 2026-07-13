<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\DirectExportResponse;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateDailyStockReportExport;
use App\Models\DailyStockSession;
use App\Models\GeneratedExport;
use App\Services\Admin\DailyStockReportQueryService;
use App\Support\BranchScope;
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
    private const DIRECT_EXPORT_LIMIT = 100;

    public function __construct(
        private readonly DailyStockReportQueryService $reportQuery
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewReport', DailyStockSession::class);

        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);
        $branchId = BranchScope::scopedBranchIdFor($request->user());

        $runtimeError = null;

        try {
            $sessions = $this->reportQuery->paginated($dateFrom, $dateTo, $branchId, 10);
            $summary = $this->reportQuery->summary($dateFrom, $dateTo, $branchId);
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
        $branchId = BranchScope::scopedBranchIdFor($request->user());

        $query = $this->reportQuery->exportQuery($dateFrom, $dateTo, $branchId);
        $total = (clone $query)->count();
        $summary = $this->reportQuery->summary($dateFrom, $dateTo, $branchId);

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

        $dateSuffix = $dateFrom->isSameDay($dateTo)
            ? $dateFrom->format('dMY')
            : $dateFrom->format('dM') . '-' . $dateTo->format('dMY');
        $fileName = 'Stok_Harian_' . $dateSuffix;

        if ($format === 'excel' && $total > self::DIRECT_EXPORT_LIMIT) {
            $generatedExport = GeneratedExport::query()->create([
                'requested_by' => $request->user()->id, 'branch_id' => $branchId,
                'type' => 'daily_stock_report', 'format' => 'excel',
                'filters' => ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()],
                'status' => GeneratedExport::STATUS_PENDING, 'original_filename' => $fileName . '.xlsx', 'expires_at' => now()->addDays(7),
            ]);
            GenerateDailyStockReportExport::dispatch($generatedExport->id)->onConnection('database');
            return redirect()->route('admin.generated-exports.show', $generatedExport)->with('success', 'Ekspor sedang diproses. File akan tersedia setelah selesai.');
        }
        if ($total > self::DIRECT_EXPORT_LIMIT) return redirect()->back()->withErrors(['export' => 'Ekspor langsung dibatasi hingga 100 data.']);
        $rows = $query->get();
        $viewData = [
            'sessions' => $rows,
            'periode' => $periodeLabel,
            'periodLabel' => $periodLabelText,
            'summary' => $summary,
            'logoDataUri' => ReportBrand::logoDataUri(),
            'isExcel' => $format === 'excel',
        ];

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
