<?php

namespace Tests\Feature\System;

use App\Models\GeneratedExport;
use App\Models\Role;
use App\Models\User;
use App\Services\System\SystemHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'scheduler.lock_store' => 'array',
            'scheduler.multi_server' => false,
            'health.scheduler_heartbeat_stale_seconds' => 180,
            'health.export_stale_processing_seconds' => 300,
            'health.failed_jobs_warning' => 1,
            'health.queue_oldest_pending_warning_seconds' => 300,
        ]);
    }

    public function test_liveness_is_minimal_and_returns_a_correlation_id(): void
    {
        $response = $this->get('/up');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->assertDoesNotMatchRegularExpression('/postgres|storage\\\\|SQLSTATE/i', $response->getContent());
    }

    public function test_liveness_preserves_a_valid_request_correlation_id(): void
    {
        Log::spy();

        $this->get('/up', ['X-Request-ID' => 'deploy-smoke-001'])
            ->assertOk()
            ->assertHeader('X-Request-ID', 'deploy-smoke-001');

        Log::shouldHaveReceived('withContext')
            ->once()
            ->with(['request_id' => 'deploy-smoke-001']);
    }

    public function test_liveness_replaces_an_invalid_request_correlation_id(): void
    {
        $response = $this->get('/up', ['X-Request-ID' => str_repeat('a', 65)])
            ->assertOk()
            ->assertHeader('X-Request-ID');

        $this->assertNotSame(str_repeat('a', 65), $response->headers->get('X-Request-ID'));
        $this->assertTrue(Str::isUuid((string) $response->headers->get('X-Request-ID')));
    }

    public function test_readiness_returns_only_a_healthy_status_when_dependencies_are_ready(): void
    {
        $this->writeSchedulerHeartbeat();

        $response = $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'healthy']);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_readiness_is_degraded_when_an_export_is_stale_without_exposing_details(): void
    {
        $this->writeSchedulerHeartbeat();

        $role = Role::query()->create(['name' => 'kasir']);
        $user = User::factory()->create(['role_id' => $role->id]);

        GeneratedExport::query()->create([
            'requested_by' => $user->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => [],
            'status' => GeneratedExport::STATUS_PROCESSING,
            'original_filename' => 'private-name.xlsx',
            'started_at' => now()->subSeconds(301),
        ]);

        $response = $this->getJson('/health/ready');

        $response
            ->assertOk()
            ->assertExactJson(['status' => 'degraded']);

        $this->assertStringNotContainsString('private-name.xlsx', $response->getContent());
    }

    public function test_readiness_is_degraded_when_a_completed_export_file_is_missing(): void
    {
        $this->writeSchedulerHeartbeat();

        $role = Role::query()->create(['name' => 'kasir']);
        $user = User::factory()->create(['role_id' => $role->id]);

        GeneratedExport::query()->create([
            'requested_by' => $user->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => [],
            'status' => GeneratedExport::STATUS_COMPLETED,
            'file_disk' => 'local',
            'file_path' => 'exports/health-missing-file.xlsx',
            'original_filename' => 'health-missing-file.xlsx',
            'completed_at' => now(),
        ]);

        $response = $this->getJson('/health/ready');

        $response
            ->assertOk()
            ->assertExactJson(['status' => 'degraded']);

        $this->assertStringNotContainsString('health-missing-file.xlsx', $response->getContent());
    }

    public function test_readiness_is_degraded_when_scheduler_heartbeat_is_stale(): void
    {
        Cache::store('array')->put(
            'system:scheduler-heartbeat',
            now()->subSeconds(181)->toIso8601String(),
            now()->addMinutes(5),
        );

        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'degraded']);
    }

    public function test_readiness_is_unhealthy_when_multi_server_locking_is_requested_without_shared_cache(): void
    {
        config([
            'scheduler.lock_store' => 'file',
            'scheduler.multi_server' => true,
        ]);

        $response = $this->getJson('/health/ready');

        $response
            ->assertStatus(503)
            ->assertExactJson(['status' => 'unhealthy']);

        $this->assertStringNotContainsString('file', $response->getContent());
    }

    public function test_readiness_is_degraded_when_the_cache_probe_fails(): void
    {
        $this->writeSchedulerHeartbeat();
        config(['cache.default' => 'health-missing-cache-store']);

        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'degraded']);
    }

    public function test_database_failure_is_unhealthy_and_the_diagnostics_are_sanitized(): void
    {
        $this->writeSchedulerHeartbeat();
        $originalDefault = config('database.default');
        config(['database.default' => 'health-missing-database-connection']);

        try {
            $report = app(SystemHealthService::class)->report();
        } finally {
            config(['database.default' => $originalDefault]);
        }

        $this->assertSame('unhealthy', $report->status);
        $this->assertStringNotContainsString('health-missing-database-connection', json_encode($report->toDiagnosticsArray()));
    }

    public function test_readiness_is_degraded_when_failed_jobs_reach_the_warning_threshold(): void
    {
        $this->writeSchedulerHeartbeat();

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'exports',
            'payload' => '{}',
            'exception' => 'safe test exception',
            'failed_at' => now(),
        ]);

        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'degraded']);
    }

    public function test_readiness_is_degraded_when_the_oldest_export_queue_job_is_too_old(): void
    {
        $this->writeSchedulerHeartbeat();

        DB::table('jobs')->insert([
            'queue' => 'exports',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->subSeconds(301)->timestamp,
            'created_at' => now()->subSeconds(301)->timestamp,
        ]);

        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'degraded']);
    }

    public function test_storage_probe_degrades_without_leaving_a_probe_file(): void
    {
        $this->writeSchedulerHeartbeat();
        $blockedRoot = storage_path('framework/testing-health-storage-blocked');
        File::put($blockedRoot, 'not-a-directory');
        config(['filesystems.disks.local.root' => $blockedRoot]);

        try {
            $this->getJson('/health/ready')
                ->assertOk()
                ->assertExactJson(['status' => 'degraded']);
        } finally {
            File::delete($blockedRoot);
        }

        $this->assertFalse(File::exists($blockedRoot));
    }

    public function test_system_diagnostics_outputs_safe_json_and_uses_degraded_exit_code_for_missing_heartbeat(): void
    {
        $this->artisan('system:diagnose --json --no-write-probe')
            ->expectsOutputToContain('"status":"degraded"')
            ->doesntExpectOutputToContain('postgres://')
            ->doesntExpectOutputToContain(storage_path())
            ->assertExitCode(1);
    }

    public function test_system_diagnostics_returns_healthy_exit_code_when_checks_are_ready(): void
    {
        $this->writeSchedulerHeartbeat();

        $this->artisan('system:diagnose --json')
            ->expectsOutputToContain('"status":"healthy"')
            ->assertExitCode(0);
    }

    public function test_system_diagnostics_returns_unhealthy_exit_code_for_invalid_multi_server_locking(): void
    {
        config([
            'scheduler.lock_store' => 'file',
            'scheduler.multi_server' => true,
        ]);

        $this->artisan('system:diagnose --json --no-write-probe')
            ->expectsOutputToContain('"status":"unhealthy"')
            ->assertExitCode(2);
    }

    private function writeSchedulerHeartbeat(): void
    {
        Cache::store('array')->put('system:scheduler-heartbeat', now()->toIso8601String(), now()->addMinutes(5));
    }
}
