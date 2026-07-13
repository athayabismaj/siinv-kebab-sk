<?php

namespace App\Services\System;

use App\Services\Exports\GeneratedExportQueueDiagnostics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemHealthService
{
    public function __construct(
        private readonly GeneratedExportQueueDiagnostics $exportDiagnostics,
        private readonly SchedulerLockConfiguration $schedulerLockConfiguration,
        private readonly SchedulerHeartbeat $schedulerHeartbeat,
    ) {
    }

    public function report(bool $writeStorageProbe = true): SystemHealthReport
    {
        $checks = [
            $this->check('application', fn () => $this->result('healthy', 'Application is responding.')),
            $this->checkDatabase(),
            $this->checkCache(),
            $this->checkQueueAndExports($writeStorageProbe),
            $this->checkPrivateStorage($writeStorageProbe),
            $this->checkSchedulerHeartbeat(),
            $this->checkSchedulerLock(),
            $this->checkDiskSpace(),
        ];

        return new SystemHealthReport(
            $this->overallStatus($checks),
            $checks,
            now(),
        );
    }

    private function checkDatabase(): HealthCheckResult
    {
        return $this->check('database', function (): array {
            DB::select('select 1');

            return $this->result('healthy', 'Database connection is available.');
        }, 'unhealthy', 'Database connection is unavailable.');
    }

    private function checkCache(): HealthCheckResult
    {
        return $this->check('cache', function (): array {
            $key = 'system-health-cache-probe:' . Str::uuid();
            $value = (string) Str::uuid();
            $store = Cache::store();

            try {
                if (! $store->put($key, $value, now()->addMinute()) || $store->get($key) !== $value) {
                    return $this->result('degraded', 'Application cache probe failed.');
                }
            } finally {
                $store->forget($key);
            }

            return $this->result('healthy', 'Application cache is available.');
        }, 'degraded', 'Application cache is unavailable.');
    }

    private function checkQueueAndExports(bool $writeStorageProbe): HealthCheckResult
    {
        return $this->check('queue', function () use ($writeStorageProbe): array {
            $report = $this->exportDiagnostics->report(
                $writeStorageProbe,
                max(1, (int) config('health.export_stale_processing_seconds', 300)),
                max(1, (int) config('health.export_file_check_limit', 20)),
            );

            $degraded = ! $report['queue_table_available']
                || ! $report['failed_jobs_table_available']
                || ! $report['generated_exports_table_available']
                || (int) ($report['oldest_pending_seconds'] ?? 0) > (int) config('health.queue_oldest_pending_warning_seconds', 300)
                || (int) ($report['failed_jobs'] ?? 0) >= (int) config('health.failed_jobs_warning', 1)
                || (int) $report['stale_processing'] > 0
                || (int) ($report['oldest_generated_export_pending_seconds'] ?? 0) > (int) config('health.export_pending_warning_seconds', 900)
                || (int) ($report['completed_exports_missing_file'] ?? 0) > 0;

            return $this->result(
                $degraded ? 'degraded' : 'healthy',
                $degraded ? 'Queue or generated export attention is required.' : 'Queue and generated exports are available.',
                [
                    'pending_jobs' => $report['pending_jobs'],
                    'oldest_pending_seconds' => $report['oldest_pending_seconds'],
                    'failed_jobs' => $report['failed_jobs'],
                    'stale_processing' => $report['stale_processing'],
                    'pending_exports' => $report['generated_exports_pending'],
                    'oldest_pending_export_seconds' => $report['oldest_generated_export_pending_seconds'],
                    'completed_exports_missing_file' => $report['completed_exports_missing_file'],
                ],
            );
        }, 'degraded', 'Queue or generated export checks are unavailable.');
    }

    private function checkPrivateStorage(bool $writeProbe): HealthCheckResult
    {
        return $this->check('private-storage', function () use ($writeProbe): array {
            if (! $writeProbe) {
                return $this->result('degraded', 'Private storage write probe was skipped.', ['probe' => 'skipped']);
            }

            $disk = Storage::disk('local');
            $path = 'health/.probe-' . Str::uuid();
            $value = (string) Str::uuid();
            $probeSucceeded = false;
            $cleanupSucceeded = false;

            try {
                $written = $disk->put($path, $value);
                $probeSucceeded = $written && $disk->get($path) === $value;
            } finally {
                try {
                    $cleanupSucceeded = ! $disk->exists($path) || $disk->delete($path);
                } catch (Throwable) {
                    $cleanupSucceeded = false;
                }
            }

            return $this->result(
                $probeSucceeded && $cleanupSucceeded ? 'healthy' : 'degraded',
                $probeSucceeded && $cleanupSucceeded
                    ? 'Private storage is writable.'
                    : 'Private storage probe failed.',
            );
        }, 'degraded', 'Private storage is unavailable.');
    }

    private function checkSchedulerHeartbeat(): HealthCheckResult
    {
        return $this->check('scheduler-heartbeat', function (): array {
            $latest = $this->schedulerHeartbeat->latest();
            if ($latest === null) {
                return $this->result('degraded', 'Scheduler heartbeat has not been recorded.');
            }

            $age = max(0, $latest->diffInSeconds(now()));
            $threshold = max(1, (int) config('health.scheduler_heartbeat_stale_seconds', 180));

            return $this->result(
                $age > $threshold ? 'degraded' : 'healthy',
                $age > $threshold ? 'Scheduler heartbeat is stale.' : 'Scheduler heartbeat is fresh.',
                ['age_seconds' => $age],
            );
        }, 'degraded', 'Scheduler heartbeat cannot be checked.');
    }

    private function checkSchedulerLock(): HealthCheckResult
    {
        return $this->check('scheduler-lock', function (): array {
            $readiness = $this->schedulerLockConfiguration->multiServerReadiness();
            $multiServer = (bool) config('scheduler.multi_server', false);
            $unhealthy = $multiServer && $readiness !== 'ready';

            return $this->result(
                $unhealthy ? 'unhealthy' : 'healthy',
                $unhealthy ? 'Scheduler lock configuration is not ready.' : 'Scheduler lock configuration is ready.',
                [
                    'multi_server' => $multiServer,
                    'readiness' => $readiness,
                ],
            );
        }, 'unhealthy', 'Scheduler lock configuration cannot be checked.');
    }

    private function checkDiskSpace(): HealthCheckResult
    {
        return $this->check('disk-space', function (): array {
            $root = (string) config('filesystems.disks.local.root', '');
            $free = $root !== '' && function_exists('disk_free_space') ? @disk_free_space($root) : false;
            $total = $root !== '' && function_exists('disk_total_space') ? @disk_total_space($root) : false;

            if ($free === false || $total === false || $total <= 0) {
                return $this->result('healthy', 'Disk free-space metric is unavailable on this platform.', ['supported' => false]);
            }

            $freePercent = round(((float) $free / (float) $total) * 100, 2);
            $critical = max(0, (int) config('health.disk_critical_free_percent', 5));
            $warning = max($critical, (int) config('health.disk_warning_free_percent', 10));
            $status = $freePercent <= $critical ? 'unhealthy' : ($freePercent <= $warning ? 'degraded' : 'healthy');

            return $this->result($status, 'Disk free-space check completed.', ['free_percent' => $freePercent]);
        }, 'degraded', 'Disk free-space check is unavailable.');
    }

    /**
     * @param array<string, bool|int|string|null> $metadata
     * @return array{0:string,1:string,2:array<string, bool|int|string|null>}
     */
    private function result(string $status, string $message, array $metadata = []): array
    {
        return [$status, $message, $metadata];
    }

    /**
     * @param callable(): array{0:string,1:string,2:array<string, bool|int|string|null>} $callback
     */
    private function check(string $name, callable $callback, string $failureStatus = 'degraded', string $failureMessage = 'Check failed.'): HealthCheckResult
    {
        $startedAt = microtime(true);

        try {
            [$status, $message, $metadata] = $callback();
        } catch (Throwable $exception) {
            Log::warning('system-health-check-failed', [
                'check' => $name,
                'exception_class' => $exception::class,
            ]);

            [$status, $message, $metadata] = [$failureStatus, $failureMessage, []];
        }

        return new HealthCheckResult(
            $name,
            $status,
            $message,
            $metadata,
            now(),
            (int) round((microtime(true) - $startedAt) * 1000),
        );
    }

    /**
     * @param array<int, HealthCheckResult> $checks
     */
    private function overallStatus(array $checks): string
    {
        if (collect($checks)->contains(fn (HealthCheckResult $check) => $check->status === 'unhealthy')) {
            return 'unhealthy';
        }

        if (collect($checks)->contains(fn (HealthCheckResult $check) => $check->status === 'degraded')) {
            return 'degraded';
        }

        return 'healthy';
    }
}
