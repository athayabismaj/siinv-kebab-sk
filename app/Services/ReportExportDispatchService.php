<?php

namespace App\Services;

use App\Jobs\GenerateReportExportJob;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\System\OperationWindowService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ReportExportDispatchService
{
    public function __construct(
        private readonly OperationWindowService $operationWindow
    ) {
    }

    public function dispatch(User $user, string $scope, string $type, array $filters): ReportExport
    {
        try {
            $this->assertInfrastructureReady();

            $shouldDefer = (bool) config('operations.defer_heavy_exports_during_ops', true)
                && $this->operationWindow->isOperationalNow();

            $deferSeconds = max(0, (int) config('operations.defer_seconds_during_ops', 5));
            $scheduledFor = $shouldDefer
                ? now((string) config('operations.timezone', config('app.timezone', 'Asia/Jakarta')))
                    ->addSeconds($deferSeconds)
                : null;

            $export = ReportExport::query()->create([
                'requested_by' => $user->id,
                'scope' => $scope,
                'type' => $type,
                'filters' => $filters,
                'status' => 'queued',
                'scheduled_for' => $scheduledFor,
            ]);

            $job = GenerateReportExportJob::dispatch($export->id);
            if ($scheduledFor) {
                $job->delay($scheduledFor);
            }

            return $export;
        } catch (Throwable $e) {
            Log::error('Failed to dispatch export job', [
                'user_id' => $user->id,
                'scope' => $scope,
                'type' => $type,
                'message' => $e->getMessage(),
            ]);

            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException('Infrastruktur ekspor belum siap. Jalankan migrasi export dan aktifkan worker queue.');
        }
    }

    private function assertInfrastructureReady(): void
    {
        if (! Schema::hasTable('report_exports')) {
            throw new RuntimeException('Tabel export belum tersedia. Jalankan migrasi export terlebih dahulu.');
        }

        if ((string) config('queue.default') === 'database' && ! Schema::hasTable('jobs')) {
            throw new RuntimeException('Tabel queue belum tersedia. Jalankan migrasi queue terlebih dahulu.');
        }
    }
}
