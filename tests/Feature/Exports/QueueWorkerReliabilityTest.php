<?php

namespace Tests\Feature\Exports;

use App\Jobs\GenerateDailyStockReportExport;
use App\Jobs\GenerateExpenseExport;
use App\Jobs\GenerateSalesReportExport;
use App\Jobs\GenerateStockLogExport;
use App\Jobs\GenerateTransactionExport;
use App\Jobs\GenerateUsageReportExport;
use App\Models\GeneratedExport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QueueWorkerReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_export_job_uses_the_dedicated_database_exports_queue(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.connection' => null,
        ]);

        $export = $this->pendingTransactionExport();

        GenerateTransactionExport::dispatch($export->id);

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('jobs', ['queue' => 'exports']);
    }

    public function test_export_job_declares_the_operational_timeout_and_retry_contract(): void
    {
        $job = new GenerateTransactionExport(123);

        $this->assertSame('exports', $job->queue);
        $this->assertSame('database', $job->connection);
        $this->assertTrue($job->afterCommit);
        $this->assertSame(60, $job->timeout);
        $this->assertSame(2, $job->tries);
        $this->assertSame(60, $job->backoff);
        $this->assertGreaterThan($job->timeout, config('queue.connections.database.retry_after'));
    }

    public function test_every_export_job_uses_the_dedicated_queue_and_dispatches_after_commit(): void
    {
        foreach ([
            GenerateTransactionExport::class,
            GenerateStockLogExport::class,
            GenerateUsageReportExport::class,
            GenerateDailyStockReportExport::class,
            GenerateExpenseExport::class,
            GenerateSalesReportExport::class,
        ] as $jobClass) {
            $job = new $jobClass(123);

            $this->assertSame('database', $job->connection, $jobClass);
            $this->assertSame('exports', $job->queue, $jobClass);
            $this->assertTrue($job->afterCommit, $jobClass);
        }
    }

    public function test_database_worker_processes_an_export_from_the_exports_queue(): void
    {
        $this->useDatabaseQueue();
        Storage::fake('local');
        $export = $this->pendingTransactionExport();

        GenerateTransactionExport::dispatch($export->id);

        $this->assertDatabaseHas('jobs', ['queue' => 'exports']);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--queue' => 'exports',
            '--once' => true,
            '--sleep' => 0,
            '--tries' => 2,
            '--timeout' => 75,
        ])->assertExitCode(0);

        $export->refresh();

        $this->assertSame(GeneratedExport::STATUS_COMPLETED, $export->status);
        $this->assertDatabaseCount('jobs', 0);
        Storage::disk('local')->assertExists($export->file_path);
    }

    public function test_export_dispatch_waits_for_the_creating_transaction_to_commit(): void
    {
        $this->useDatabaseQueue();

        DB::transaction(function (): void {
            $export = $this->pendingTransactionExport();

            GenerateTransactionExport::dispatch($export->id);

            $this->assertDatabaseCount('jobs', 0);
        });

        $this->assertDatabaseHas('jobs', ['queue' => 'exports']);
    }

    public function test_database_worker_records_a_failed_export_job_and_keeps_it_retryable(): void
    {
        $this->useDatabaseQueue();
        $export = $this->pendingTransactionExport();
        $export->update(['filters' => ['date_from' => 'not-a-date', 'date_to' => 'not-a-date']]);

        $job = new GenerateTransactionExport($export->id);
        $job->tries = 1;

        dispatch($job);

        $this->artisan('queue:work', [
            'connection' => 'database',
            '--queue' => 'exports',
            '--once' => true,
            '--sleep' => 0,
            '--tries' => 1,
            '--timeout' => 75,
        ])->assertExitCode(0);

        $export->refresh();

        $this->assertSame(GeneratedExport::STATUS_FAILED, $export->status);
        $this->assertDatabaseCount('failed_jobs', 1);
        $this->assertDatabaseCount('jobs', 0);
    }

    private function useDatabaseQueue(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.connection' => null,
        ]);
    }

    private function pendingTransactionExport(): GeneratedExport
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
            'original_filename' => 'uji-antrean.xlsx',
            'expires_at' => now()->addDay(),
        ]);
    }
}
