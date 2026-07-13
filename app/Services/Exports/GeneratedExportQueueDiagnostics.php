<?php

namespace App\Services\Exports;

use App\Models\GeneratedExport;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GeneratedExportQueueDiagnostics
{
    public function staleCandidates(?int $thresholdSeconds = null): Collection
    {
        $cutoff = $this->staleCutoff($thresholdSeconds);

        return GeneratedExport::query()
            ->where('status', GeneratedExport::STATUS_PROCESSING)
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $cutoff)
            ->orderBy('started_at')
            ->orderBy('id')
            ->get()
            ->map(fn (GeneratedExport $export) => [
                'export' => $export,
                'queue_state' => $this->queueStateFor($export->id),
            ]);
    }

    public function staleCutoff(?int $thresholdSeconds = null): CarbonInterface
    {
        return now()->subSeconds($this->staleThreshold($thresholdSeconds));
    }

    public function staleThreshold(?int $thresholdSeconds = null): int
    {
        return max(1, $thresholdSeconds ?? (int) config('exports.stale_after_seconds', 300));
    }

    /**
     * Return queue health metadata without exposing serialized job payloads.
     *
     * @return array<string, int|bool|string|null>
     */
    public function report(
        bool $writeStorageProbe = true,
        ?int $staleThresholdSeconds = null,
        ?int $fileCheckLimit = null,
    ): array
    {
        $queueTableAvailable = $this->queueTableAvailable();
        $failedJobsTableAvailable = $this->failedJobsTableAvailable();
        $generatedExportsTableAvailable = $this->generatedExportsTableAvailable();
        $pendingJobs = $queueTableAvailable ? $this->pendingJobsCount() : null;
        $oldestPendingSeconds = $queueTableAvailable ? $this->oldestPendingSeconds() : null;
        $staleProcessing = $generatedExportsTableAvailable
            ? $this->staleProcessingCount($staleThresholdSeconds)
            : null;
        $storageWritable = $this->storageWritable($writeStorageProbe);

        $report = [
            'queue_connection' => (string) config('exports.queue_connection'),
            'queue_name' => (string) config('exports.queue_name'),
            'queue_table_available' => $queueTableAvailable,
            'failed_jobs_table_available' => $failedJobsTableAvailable,
            'pending_jobs' => $pendingJobs,
            'oldest_pending_seconds' => $oldestPendingSeconds,
            'failed_jobs' => $failedJobsTableAvailable ? $this->failedJobsCount() : null,
            'generated_exports_table_available' => $generatedExportsTableAvailable,
            'generated_exports_pending' => $generatedExportsTableAvailable ? GeneratedExport::query()
                ->where('status', GeneratedExport::STATUS_PENDING)
                ->count() : null,
            'generated_exports_processing' => $generatedExportsTableAvailable ? GeneratedExport::query()
                ->where('status', GeneratedExport::STATUS_PROCESSING)
                ->count() : null,
            'generated_exports_completed' => $generatedExportsTableAvailable ? GeneratedExport::query()
                ->where('status', GeneratedExport::STATUS_COMPLETED)
                ->count() : null,
            'generated_exports_failed' => $generatedExportsTableAvailable ? GeneratedExport::query()
                ->where('status', GeneratedExport::STATUS_FAILED)
                ->count() : null,
            'oldest_generated_export_pending_seconds' => $generatedExportsTableAvailable
                ? $this->oldestPendingExportSeconds()
                : null,
            'completed_exports_missing_file' => $generatedExportsTableAvailable
                ? $this->completedExportsMissingFileCount($fileCheckLimit)
                : null,
            'stale_processing' => $staleProcessing,
            'storage_writable' => $storageWritable,
        ];

        $report['health'] = $this->healthFor($report);

        return $report;
    }

    public function queueStateFor(int $generatedExportId): string
    {
        if (! $this->queueTableAvailable()) {
            return 'unverified';
        }

        $jobs = $this->queueDatabase()
            ->table($this->queueTable())
            ->where('queue', config('exports.queue_name'))
            ->get(['payload']);

        foreach ($jobs as $job) {
            $payload = json_decode((string) $job->payload, true);
            $command = (string) data_get($payload, 'data.command', '');

            if (str_contains($command, 'generatedExportId";i:' . $generatedExportId . ';')) {
                return 'queued';
            }
        }

        return 'none';
    }

    public function queueTableAvailable(): bool
    {
        return $this->queueSchema()->hasTable($this->queueTable());
    }

    public function staleProcessingCount(?int $thresholdSeconds = null): int
    {
        return GeneratedExport::query()
            ->where('status', GeneratedExport::STATUS_PROCESSING)
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $this->staleCutoff($thresholdSeconds))
            ->count();
    }

    private function pendingJobsCount(): int
    {
        return $this->queueDatabase()
            ->table($this->queueTable())
            ->where('queue', config('exports.queue_name'))
            ->count();
    }

    private function oldestPendingSeconds(): ?int
    {
        $availableAt = $this->queueDatabase()
            ->table($this->queueTable())
            ->where('queue', config('exports.queue_name'))
            ->min('available_at');

        if ($availableAt === null) {
            return null;
        }

        return max(0, now()->timestamp - (int) $availableAt);
    }

    private function failedJobsTableAvailable(): bool
    {
        return $this->failedJobsSchema()->hasTable($this->failedJobsTable());
    }

    private function failedJobsCount(): int
    {
        return $this->failedJobsDatabase()->table($this->failedJobsTable())->count();
    }

    private function generatedExportsTableAvailable(): bool
    {
        return Schema::hasTable((new GeneratedExport())->getTable());
    }

    private function oldestPendingExportSeconds(): ?int
    {
        $createdAt = GeneratedExport::query()
            ->where('status', GeneratedExport::STATUS_PENDING)
            ->min('created_at');

        if ($createdAt === null) {
            return null;
        }

        return max(0, Carbon::parse($createdAt)->diffInSeconds(now()));
    }

    private function completedExportsMissingFileCount(?int $limit): int
    {
        $limit = max(1, $limit ?? 20);

        return GeneratedExport::query()
            ->where('status', GeneratedExport::STATUS_COMPLETED)
            ->whereNotNull('file_path')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['file_disk', 'file_path'])
            ->filter(function (GeneratedExport $export): bool {
                try {
                    return ! Storage::disk($export->file_disk ?: 'local')->exists((string) $export->file_path);
                } catch (Throwable) {
                    return true;
                }
            })
            ->count();
    }

    private function storageWritable(bool $writeProbe): ?bool
    {
        if (! $writeProbe) {
            return null;
        }

        $disk = Storage::disk('local');
        $path = 'exports/.queue-diagnostics-' . Str::uuid();

        try {
            $disk->put($path, '');
            $disk->delete($path);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, int|bool|string|null> $report
     */
    private function healthFor(array $report): string
    {
        if (! $report['queue_table_available']
            || ! $report['failed_jobs_table_available']
            || ! $report['generated_exports_table_available']
            || $report['storage_writable'] === false
            || (int) ($report['completed_exports_missing_file'] ?? 0) > 0
            || (int) $report['stale_processing'] > 0) {
            return 'unhealthy';
        }

        if ((int) $report['failed_jobs'] > 0
            || ((int) $report['oldest_pending_seconds'] > $this->staleThreshold())) {
            return 'degraded';
        }

        return 'healthy';
    }

    private function queueDatabase(): \Illuminate\Database\Connection
    {
        return DB::connection(config('queue.connections.database.connection'));
    }

    private function queueSchema(): \Illuminate\Database\Schema\Builder
    {
        return Schema::connection(config('queue.connections.database.connection'));
    }

    private function failedJobsDatabase(): \Illuminate\Database\Connection
    {
        return DB::connection(config('queue.failed.database'));
    }

    private function failedJobsSchema(): \Illuminate\Database\Schema\Builder
    {
        return Schema::connection(config('queue.failed.database'));
    }

    private function queueTable(): string
    {
        return (string) config('queue.connections.database.table', 'jobs');
    }

    private function failedJobsTable(): string
    {
        return (string) config('queue.failed.table', 'failed_jobs');
    }
}
