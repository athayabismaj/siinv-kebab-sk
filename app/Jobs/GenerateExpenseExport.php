<?php

namespace App\Jobs;

use App\Exports\QueuedExpenseReportExport;
use App\Models\Branch;
use App\Models\GeneratedExport;
use App\Services\Exports\ExpenseExportQuery;
use App\Services\Exports\GeneratedExportLifecycle;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class GenerateExpenseExport implements ShouldQueue
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

    public function handle(ExpenseExportQuery $queryService, ?GeneratedExportLifecycle $lifecycle = null): void
    {
        $lifecycle ??= app(GeneratedExportLifecycle::class);
        $export = GeneratedExport::query()->find($this->generatedExportId);
        if ($export === null || $export->type !== 'expense_report' || $export->format !== 'excel' || ! $lifecycle->claim($export->id, $this->attempts())) {
            return;
        }

        $export->refresh();

        $disk = 'local';
        $path = null;

        try {
            $filters = $export->filters ?? [];
            $start = Carbon::parse($filters['date_from'])->startOfDay();
            $end = Carbon::parse($filters['date_to'])->endOfDay();
            $query = $queryService->build($start, $end, $export->branch_id, (string) ($filters['search'] ?? ''));
            $summary = $queryService->summary($query);
            $period = $start->isSameDay($end)
                ? $start->translatedFormat('d F Y')
                : $start->translatedFormat('d F Y') . ' s/d ' . $end->translatedFormat('d F Y');
            $periodType = match ($filters['type'] ?? 'daily') {
                'weekly' => 'MINGGUAN',
                'monthly' => 'BULANAN',
                'custom' => 'KUSTOM',
                default => 'HARIAN',
            };
            $branchName = $export->branch_id
                ? (Branch::query()->whereKey($export->branch_id)->value('name') ?: 'Cabang tidak ditemukan')
                : 'Semua Cabang';

            $path = 'exports/' . $export->requested_by . '/' . $export->id . '/' . $export->original_filename;
            Storage::disk($disk)->delete($path);
            Excel::store(new QueuedExpenseReportExport($query, $period, $periodType, $branchName, $summary), $path, $disk);

            if (! Storage::disk($disk)->exists($path)) {
                throw new \RuntimeException('File ekspor tidak berhasil disimpan.');
            }

            if (! $lifecycle->complete($export->id, $disk, $path)) {
                Storage::disk($disk)->delete($path);
            }
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk($disk)->delete($path);
            }

            $lifecycle->fail($export->id);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        app(GeneratedExportLifecycle::class)->fail($this->generatedExportId);
    }
}
