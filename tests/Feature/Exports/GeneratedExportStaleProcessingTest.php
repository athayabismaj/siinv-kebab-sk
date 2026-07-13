<?php

namespace Tests\Feature\Exports;

use App\Jobs\GenerateTransactionExport;
use App\Models\GeneratedExport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeneratedExportStaleProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_detection_is_read_only_by_default(): void
    {
        $export = $this->staleExport();

        $this->artisan('exports:detect-stale')
            ->assertExitCode(0);

        $this->assertSame(GeneratedExport::STATUS_PROCESSING, $export->fresh()->status);
    }

    public function test_explicit_recovery_marks_a_stale_export_failed_and_removes_its_partial_file(): void
    {
        Storage::fake('local');
        $export = $this->staleExport();
        $path = 'exports/' . $export->requested_by . '/' . $export->id . '/' . $export->original_filename;
        Storage::disk('local')->put($path, 'partial workbook');

        $this->artisan('exports:detect-stale', [
            '--mark-failed' => true,
            '--id' => [$export->id],
        ])->assertExitCode(0);

        $this->assertSame(GeneratedExport::STATUS_FAILED, $export->fresh()->status);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_recovery_does_not_mark_a_stale_export_when_its_job_is_still_queued(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.connection' => null,
        ]);

        $export = $this->staleExport();
        GenerateTransactionExport::dispatch($export->id);

        $this->artisan('exports:detect-stale', [
            '--mark-failed' => true,
            '--id' => [$export->id],
        ])->assertExitCode(0);

        $this->assertDatabaseHas('jobs', ['queue' => 'exports']);
        $this->assertSame(GeneratedExport::STATUS_PROCESSING, $export->fresh()->status);
    }

    private function staleExport(): GeneratedExport
    {
        config(['exports.stale_after_seconds' => 300]);

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
            'status' => GeneratedExport::STATUS_PROCESSING,
            'original_filename' => 'stale-export.xlsx',
            'started_at' => now()->subSeconds(301),
            'expires_at' => now()->addDay(),
        ]);
    }
}
