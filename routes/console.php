<?php

use App\Services\Analytics\DailySalesSummaryService;
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

Schedule::command('analytics:daily-summary --days=2')
    ->dailyAt('02:10')
    ->withoutOverlapping();

Schedule::command('ops:traffic-alert-check --window=5')
    ->everyFiveMinutes()
    ->withoutOverlapping();
