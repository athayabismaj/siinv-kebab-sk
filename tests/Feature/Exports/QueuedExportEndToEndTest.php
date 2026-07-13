<?php

namespace Tests\Feature\Exports;

use App\Jobs\GenerateDailyStockReportExport;
use App\Jobs\GenerateExpenseExport;
use App\Jobs\GenerateSalesReportExport;
use App\Jobs\GenerateStockLogExport;
use App\Jobs\GenerateTransactionExport;
use App\Jobs\GenerateUsageReportExport;
use App\Models\Branch;
use App\Models\CashflowEntry;
use App\Models\DailyStockSession;
use App\Models\GeneratedExport;
use App\Models\Ingredient;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Admin\DailyStockReportQueryService;
use App\Services\Exports\ExpenseExportQuery;
use App\Services\Exports\SalesReportExportQuery;
use App\Services\Exports\StockLogExportQuery;
use App\Services\Exports\UsageReportExportQuery;
use App\Services\Owner\TransactionHistoryQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class QueuedExportEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_transaction_workbook_is_readable_and_keeps_the_branch_snapshot(): void
    {
        [$owner, $branch, $otherBranch, $payment] = $this->context('transaction');
        $cashier = $this->user('kasir', $branch->id, 'transaction-cashier');
        $otherCashier = $this->user('kasir', $otherBranch->id, 'transaction-other-cashier');
        $this->transaction($branch, $cashier, $payment, 'TRX-A-001');
        $this->transaction($otherBranch, $otherCashier, $payment, 'TRX-B-001');

        $export = $this->export($owner, $branch, 'transaction_history', 'Riwayat.xlsx', $this->transactionFilters());
        (new GenerateTransactionExport($export->id))->handle(app(TransactionHistoryQueryService::class));

        $this->assertWorkbook($export, ['Kode Transaksi', 'TRX-A-001'], ['TRX-B-001']);
    }

    public function test_stock_log_workbook_is_readable_and_keeps_the_branch_snapshot(): void
    {
        [$owner, $branch, $otherBranch] = $this->context('stock');
        $ingredient = Ingredient::query()->create(['name' => 'Tortilla', 'stock' => 20, 'minimum_stock' => 1, 'base_unit' => 'pcs', 'display_unit' => 'pcs']);
        $this->stockLog($branch, $ingredient, 'Log Cabang A');
        $this->stockLog($otherBranch, $ingredient, 'Log Cabang B');

        $export = $this->export($owner, $branch, 'stock_log', 'Riwayat-Stok.xlsx', $this->dateFilters(['type' => null]));
        (new GenerateStockLogExport($export->id))->handle(app(StockLogExportQuery::class));

        $this->assertWorkbook($export, ['Catatan', 'Log Cabang A'], ['Log Cabang B']);
    }

    public function test_usage_workbook_is_readable_and_keeps_the_branch_snapshot(): void
    {
        [$owner, $branch, $otherBranch, $payment] = $this->context('usage');
        $cashier = $this->user('kasir', $branch->id, 'usage-cashier');
        $otherCashier = $this->user('kasir', $otherBranch->id, 'usage-other-cashier');
        $ingredient = Ingredient::query()->create(['name' => 'Bahan Cabang A', 'stock' => 20, 'minimum_stock' => 1, 'base_unit' => 'pcs', 'display_unit' => 'pcs']);
        $otherIngredient = Ingredient::query()->create(['name' => 'Bahan Cabang B', 'stock' => 20, 'minimum_stock' => 1, 'base_unit' => 'pcs', 'display_unit' => 'pcs']);
        $transaction = $this->transaction($branch, $cashier, $payment, 'TRX-USAGE-A');
        $otherTransaction = $this->transaction($otherBranch, $otherCashier, $payment, 'TRX-USAGE-B');
        $this->usage($branch, $ingredient, $transaction);
        $this->usage($otherBranch, $otherIngredient, $otherTransaction);

        $export = $this->export($owner, $branch, 'usage_report', 'Pemakaian.xlsx', $this->dateFilters());
        (new GenerateUsageReportExport($export->id))->handle(app(UsageReportExportQuery::class));

        $this->assertWorkbook($export, ['Nama Bahan Baku', 'Bahan Cabang A'], ['Bahan Cabang B']);
    }

    public function test_daily_stock_workbook_is_readable_and_keeps_the_branch_snapshot(): void
    {
        [$owner, $branch, $otherBranch] = $this->context('daily');
        $cashier = $this->user('kasir', $branch->id, 'daily-cashier');
        $otherCashier = $this->user('kasir', $otherBranch->id, 'daily-other-cashier');
        $this->dailyStockSession($branch, $cashier);
        $this->dailyStockSession($otherBranch, $otherCashier);

        $export = $this->export($owner, $branch, 'daily_stock_report', 'Stok-Harian.xlsx', $this->dateFilters());
        (new GenerateDailyStockReportExport($export->id))->handle(app(DailyStockReportQueryService::class));

        $this->assertWorkbook(
            $export,
            ['Tanggal & Kasir', now()->format('d/m/Y') . ' - ' . $cashier->name],
            [now()->format('d/m/Y') . ' - ' . $otherCashier->name]
        );
    }

    public function test_expense_workbook_is_readable_and_keeps_the_branch_snapshot(): void
    {
        [$owner, $branch, $otherBranch] = $this->context('expense');
        $this->expense($owner, $branch, 'Operasional Cabang A');
        $this->expense($owner, $otherBranch, 'Operasional Cabang B');

        $export = $this->export($owner, $branch, 'expense_report', 'Pengeluaran.xlsx', $this->dateFilters(['type' => 'daily', 'search' => '']));
        (new GenerateExpenseExport($export->id))->handle(app(ExpenseExportQuery::class));

        $this->assertWorkbook($export, ['Nominal Pengeluaran', 'Operasional Cabang A'], ['Operasional Cabang B']);
    }

    public function test_sales_workbook_is_readable_and_keeps_the_branch_snapshot(): void
    {
        [$owner, $branch, $otherBranch, $payment] = $this->context('sales');
        $cashier = $this->user('kasir', $branch->id, 'sales-cashier');
        $otherCashier = $this->user('kasir', $otherBranch->id, 'sales-other-cashier');
        $this->transaction($branch, $cashier, $payment, 'TRX-SALES-A');
        $this->transaction($otherBranch, $otherCashier, $payment, 'TRX-SALES-B');

        $export = $this->export($owner, $branch, 'sales_report', 'Penjualan.xlsx', $this->dateFilters(['type' => 'daily']));
        (new GenerateSalesReportExport($export->id))->handle(app(SalesReportExportQuery::class));

        $this->assertWorkbook($export, ['LAPORAN PENJUALAN HARIAN', 'TRX-SALES-A'], ['TRX-SALES-B']);
    }

    private function assertWorkbook(GeneratedExport $export, array $expectedValues, array $unexpectedValues): void
    {
        $export->refresh();
        $this->assertSame(GeneratedExport::STATUS_COMPLETED, $export->status);
        Storage::disk('local')->assertExists($export->file_path);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($export->file_path));
        $values = collect($spreadsheet->getActiveSheet()->toArray(null, true, true, true))
            ->flatten()
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->all();

        foreach ($expectedValues as $value) {
            $this->assertContains($value, $values);
        }

        foreach ($unexpectedValues as $value) {
            $this->assertNotContains($value, $values);
        }
    }

    private function context(string $suffix): array
    {
        $owner = $this->user('owner', null, $suffix . '-owner');
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $otherBranch = Branch::query()->create(['name' => 'Kebab SK Cabang Lain ' . $suffix, 'code' => substr($suffix, 0, 3) . '2', 'is_active' => true]);
        $payment = PaymentMethod::query()->create(['name' => 'Tunai']);

        return [$owner, $branch, $otherBranch, $payment];
    }

    private function export(User $owner, Branch $branch, string $type, string $filename, array $filters): GeneratedExport
    {
        return GeneratedExport::query()->create([
            'requested_by' => $owner->id,
            'branch_id' => $branch->id,
            'type' => $type,
            'format' => 'excel',
            'filters' => $filters,
            'status' => GeneratedExport::STATUS_PENDING,
            'original_filename' => $filename,
            'expires_at' => now()->addDays(7),
        ]);
    }

    private function transactionFilters(): array
    {
        return $this->dateFilters(['search' => '', 'user_id' => 0, 'payment_method_id' => 0]);
    }

    private function dateFilters(array $extra = []): array
    {
        return array_merge([
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ], $extra);
    }

    private function transaction(Branch $branch, User $cashier, PaymentMethod $payment, string $code): Transaction
    {
        return Transaction::query()->create([
            'transaction_code' => $code,
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'payment_method_id' => $payment->id,
            'total_amount' => 1000,
            'paid_amount' => 1000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
        ]);
    }

    private function stockLog(Branch $branch, Ingredient $ingredient, string $note): void
    {
        StockLog::query()->create(['branch_id' => $branch->id, 'ingredient_id' => $ingredient->id, 'type' => 'in', 'quantity' => 1, 'note' => $note]);
    }

    private function usage(Branch $branch, Ingredient $ingredient, Transaction $transaction): void
    {
        StockLog::query()->create(['branch_id' => $branch->id, 'ingredient_id' => $ingredient->id, 'reference_id' => $transaction->id, 'type' => 'daily_usage', 'quantity' => -1]);
    }

    private function dailyStockSession(Branch $branch, User $cashier): void
    {
        DailyStockSession::query()->create(['session_date' => now()->toDateString(), 'branch_id' => $branch->id, 'cashier_id' => $cashier->id, 'opened_by' => $cashier->id, 'status' => 'open']);
    }

    private function expense(User $owner, Branch $branch, string $source): void
    {
        CashflowEntry::query()->create(['entry_date' => now()->toDateString(), 'branch_id' => $branch->id, 'type' => 'expense', 'amount' => 1000, 'source' => $source, 'created_by' => $owner->id]);
    }

    private function user(string $role, ?int $branchId, string $suffix): User
    {
        $roleModel = Role::query()->firstOrCreate(['name' => $role]);

        return User::query()->create(['name' => ucfirst($role) . ' ' . $suffix, 'username' => $role . '-' . $suffix, 'email' => $role . '-' . $suffix . '@example.test', 'password' => 'secret123', 'role_id' => $roleModel->id, 'branch_id' => $branchId]);
    }
}
