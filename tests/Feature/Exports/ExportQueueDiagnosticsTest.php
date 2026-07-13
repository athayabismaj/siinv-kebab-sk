<?php

namespace Tests\Feature\Exports;

use App\Jobs\GenerateTransactionExport;
use App\Models\GeneratedExport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportQueueDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_diagnostics_reports_export_queue_health_without_exposing_job_payloads(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.connection' => null,
        ]);
        Storage::fake('local');
        $export = $this->pendingExport();

        GenerateTransactionExport::dispatch($export->id);

        $this->artisan('exports:diagnose')
            ->expectsOutputToContain('queue=exports')
            ->expectsOutputToContain('pending-jobs=1')
            ->expectsOutputToContain('generated-exports-pending=1')
            ->doesntExpectOutputToContain('data.command')
            ->assertExitCode(0);
    }

    public function test_queue_diagnostics_reports_missing_export_table_without_throwing(): void
    {
        Schema::drop('generated_exports');

        $this->artisan('exports:diagnose')
            ->expectsOutputToContain('generated-exports-pending=unavailable')
            ->expectsOutputToContain('health=unhealthy')
            ->assertExitCode(0);
    }

    private function pendingExport(): GeneratedExport
    {
        $role = Role::query()->firstOrCreate(['name' => 'owner']);
        $user = User::factory()->create(['role_id' => $role->id]);

        return GeneratedExport::query()->create([
            'requested_by' => $user->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ],
            'status' => GeneratedExport::STATUS_PENDING,
            'original_filename' => 'diagnostic.xlsx',
            'expires_at' => now()->addDay(),
        ]);
    }
}
