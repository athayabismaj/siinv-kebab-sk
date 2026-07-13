<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Verified in the original migration and the current PostgreSQL schema.
    private const LEGACY_SALE_DATE_UNIQUE = 'daily_sales_summaries_sale_date_unique';

    private const BRANCH_SALE_DATE_UNIQUE = 'daily_sales_summaries_branch_sale_date_unique';

    public function up(): void
    {
        $this->runAtomically(function (): void {
            $this->assertTransactionsHaveValidBranches();

            // Summary rows are derived data. They cannot be assigned to one branch safely,
            // so rebuild them from the branch-scoped transaction source instead.
            DB::table('daily_sales_summaries')->delete();

            Schema::table('daily_sales_summaries', function (Blueprint $table) {
                $table->dropUnique(self::LEGACY_SALE_DATE_UNIQUE);
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->unique(['branch_id', 'sale_date'], self::BRANCH_SALE_DATE_UNIQUE);
            });

            $this->rebuildFromSuccessfulTransactions();
        });
    }

    public function down(): void
    {
        $this->runAtomically(function (): void {
            $collapsedRows = DB::table('daily_sales_summaries')
                ->selectRaw('sale_date, SUM(total_transactions) as total_transactions, SUM(total_revenue) as total_revenue, SUM(total_items_sold) as total_items_sold, MIN(created_at) as created_at, MAX(updated_at) as updated_at')
                ->groupBy('sale_date')
                ->orderBy('sale_date')
                ->get();

            DB::table('daily_sales_summaries')->delete();

            Schema::table('daily_sales_summaries', function (Blueprint $table) {
                $table->dropUnique(self::BRANCH_SALE_DATE_UNIQUE);
                $table->dropConstrainedForeignId('branch_id');
            });

            if ($collapsedRows->isNotEmpty()) {
                DB::table('daily_sales_summaries')->insert($collapsedRows->map(fn ($row) => [
                    'sale_date' => $row->sale_date,
                    'total_transactions' => (int) $row->total_transactions,
                    'total_revenue' => $row->total_revenue,
                    'total_items_sold' => (int) $row->total_items_sold,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ])->all());
            }

            Schema::table('daily_sales_summaries', function (Blueprint $table) {
                $table->unique('sale_date', self::LEGACY_SALE_DATE_UNIQUE);
            });
        });
    }

    private function assertTransactionsHaveValidBranches(): void
    {
        $unmappedTransactions = DB::table('transactions as transactions')
            ->leftJoin('branches as branches', 'branches.id', '=', 'transactions.branch_id')
            ->where(function ($query) {
                $query->whereNull('transactions.branch_id')
                    ->orWhereNull('branches.id');
            })
            ->count();

        if ($unmappedTransactions > 0) {
            throw new RuntimeException(
                "Daily sales summary migration stopped: {$unmappedTransactions} transaction(s) cannot be mapped to a valid branch."
            );
        }
    }

    private function rebuildFromSuccessfulTransactions(): void
    {
        $transactionRows = DB::table('transactions')
            ->selectRaw('branch_id, DATE(created_at) as sale_date, COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->whereRaw("UPPER(COALESCE(status, '')) = ?", ['SUCCESS'])
            ->groupBy('branch_id')
            ->groupByRaw('DATE(created_at)')
            ->get();

        $itemsByBranchAndDate = DB::table('transaction_details as details')
            ->join('transactions as transactions', 'transactions.id', '=', 'details.transaction_id')
            ->selectRaw('transactions.branch_id, DATE(transactions.created_at) as sale_date, COALESCE(SUM(details.quantity), 0) as total_items_sold')
            ->whereRaw("UPPER(COALESCE(transactions.status, '')) = ?", ['SUCCESS'])
            ->groupBy('transactions.branch_id')
            ->groupByRaw('DATE(transactions.created_at)')
            ->get()
            ->keyBy(fn ($row) => $row->branch_id . '|' . $row->sale_date);

        $timestamp = now();
        $rows = $transactionRows->map(function ($row) use ($itemsByBranchAndDate, $timestamp) {
            $itemRow = $itemsByBranchAndDate->get($row->branch_id . '|' . $row->sale_date);

            return [
                'branch_id' => (int) $row->branch_id,
                'sale_date' => $row->sale_date,
                'total_transactions' => (int) $row->total_transactions,
                'total_revenue' => $row->total_revenue,
                'total_items_sold' => (int) ($itemRow->total_items_sold ?? 0),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        });

        if ($rows->isNotEmpty()) {
            DB::table('daily_sales_summaries')->insert($rows->all());
        }
    }

    private function runAtomically(callable $callback): void
    {
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::transaction($callback);

            return;
        }

        $callback();
    }
};
