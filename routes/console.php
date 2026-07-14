<?php

use App\Services\Analytics\DailySalesSummaryService;
use App\Models\Branch;
use App\Services\System\DailyStockIntegrityAuditService;
use App\Services\System\SchedulerLockConfiguration;
use App\Services\System\SchedulerHeartbeat;
use App\Services\System\SystemHealthService;
use App\Services\Backup\BackupDiagnosticsService;
use App\Models\GeneratedExport;
use App\Services\Exports\GeneratedExportLifecycle;
use App\Services\Exports\GeneratedExportQueueDiagnostics;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ops:optimize-production', function () {
    $this->call('config:cache');
    $this->call('route:cache');
    $this->call('view:cache');
    $this->info('Production optimization cache created (config/route/view).');
})->purpose('Build production caches (config, route, view)');

Artisan::command('ops:optimize-clear', function () {
    $this->call('optimize:clear');
    $this->info('Optimization caches cleared.');
})->purpose('Clear production optimization caches');

Artisan::command('analytics:daily-summary {--date=} {--days=0}', function (DailySalesSummaryService $service) {
    $dateOption = $this->option('date');
    $days = max(0, (int) $this->option('days'));

    $baseDate = $dateOption
        ? Carbon::parse((string) $dateOption)->startOfDay()
        : now()->startOfDay();

    for ($i = $days; $i >= 0; $i--) {
        $target = $baseDate->copy()->subDays($i);
        Branch::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->each(function (Branch $branch) use ($service, $target): void {
                $result = $service->rebuildForDate($branch, $target);

                $this->info(sprintf(
                    '[%s][%s] trx=%d omzet=%.2f items=%d',
                    $target->toDateString(),
                    $branch->code,
                    $result['total_transactions'],
                    $result['total_revenue'],
                    $result['total_items_sold']
                ));
            });
    }
})->purpose('Rebuild daily pre-aggregated sales summary');

Artisan::command('ops:traffic-alert-check {--window=5}', function () {
    $window = max(1, (int) $this->option('window'));
    $keys = [];

    for ($i = 0; $i < $window; $i++) {
        $minute = now()->subMinutes($i)->format('YmdHi');
        $keys[] = "traffic:{$minute}:total";
        $keys[] = "traffic:{$minute}:slow";
        $keys[] = "traffic:{$minute}:status_429";
        $keys[] = "traffic:{$minute}:status_5xx";
    }

    $total = 0;
    $slow = 0;
    $status429 = 0;
    $status5xx = 0;

    foreach ($keys as $key) {
        $value = (int) Cache::get($key, 0);

        if (str_ends_with($key, ':total')) {
            $total += $value;
        } elseif (str_ends_with($key, ':slow')) {
            $slow += $value;
        } elseif (str_ends_with($key, ':status_429')) {
            $status429 += $value;
        } elseif (str_ends_with($key, ':status_5xx')) {
            $status5xx += $value;
        }
    }

    $thresholdTotal = (int) env('TRAFFIC_ALERT_TOTAL_REQUESTS', 1200);
    $thresholdSlow = (int) env('TRAFFIC_ALERT_SLOW_REQUESTS', 80);
    $threshold429 = (int) env('TRAFFIC_ALERT_STATUS_429', 40);
    $threshold5xx = (int) env('TRAFFIC_ALERT_STATUS_5XX', 15);

    $triggered = $total >= $thresholdTotal
        || $slow >= $thresholdSlow
        || $status429 >= $threshold429
        || $status5xx >= $threshold5xx;

    if (! $triggered) {
        $this->info("traffic-ok window={$window}m total={$total} slow={$slow} 429={$status429} 5xx={$status5xx}");

        return;
    }

    $payload = [
        'window_minutes' => $window,
        'total_requests' => $total,
        'slow_requests' => $slow,
        'status_429' => $status429,
        'status_5xx' => $status5xx,
        'threshold_total' => $thresholdTotal,
        'threshold_slow' => $thresholdSlow,
        'threshold_429' => $threshold429,
        'threshold_5xx' => $threshold5xx,
    ];

    Log::warning('traffic-anomaly-window', $payload);

    if (filter_var(env('TRAFFIC_ALERT_SLACK_ENABLED', false), FILTER_VALIDATE_BOOL)) {
        Log::channel('slack')->warning('traffic-anomaly-window', $payload);
    }

    $this->warn('traffic-anomaly-window detected');
})->purpose('Check traffic anomaly window and emit warning/slack alert');

Artisan::command('ops:daily-stock-integrity-audit {--date=} {--days=0} {--fail-on-findings=1}', function (DailyStockIntegrityAuditService $auditService) {
    $dateOption = $this->option('date');
    $days = max(0, (int) $this->option('days'));
    $failOnFindings = (string) $this->option('fail-on-findings') !== '0';

    $baseDate = $dateOption
        ? Carbon::parse((string) $dateOption)->startOfDay()
        : now()->startOfDay();

    $from = $baseDate->copy()->subDays($days);
    $to = $baseDate->copy();

    $result = $auditService->audit($from, $to);

    $this->info(sprintf(
        'daily-stock-audit scanned=%d findings=%d range=%s..%s',
        (int) $result['scanned_sessions'],
        (int) $result['findings_count'],
        $from->toDateString(),
        $to->toDateString()
    ));

    foreach (array_slice($result['findings'], 0, 50) as $finding) {
        $this->warn(json_encode($finding));
    }

    if ((int) $result['findings_count'] > 0) {
        Log::warning('daily-stock-integrity-findings', [
            'range_from' => $from->toDateString(),
            'range_to' => $to->toDateString(),
            'scanned_sessions' => $result['scanned_sessions'],
            'findings_count' => $result['findings_count'],
            'findings_preview' => array_slice($result['findings'], 0, 20),
        ]);
    }

    if ($failOnFindings && (int) $result['findings_count'] > 0) {
        $this->fail('Daily stock integrity findings detected.');
    }
})->purpose('Audit data consistency between daily stock session items and usage logs');

Artisan::command('ops:doctor-env {--strict=0}', function () {
    $strict = (string) $this->option('strict') === '1';

    $requiredExtensions = ['pdo_pgsql', 'pgsql', 'mbstring', 'json'];
    $recommendedExtensions = ['intl', 'pdo_sqlite'];

    $missingRequired = [];
    foreach ($requiredExtensions as $ext) {
        if (! extension_loaded($ext)) {
            $missingRequired[] = $ext;
        }
    }

    $missingRecommended = [];
    foreach ($recommendedExtensions as $ext) {
        if (! extension_loaded($ext)) {
            $missingRecommended[] = $ext;
        }
    }

    $this->info('Environment doctor check:');
    $this->line('- APP_ENV: ' . config('app.env'));
    $this->line('- DB_CONNECTION: ' . config('database.default'));
    $this->line('- PHP version: ' . PHP_VERSION);

    if (! empty($missingRequired)) {
        $this->error('Missing required PHP extensions: ' . implode(', ', $missingRequired));
    } else {
        $this->info('Required extensions: OK');
    }

    if (! empty($missingRecommended)) {
        $this->warn('Missing recommended PHP extensions: ' . implode(', ', $missingRecommended));
        $this->line('Note: missing "intl" can break numeric formatting in some artisan commands.');
        $this->line('Note: missing "pdo_sqlite" can break default phpunit sqlite in-memory tests.');
    } else {
        $this->info('Recommended extensions: OK');
    }

    $pgsqlBin = env('PG_DUMP_PATH');
    if ($pgsqlBin && ! file_exists($pgsqlBin)) {
        $this->warn('PG_DUMP_PATH configured but file not found: ' . $pgsqlBin);
    }

    if ($strict && (! empty($missingRequired) || ! empty($missingRecommended))) {
        return 1;
    }

    return 0;
})->purpose('Check runtime environment health for required/recommended PHP extensions');

Artisan::command('exports:cleanup', function () {
    $deleted = 0;
    $failed = 0;

    GeneratedExport::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<=', now())
        ->whereIn('status', [
            GeneratedExport::STATUS_COMPLETED,
            GeneratedExport::STATUS_FAILED,
        ])
        ->orderBy('id')
        ->eachById(function (GeneratedExport $generatedExport) use (&$deleted, &$failed): void {
            $disk = $generatedExport->file_disk ?: 'local';

            if ($generatedExport->file_path && Storage::disk($disk)->exists($generatedExport->file_path)) {
                if (! Storage::disk($disk)->delete($generatedExport->file_path)) {
                    $failed++;
                    $this->warn("Unable to delete export file {$generatedExport->id}.");

                    return;
                }
            }

            $generatedExport->delete();
            $deleted++;
        });

    $this->info("exports-cleanup deleted={$deleted} failed={$failed}");

    return $failed > 0 ? 1 : 0;
})->purpose('Remove expired private generated export files');

Artisan::command('exports:detect-stale {--threshold= : Stale threshold in seconds} {--mark-failed : Mark explicitly selected stale exports as failed} {--id=* : Generated export IDs to recover}', function (
    GeneratedExportQueueDiagnostics $diagnostics,
    GeneratedExportLifecycle $lifecycle
) {
    $thresholdOption = $this->option('threshold');
    $threshold = $thresholdOption === null || $thresholdOption === ''
        ? null
        : filter_var($thresholdOption, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($threshold === false) {
        $this->error('Threshold harus berupa bilangan bulat positif.');

        return 1;
    }

    $candidates = $diagnostics->staleCandidates($threshold);
    $this->info(sprintf(
        'stale-candidates=%d threshold-seconds=%d',
        $candidates->count(),
        $diagnostics->staleThreshold($threshold)
    ));

    foreach ($candidates as $candidate) {
        /** @var GeneratedExport $export */
        $export = $candidate['export'];
        $this->line(sprintf(
            'id=%d type=%s started_at=%s queue=%s',
            $export->id,
            $export->type,
            optional($export->started_at)->toDateTimeString() ?? '-',
            $candidate['queue_state']
        ));
    }

    if (! $this->option('mark-failed')) {
        return 0;
    }

    $ids = collect($this->option('id'))
        ->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))
        ->filter()
        ->unique()
        ->values();

    if ($ids->isEmpty()) {
        $this->error('Recovery memerlukan setidaknya satu opsi --id=<generated-export-id>.');

        return 1;
    }

    $candidatesById = $candidates->keyBy(fn (array $candidate) => $candidate['export']->id);
    $recovered = 0;
    $skipped = 0;
    $cutoff = $diagnostics->staleCutoff($threshold);

    foreach ($ids as $id) {
        $candidate = $candidatesById->get($id);

        if ($candidate === null || $candidate['queue_state'] !== 'none') {
            $skipped++;

            continue;
        }

        /** @var GeneratedExport $export */
        $export = $candidate['export'];
        if ($lifecycle->failStaleProcessing($export->id, $cutoff)) {
            $recovered++;
            Log::warning('Generated export marked failed after stale processing detection.', [
                'generated_export_id' => $export->id,
                'export_type' => $export->type,
                'user_id' => $export->requested_by,
                'branch_id' => $export->branch_id,
            ]);
        } else {
            $skipped++;
        }
    }

    $this->info("stale-recovery recovered={$recovered} skipped={$skipped}");

    return 0;
})->purpose('Report stale generated exports and explicitly recover safe candidates');

Artisan::command('exports:diagnose', function (GeneratedExportQueueDiagnostics $diagnostics) {
    $report = $diagnostics->report();

    $this->line('queue=' . $report['queue_name']);
    $this->line('connection=' . $report['queue_connection']);
    $this->line('queue-table=' . ($report['queue_table_available'] ? 'available' : 'missing'));
    $this->line('failed-jobs-table=' . ($report['failed_jobs_table_available'] ? 'available' : 'missing'));
    $this->line('pending-jobs=' . ($report['pending_jobs'] ?? 'unavailable'));
    $this->line('oldest-pending-seconds=' . ($report['oldest_pending_seconds'] ?? 'none'));
    $this->line('failed-jobs=' . ($report['failed_jobs'] ?? 'unavailable'));
    $this->line('generated-exports-pending=' . ($report['generated_exports_pending'] ?? 'unavailable'));
    $this->line('generated-exports-processing=' . ($report['generated_exports_processing'] ?? 'unavailable'));
    $this->line('generated-exports-completed=' . ($report['generated_exports_completed'] ?? 'unavailable'));
    $this->line('generated-exports-failed=' . ($report['generated_exports_failed'] ?? 'unavailable'));
    $this->line('stale-processing=' . ($report['stale_processing'] ?? 'unavailable'));
    $this->line('storage=' . match ($report['storage_writable']) {
        true => 'writable',
        false => 'unwritable',
        default => 'unverified',
    });
    $this->line('health=' . $report['health']);

    return 0;
})->purpose('Report generated export queue health without exposing job payloads');

Artisan::command('scheduler:diagnose', function (SchedulerLockConfiguration $lockConfiguration) {
    $schedule = app(\Illuminate\Console\Scheduling\Schedule::class);
    $events = collect($schedule->events());
    $criticalNames = (array) config('scheduler.critical_event_names', []);
    $criticalEvents = $events
        ->filter(fn (Event $event) => in_array($event->description, $criticalNames, true))
        ->map(fn (Event $event) => $event->description)
        ->values();
    $databaseTables = $lockConfiguration->databaseTablesAvailable();

    $this->line('timezone=' . config('app.timezone'));
    $this->line('current-time=' . now()->toDateTimeString());
    $this->line('lock-store=' . $lockConfiguration->store());
    $this->line('lock-store-known=' . ($lockConfiguration->hasKnownStore() ? 'yes' : 'no'));
    $this->line('shared-atomic-store=' . ($lockConfiguration->usesSharedAtomicStore() ? 'yes' : 'no'));
    $this->line('multi-server-requested=' . (config('scheduler.multi_server') ? 'yes' : 'no'));
    $this->line('multi-server-readiness=' . $lockConfiguration->multiServerReadiness());
    $this->line('database-cache-tables=' . match ($databaseTables) {
        true => 'available',
        false => 'missing',
        default => 'not-applicable',
    });
    $this->line('scheduled-events=' . $events->count());
    $this->line('critical-events=' . ($criticalEvents->isNotEmpty() ? $criticalEvents->implode(',') : 'none'));

    return 0;
})->purpose('Report scheduler lock readiness without exposing cache configuration');

Artisan::command('system:heartbeat', function (SchedulerHeartbeat $heartbeat) {
    try {
        $heartbeat->beat();
        $this->info('scheduler-heartbeat=recorded');

        return 0;
    } catch (\Throwable $exception) {
        Log::error('scheduler-heartbeat-failed', [
            'scheduler_event' => 'system-heartbeat',
            'command' => 'system:heartbeat',
            'exception_class' => $exception::class,
            'result' => 'failed',
        ]);
        $this->error('scheduler-heartbeat=failed');

        return 1;
    }
})->purpose('Record a lightweight scheduler heartbeat in the configured cache store');

Artisan::command('system:diagnose {--json : Emit safe JSON output} {--no-write-probe : Skip the private storage write probe}', function (SystemHealthService $healthService, BackupDiagnosticsService $backupDiagnostics) {
    $report = $healthService->report(! $this->option('no-write-probe'));
    $data = $report->toDiagnosticsArray();
    $data['backup'] = $backupDiagnostics->report();

    if ($this->option('json')) {
        $this->line((string) json_encode($data, JSON_UNESCAPED_SLASHES));
    } else {
        $this->line('overall-status=' . $report->status);

        foreach ($report->checks as $check) {
            $this->line(sprintf(
                '%s=%s duration-ms=%d message=%s',
                $check->name,
                $check->status,
                $check->durationMs,
                $check->message,
            ));
        }
    }

    return match ($report->status) {
        'healthy' => 0,
        'degraded' => 1,
        default => 2,
    };
})->purpose('Report safe readiness diagnostics without exposing infrastructure secrets');

$schedulerLockConfiguration = app(SchedulerLockConfiguration::class);
if ($schedulerLockConfiguration->hasKnownStore()) {
    Schedule::useCache($schedulerLockConfiguration->store());
}

$guardScheduledEvent = function (Event $event, string $name, int $lockMinutes, bool $singleAcrossServers) use ($schedulerLockConfiguration): Event {
    $event->name($name)->withoutOverlapping($lockMinutes);

    if ($singleAcrossServers && $schedulerLockConfiguration->shouldUseOneServer()) {
        $event->onOneServer();
    }

    return $event;
};

$guardScheduledEvent(
    Schedule::command('system:heartbeat')->everyMinute(),
    'system-heartbeat',
    2,
    true,
);

// Rebuild summary penjualan setiap 15 menit agar data Omzet/Transaksi selalu fresh
$guardScheduledEvent(
    Schedule::command('analytics:daily-summary --days=0')->everyFifteenMinutes(),
    'sales-summary-current',
    10,
    true,
);

// Rebuild summary untuk kemarin juga, sekali dini hari (untuk menutup data kemarin)
$guardScheduledEvent(
    Schedule::command('analytics:daily-summary --days=2')->dailyAt('02:10'),
    'sales-summary-rebuild',
    30,
    true,
);

$guardScheduledEvent(
    Schedule::command('ops:traffic-alert-check --window=5')->everyFiveMinutes(),
    'traffic-alert-check',
    10,
    false,
);

$guardScheduledEvent(
    Schedule::command('ops:daily-stock-integrity-audit --days=1 --fail-on-findings=0')->dailyAt('03:20'),
    'daily-stock-integrity-audit',
    20,
    true,
);

// Menutup sesi stok kasir yang menggantung dari shift sebelumnya setiap jam 04:00 pagi
$guardScheduledEvent(
    Schedule::command('ops:auto-close-stock-sessions')->dailyAt('04:00'),
    'daily-stock-auto-close',
    30,
    true,
);

$guardScheduledEvent(
    Schedule::command('ops:doctor-env --strict=0')->dailyAt('03:10'),
    'environment-doctor',
    10,
    false,
);

// ─── BACKUP DATABASE OTOMATIS ───
// Harian: setiap hari jam 01:00
$guardScheduledEvent(
    Schedule::command('exports:cleanup')->dailyAt('03:35'),
    'exports-cleanup',
    10,
    true,
);

$guardScheduledEvent(
    Schedule::command('backup:database --type=harian')->dailyAt('01:00'),
    'backup-daily',
    180,
    true,
);

// Mingguan: setiap Senin jam 02:00
$guardScheduledEvent(
    Schedule::command('backup:database --type=mingguan')->weeklyOn(1, '02:00'),
    'backup-weekly',
    180,
    true,
);

// Bulanan: tanggal 1 setiap bulan jam 03:00
$guardScheduledEvent(
    Schedule::command('backup:database --type=bulanan')->monthlyOn(1, '03:00'),
    'backup-monthly',
    180,
    true,
);
