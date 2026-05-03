<?php

use App\Services\Analytics\DailySalesSummaryService;
use App\Services\System\DailyStockIntegrityAuditService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

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
        $result = $service->rebuildForDate($target);

        $this->info(sprintf(
            '[%s] trx=%d omzet=%.2f items=%d',
            $target->toDateString(),
            $result['total_transactions'],
            $result['total_revenue'],
            $result['total_items_sold']
        ));
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

    return ($failOnFindings && (int) $result['findings_count'] > 0) ? 1 : 0;
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

// Rebuild summary penjualan setiap 15 menit agar data Omzet/Transaksi selalu fresh
Schedule::command('analytics:daily-summary --days=0')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

// Rebuild summary untuk kemarin juga, sekali dini hari (untuk menutup data kemarin)
Schedule::command('analytics:daily-summary --days=2')
    ->dailyAt('02:10')
    ->withoutOverlapping();

Schedule::command('ops:traffic-alert-check --window=5')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('ops:daily-stock-integrity-audit --days=1 --fail-on-findings=0')
    ->dailyAt('03:20')
    ->withoutOverlapping();

// Menutup sesi stok kasir yang menggantung dari shift sebelumnya setiap jam 04:00 pagi
Schedule::command('ops:auto-close-stock-sessions')
    ->dailyAt('04:00')
    ->withoutOverlapping();

Schedule::command('ops:doctor-env --strict=0')
    ->dailyAt('03:10')
    ->withoutOverlapping();

// ─── BACKUP DATABASE OTOMATIS ───
// Harian: setiap hari jam 01:00
Schedule::command('backup:database --type=harian')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();

// Mingguan: setiap Senin jam 02:00
Schedule::command('backup:database --type=mingguan')
    ->weeklyOn(1, '02:00')
    ->withoutOverlapping()
    ->onOneServer();

// Bulanan: tanggal 1 setiap bulan jam 03:00
Schedule::command('backup:database --type=bulanan')
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->onOneServer();
