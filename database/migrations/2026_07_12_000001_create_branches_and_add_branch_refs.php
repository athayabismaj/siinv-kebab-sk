<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 50)->unique();
            $table->string('address', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaultBranchId = null;
        if (! DB::connection()->pretending()) {
            $now = now();
            DB::table('branches')->insertOrIgnore([
                'name' => 'Kebab SK',
                'code' => 'default',
                'address' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $defaultBranchId = (int) DB::table('branches')->where('code', 'default')->value('id');
        }

        $this->addBranchColumn('users', $defaultBranchId);
        $this->addBranchColumn('transactions', $defaultBranchId);
        $this->addBranchColumn('daily_stock_sessions', $defaultBranchId);
        $this->addBranchColumn('stock_logs', $defaultBranchId);
        $this->addBranchColumn('cashflow_entries', $defaultBranchId);
        $this->addBranchColumn('waste_logs', $defaultBranchId);
    }

    public function down(): void
    {
        foreach (['waste_logs', 'cashflow_entries', 'stock_logs', 'daily_stock_sessions', 'transactions', 'users'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('branch_id');
                });
            }
        }

        Schema::dropIfExists('branches');
    }

    private function addBranchColumn(string $tableName, ?int $defaultBranchId): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->index('branch_id');
        });

        if ($defaultBranchId && ! DB::connection()->pretending()) {
            DB::table($tableName)->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
        }
    }
};
