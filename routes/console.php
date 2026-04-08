<?php

use App\Services\Analytics\DailySalesSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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

Schedule::command('analytics:daily-summary --days=2')
    ->dailyAt('02:10')
    ->withoutOverlapping();
