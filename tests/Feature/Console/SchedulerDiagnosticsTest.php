<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private string|false $previousLockStore;

    private string|false $previousMultiServer;

    protected function setUp(): void
    {
        $this->previousLockStore = getenv('SCHEDULER_CACHE_STORE');
        $this->previousMultiServer = getenv('SCHEDULER_MULTI_SERVER');
        putenv('SCHEDULER_CACHE_STORE=database');
        putenv('SCHEDULER_MULTI_SERVER=true');
        $_ENV['SCHEDULER_CACHE_STORE'] = 'database';
        $_ENV['SCHEDULER_MULTI_SERVER'] = 'true';

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironment('SCHEDULER_CACHE_STORE', $this->previousLockStore);
        $this->restoreEnvironment('SCHEDULER_MULTI_SERVER', $this->previousMultiServer);

        parent::tearDown();
    }

    public function test_scheduler_diagnostics_reports_lock_readiness_without_exposing_connection_details(): void
    {
        $this->artisan('scheduler:diagnose')
            ->expectsOutputToContain('timezone=Asia/Jakarta')
            ->expectsOutputToContain('lock-store-known=yes')
            ->expectsOutputToContain('multi-server-readiness=ready')
            ->expectsOutputToContain('database-cache-tables=available')
            ->expectsOutputToContain('scheduled-events=11')
            ->expectsOutputToContain('critical-events=system-heartbeat,sales-summary-current')
            ->doesntExpectOutputToContain('postgres://')
            ->assertExitCode(0);
    }

    private function restoreEnvironment(string $key, string|false $value): void
    {
        if ($value === false) {
            putenv($key);
            unset($_ENV[$key]);

            return;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}
