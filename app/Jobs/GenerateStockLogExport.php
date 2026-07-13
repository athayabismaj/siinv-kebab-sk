<?php

namespace App\Jobs;

use App\Exports\QueuedStockLogExport;
use App\Models\GeneratedExport;
use App\Services\Exports\GeneratedExportLifecycle;
use App\Services\Exports\StockLogExportQuery;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GenerateStockLogExport implements ShouldQueue
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

    public function handle(StockLogExportQuery $queryService, ?GeneratedExportLifecycle $lifecycle = null): void
    {
        $lifecycle ??= app(GeneratedExportLifecycle::class);
        $export = GeneratedExport::query()->find($this->generatedExportId);
        if ($export === null || $export->type !== 'stock_log' || $export->format !== 'excel' || ! $lifecycle->claim($export->id, $this->attempts())) return;
        $export->refresh();
        $disk = 'local';
        $path = null;
        try {
            $filters = $export->filters ?? [];
            $query = $queryService->build(Carbon::parse($filters['date_from'])->startOfDay(), Carbon::parse($filters['date_to'])->endOfDay(), $filters['type'] ?? null, $export->branch_id);
            $path = 'exports/' . $export->requested_by . '/' . $export->id . '/' . $export->original_filename;
            Storage::disk($disk)->delete($path);
            Excel::store(new QueuedStockLogExport($query), $path, $disk);
            if (! Storage::disk($disk)->exists($path)) throw new \RuntimeException('File ekspor tidak berhasil disimpan.');
            if (! $lifecycle->complete($export->id, $disk, $path)) Storage::disk($disk)->delete($path);
        } catch (Throwable $exception) {
            if ($path !== null) Storage::disk($disk)->delete($path);
            $lifecycle->fail($export->id);
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void { app(GeneratedExportLifecycle::class)->fail($this->generatedExportId); }
}
