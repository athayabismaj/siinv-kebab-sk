<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->index(['category_id', 'deleted_at'], 'ingredients_category_deleted_idx');
            $table->index('created_at', 'ingredients_created_at_idx');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->index(['category_id', 'deleted_at'], 'menus_category_deleted_idx');
            $table->index(['sort_order', 'created_at'], 'menus_sort_created_idx');
        });

        Schema::table('menu_variants', function (Blueprint $table) {
            $table->index(['menu_id', 'is_available', 'sort_order'], 'menu_variants_menu_available_sort_idx');
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->index('transaction_id', 'transaction_details_transaction_id_idx');
            $table->index('menu_id', 'transaction_details_menu_id_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->index(['type', 'created_at'], 'stock_logs_type_created_idx');
            $table->index('reference_id', 'stock_logs_reference_id_idx');
        });

        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->index(['type', 'entry_date'], 'cashflow_entries_type_entry_date_idx');
        });

        Schema::table('api_tokens', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'api_tokens_user_created_idx');
            $table->index('expires_at', 'api_tokens_expires_at_idx');
        });

        Schema::table('password_otps', function (Blueprint $table) {
            $table->index(['user_id', 'used', 'created_at'], 'password_otps_user_used_created_idx');
            $table->index('expires_at', 'password_otps_expires_at_idx');
        });
    }

    public function down(): void
    {
        $this->dropIndexIfExists('password_otps', 'password_otps_user_used_created_idx');
        $this->dropIndexIfExists('password_otps', 'password_otps_expires_at_idx');

        $this->dropIndexIfExists('api_tokens', 'api_tokens_user_created_idx');
        $this->dropIndexIfExists('api_tokens', 'api_tokens_expires_at_idx');

        $this->dropIndexIfExists('cashflow_entries', 'cashflow_entries_type_entry_date_idx');

        $this->dropIndexIfExists('stock_logs', 'stock_logs_type_created_idx');
        $this->dropIndexIfExists('stock_logs', 'stock_logs_reference_id_idx');

        $this->dropIndexIfExists('transaction_details', 'transaction_details_transaction_id_idx');
        $this->dropIndexIfExists('transaction_details', 'transaction_details_menu_id_idx');

        $this->dropIndexIfExists('menu_variants', 'menu_variants_menu_available_sort_idx');

        $this->dropIndexIfExists('menus', 'menus_category_deleted_idx');
        $this->dropIndexIfExists('menus', 'menus_sort_created_idx');

        $this->dropIndexIfExists('ingredients', 'ingredients_category_deleted_idx');
        $this->dropIndexIfExists('ingredients', 'ingredients_created_at_idx');
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('drop index ' . $indexName . ' on ' . $table);
            return;
        }

        \Illuminate\Support\Facades\DB::statement('drop index ' . $indexName);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        if ($driver === 'pgsql') {
            $exists = \Illuminate\Support\Facades\DB::table('pg_indexes')
                ->whereRaw('schemaname = current_schema()')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();

            return (bool) $exists;
        }

        if ($driver === 'mysql') {
            $databaseName = (string) config('database.connections.mysql.database');

            $exists = \Illuminate\Support\Facades\DB::table('information_schema.statistics')
                ->where('table_schema', $databaseName)
                ->where('table_name', $table)
                ->where('index_name', $indexName)
                ->exists();

            return (bool) $exists;
        }

        if ($driver === 'sqlite') {
            $indexes = \Illuminate\Support\Facades\DB::select("pragma index_list('$table')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }
        }

        return false;
    }
};
