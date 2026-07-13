<?php

namespace App\Jobs;

use App\Exports\QueuedTransactionReportExport;
use App\Models\GeneratedExport;
use App\Services\Exports\GeneratedExportLifecycle;
use App\Services\Owner\TransactionHistoryQueryService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GenerateTransactionExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;
    public int $backoff = 60;

    public function __construct(public readonly int $generatedExportId)
    {
        $this->onConnection(config('exports.queue_connection'));
        $this->onQueue(config('exports.queue_name'));
        $this->afterCommit();
    }

    public function handle(
        TransactionHistoryQueryService $queryService,
        ?GeneratedExportLifecycle $lifecycle = null
    ): void
    {
        $lifecycle ??= app(GeneratedExportLifecycle::class);
        $generatedExport = GeneratedExport::query()->find($this->generatedExportId);

        if ($generatedExport === null
            || $generatedExport->type !== 'transaction_history'
            || $generatedExport->format !== 'excel'
            || ! $lifecycle->claim($generatedExport->id, $this->attempts())) {
            return;
        }

        $generatedExport->refresh();

        $disk = 'local';
        $path = null;

        try {
            $filters = $generatedExport->filters ?? [];
            $dateFrom = Carbon::parse((string) $filters['date_from'])->startOfDay();
            $dateTo = Carbon::parse((string) $filters['date_to'])->endOfDay();
            $queryFilters = [
                'search' => (string) ($filters['search'] ?? ''),
                'user_id' => (int) ($filters['user_id'] ?? 0),
                'payment_method_id' => (int) ($filters['payment_method_id'] ?? 0),
            ];

            if ($generatedExport->branch_id !== null) {
                $queryFilters['branch_id'] = (int) $generatedExport->branch_id;
            }

            $query = $queryService
                ->applyFilters($queryService->baseListQuery($dateFrom, $dateTo), $queryFilters)
                ->orderByDesc('transactions.created_at')
                ->orderByDesc('transactions.id');

            $filename = (string) $generatedExport->original_filename;
            $path = 'exports/' . $generatedExport->requested_by . '/' . $generatedExport->id . '/' . $filename;
            Storage::disk($disk)->delete($path);

            Excel::store(new QueuedTransactionReportExport($query), $path, $disk);

            if (! Storage::disk($disk)->exists($path)) {
                throw new \RuntimeException('File ekspor tidak berhasil disimpan.');
            }

            if (! $lifecycle->complete($generatedExport->id, $disk, $path)) {
                Storage::disk($disk)->delete($path);
            }
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk($disk)->delete($path);
            }

            $lifecycle->fail($generatedExport->id);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        app(GeneratedExportLifecycle::class)->fail($this->generatedExportId);
    }
}
