<?php

namespace App\Services\Exports;

use App\Models\GeneratedExport;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeneratedExportLifecycle
{
    public function claim(int $exportId, int $attempt): bool
    {
        $claimableStatuses = [GeneratedExport::STATUS_PENDING];

        // Queue retries may resume a failed first attempt; a separate first-attempt job may not.
        if ($attempt > 1) {
            $claimableStatuses[] = GeneratedExport::STATUS_FAILED;
        }

        return GeneratedExport::query()
            ->whereKey($exportId)
            ->whereIn('status', $claimableStatuses)
            ->update([
                'status' => GeneratedExport::STATUS_PROCESSING,
                'error_message' => null,
                'started_at' => now(),
                'completed_at' => null,
                'updated_at' => now(),
            ]) === 1;
    }

    public function complete(int $exportId, string $disk, string $path): bool
    {
        $export = GeneratedExport::query()->find($exportId);
        $completed = GeneratedExport::query()
            ->whereKey($exportId)
            ->where('status', GeneratedExport::STATUS_PROCESSING)
            ->update([
                'status' => GeneratedExport::STATUS_COMPLETED,
                'file_disk' => $disk,
                'file_path' => $path,
                'error_message' => null,
                'completed_at' => now(),
                'updated_at' => now(),
            ]) === 1;

        if ($completed && $export !== null) {
            Log::info('generated-export-completed', $this->logContext($export, 'completed'));
        }

        return $completed;
    }

    public function fail(int $exportId): void
    {
        $export = GeneratedExport::query()->find($exportId);

        if ($export === null || $export->status === GeneratedExport::STATUS_COMPLETED) {
            return;
        }

        $path = $export->file_path ?: $this->expectedPath($export);
        if ($path !== null) {
            Storage::disk($export->file_disk ?: 'local')->delete($path);
        }

        $failed = GeneratedExport::query()
            ->whereKey($exportId)
            ->where('status', '!=', GeneratedExport::STATUS_COMPLETED)
            ->update([
                'status' => GeneratedExport::STATUS_FAILED,
                'file_disk' => null,
                'file_path' => null,
                'completed_at' => null,
                'error_message' => 'Proses ekspor tidak dapat diselesaikan. Silakan coba lagi.',
                'updated_at' => now(),
            ]) === 1;

        if ($failed) {
            Log::warning('generated-export-failed', $this->logContext($export, 'failed'));
        }
    }

    public function retry(GeneratedExport $export): bool
    {
        return GeneratedExport::query()
            ->whereKey($export->id)
            ->where('status', GeneratedExport::STATUS_FAILED)
            ->update([
                'status' => GeneratedExport::STATUS_PENDING,
                'error_message' => null,
                'file_disk' => null,
                'file_path' => null,
                'started_at' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]) === 1;
    }

    public function failStaleProcessing(int $exportId, CarbonInterface $cutoff): bool
    {
        $export = GeneratedExport::query()
            ->whereKey($exportId)
            ->where('status', GeneratedExport::STATUS_PROCESSING)
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $cutoff)
            ->first();

        if ($export === null) {
            return false;
        }

        $updated = GeneratedExport::query()
            ->whereKey($exportId)
            ->where('status', GeneratedExport::STATUS_PROCESSING)
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $cutoff)
            ->update([
                'status' => GeneratedExport::STATUS_FAILED,
                'file_disk' => null,
                'file_path' => null,
                'completed_at' => null,
                'error_message' => 'Proses ekspor berhenti terlalu lama dan perlu dicoba ulang.',
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            return false;
        }

        $path = $export->file_path ?: $this->expectedPath($export);
        if ($path !== null) {
            Storage::disk($export->file_disk ?: 'local')->delete($path);
        }

        Log::warning('generated-export-stale', $this->logContext($export, 'failed'));

        return true;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function logContext(GeneratedExport $export, string $result): array
    {
        return [
            'operation' => 'generated-export',
            'generated_export_id' => $export->id,
            'export_type' => $export->type,
            'branch_id' => $export->branch_id,
            'user_id' => $export->requested_by,
            'queue' => (string) config('exports.queue_name'),
            'result' => $result,
        ];
    }

    private function expectedPath(GeneratedExport $export): ?string
    {
        $filename = trim((string) $export->original_filename);

        if ($filename === '') {
            return null;
        }

        return 'exports/' . $export->requested_by . '/' . $export->id . '/' . $filename;
    }
}
