<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->createIndexIfMissing(
            'stock_logs',
            'stock_logs_type_created_at_idx',
            'create index stock_logs_type_created_at_idx on stock_logs (type, created_at)'
        );
        $this->createIndexIfMissing(
            'stock_logs',
            'stock_logs_ingredient_type_created_at_idx',
            'create index stock_logs_ingredient_type_created_at_idx on stock_logs (ingredient_id, type, created_at)'
        );
        $this->createIndexIfMissing(
            'transactions',
            'transactions_created_at_user_id_idx',
            'create index transactions_created_at_user_id_idx on transactions (created_at, user_id)'
        );
        $this->createIndexIfMissing(
            'transactions',
            'transactions_created_at_payment_method_id_idx',
            'create index transactions_created_at_payment_method_id_idx on transactions (created_at, payment_method_id)'
        );
        $this->createIndexIfMissing(
            'cashflow_entries',
            'cashflow_entries_type_entry_date_idx',
            'create index cashflow_entries_type_entry_date_idx on cashflow_entries (type, entry_date)'
        );
        $this->createIndexIfMissing(
            'cashflow_entries',
            'cashflow_entries_entry_date_created_by_idx',
            'create index cashflow_entries_entry_date_created_by_idx on cashflow_entries (entry_date, created_by)'
        );
    }

    public function down(): void
    {
        $this->dropIndexIfExists('cashflow_entries', 'cashflow_entries_type_entry_date_idx');
        $this->dropIndexIfExists('cashflow_entries', 'cashflow_entries_entry_date_created_by_idx');
        $this->dropIndexIfExists('transactions', 'transactions_created_at_user_id_idx');
        $this->dropIndexIfExists('transactions', 'transactions_created_at_payment_method_id_idx');
        $this->dropIndexIfExists('stock_logs', 'stock_logs_type_created_at_idx');
        $this->dropIndexIfExists('stock_logs', 'stock_logs_ingredient_type_created_at_idx');
    }

    private function createIndexIfMissing(string $table, string $indexName, string $sql): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        DB::statement($sql);
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('drop index ' . $indexName . ' on ' . $table);
            return;
        }

        DB::statement('drop index ' . $indexName);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $exists = DB::table('pg_indexes')
                ->whereRaw('schemaname = current_schema()')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();

            return (bool) $exists;
        }

        if ($driver === 'mysql') {
            $databaseName = (string) config('database.connections.mysql.database');

            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', $databaseName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();

            return (bool) $exists;
        }

        return false;
    }
};
