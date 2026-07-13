<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('period_closings') || ! Schema::hasTable('branches')) {
            return;
        }

        if (! Schema::hasColumn('period_closings', 'branch_id')) {
            Schema::table('period_closings', function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->index('branch_id');
            });
        }

        if (! DB::connection()->pretending()) {
            $defaultBranchId = DB::table('branches')->where('code', 'default')->value('id')
                ?: DB::table('branches')->orderBy('id')->value('id');

            if ($defaultBranchId) {
                DB::table('period_closings')
                    ->whereNull('branch_id')
                    ->update(['branch_id' => (int) $defaultBranchId]);
            }
        }

        if (! $this->uniqueIndexExists('period_closings_branch_period_unique')) {
            Schema::table('period_closings', function (Blueprint $table) {
                $table->unique(['branch_id', 'period_type', 'period_date'], 'period_closings_branch_period_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('period_closings')) {
            return;
        }

        if ($this->uniqueIndexExists('period_closings_branch_period_unique')) {
            Schema::table('period_closings', function (Blueprint $table) {
                $table->dropUnique('period_closings_branch_period_unique');
            });
        }
    }

    private function uniqueIndexExists(string $indexName): bool
    {
        if (DB::connection()->pretending()) {
            return false;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return (bool) DB::table('pg_indexes')
                ->where('schemaname', DB::raw('current_schema()'))
                ->where('indexname', $indexName)
                ->exists();
        }

        $database = DB::getDatabaseName();

        return (bool) DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'period_closings')
            ->where('index_name', $indexName)
            ->exists();
    }
};
