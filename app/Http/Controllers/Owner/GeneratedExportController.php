<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTransactionExport;
use App\Jobs\GenerateStockLogExport;
use App\Jobs\GenerateUsageReportExport;
use App\Jobs\GenerateDailyStockReportExport;
use App\Jobs\GenerateExpenseExport;
use App\Jobs\GenerateSalesReportExport;
use App\Models\GeneratedExport;
use App\Services\Exports\GeneratedExportLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GeneratedExportController extends Controller
{
    public function show(GeneratedExport $generatedExport)
    {
        $this->authorize('view', $generatedExport);

        return view('owner.exports.show', compact('generatedExport'));
    }

    public function download(GeneratedExport $generatedExport): StreamedResponse
    {
        $this->authorize('view', $generatedExport);

        abort_unless(
            $generatedExport->status === GeneratedExport::STATUS_COMPLETED
                && ! $generatedExport->isExpired()
                && $generatedExport->file_path
                && Storage::disk($generatedExport->file_disk ?: 'local')->exists($generatedExport->file_path),
            404
        );

        return Storage::disk($generatedExport->file_disk ?: 'local')->download(
            $generatedExport->file_path,
            $generatedExport->original_filename
        );
    }

    public function retry(Request $request, GeneratedExport $generatedExport, GeneratedExportLifecycle $lifecycle): RedirectResponse
    {
        $this->authorize('view', $generatedExport);

        abort_unless(
            in_array($generatedExport->type, ['transaction_history', 'stock_log', 'usage_report', 'daily_stock_report', 'expense_report', 'sales_report'], true)
                && $generatedExport->format === 'excel'
                && $generatedExport->status === GeneratedExport::STATUS_FAILED,
            404
        );

        abort_unless($lifecycle->retry($generatedExport), 404);
        $generatedExport->refresh();

        $dispatch = match ($generatedExport->type) {
            'transaction_history' => GenerateTransactionExport::dispatch($generatedExport->id),
            'stock_log' => GenerateStockLogExport::dispatch($generatedExport->id),
            'usage_report' => GenerateUsageReportExport::dispatch($generatedExport->id),
            'daily_stock_report' => GenerateDailyStockReportExport::dispatch($generatedExport->id),
            'expense_report' => GenerateExpenseExport::dispatch($generatedExport->id),
            'sales_report' => GenerateSalesReportExport::dispatch($generatedExport->id),
        };
        $dispatch->onConnection('database');

        $routePrefix = $request->routeIs('admin.*') ? 'admin' : 'owner';

        return redirect()->route($routePrefix . '.generated-exports.show', $generatedExport)
            ->with('success', 'Ekspor kembali dimasukkan ke antrean.');
    }
}
