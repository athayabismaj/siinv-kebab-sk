<?php

namespace Tests\Benchmark;

use App\Jobs\GenerateTransactionExport;
use App\Models\Branch;
use App\Models\GeneratedExport;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Owner\TransactionHistoryQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QueuedTransactionExportBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('rowCounts')]
    public function test_queued_transaction_export_benchmark(int $rowCount): void
    {
        Storage::fake('local');
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $role = Role::query()->firstOrCreate(['name' => 'owner']);
        $owner = User::query()->create(['name' => 'Benchmark Owner', 'username' => 'benchmark-owner', 'email' => 'benchmark-owner@example.test', 'password' => 'secret123', 'role_id' => $role->id]);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $cashier = User::query()->create(['name' => 'Benchmark Cashier', 'username' => 'benchmark-cashier', 'email' => 'benchmark-cashier@example.test', 'password' => 'secret123', 'role_id' => $cashierRole->id, 'branch_id' => $branch->id]);
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);

        foreach (range(1, $rowCount) as $number) {
            Transaction::query()->create([
                'transaction_code' => sprintf('TRX-BENCH-%04d', $number),
                'branch_id' => $branch->id,
                'user_id' => $cashier->id,
                'payment_method_id' => $payment->id,
                'total_amount' => 1000,
                'paid_amount' => 1000,
                'change_amount' => 0,
                'status' => 'SUCCESS',
            ]);
        }

        $export = GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'branch_id' => $branch->id,
            'type' => 'transaction_history',
            'format' => 'excel',
            'filters' => ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString(), 'search' => '', 'user_id' => 0, 'payment_method_id' => 0],
            'status' => GeneratedExport::STATUS_PENDING,
            'original_filename' => 'Benchmark-' . $rowCount . '.xlsx',
            'expires_at' => now()->addDay(),
        ]);

        $memoryBefore = memory_get_usage(true);
        $startedAt = hrtime(true);

        (new GenerateTransactionExport($export->id))->handle(app(TransactionHistoryQueryService::class));

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $memoryDeltaMb = (memory_get_peak_usage(true) - $memoryBefore) / 1024 / 1024;
        $export->refresh();
        $sizeKb = Storage::disk('local')->size($export->file_path) / 1024;

        fwrite(STDERR, sprintf(
            "BENCHMARK transaction rows=%d duration_ms=%.2f peak_delta_mb=%.2f file_kb=%.2f\n",
            $rowCount,
            $durationMs,
            $memoryDeltaMb,
            $sizeKb
        ));

        $this->assertSame(GeneratedExport::STATUS_COMPLETED, $export->status);
        $this->assertGreaterThan(0, $sizeKb);
    }

    public static function rowCounts(): array
    {
        return [[100], [250], [1000]];
    }
}
