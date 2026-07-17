<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        DB::transaction(function (): void {
            $branch = DB::table('branches')
                ->where('code', 'default')
                ->where('name', 'Kebab SK')
                ->first(['id']);

            if (! $branch) {
                return;
            }

            if ($this->hasBranchReferences((int) $branch->id)) {
                return;
            }

            DB::table('branches')->where('id', $branch->id)->delete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        DB::table('branches')->insertOrIgnore([
            'name' => 'Kebab SK',
            'code' => 'default',
            'address' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function hasBranchReferences(int $branchId): bool
    {
        foreach ($this->branchReferenceTables() as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            if (DB::table($table)->where($column, $branchId)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function branchReferenceTables(): array
    {
        return [
            'users' => 'branch_id',
            'branch_user' => 'branch_id',
            'transactions' => 'branch_id',
            'daily_stock_sessions' => 'branch_id',
            'cashflow_entries' => 'branch_id',
            'stock_logs' => 'branch_id',
            'waste_logs' => 'branch_id',
            'period_closings' => 'branch_id',
            'daily_targets' => 'branch_id',
            'daily_sales_summaries' => 'branch_id',
            'transaction_sequences' => 'branch_id',
            'generated_exports' => 'branch_id',
        ];
    }
};
