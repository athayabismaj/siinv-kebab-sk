<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('daily_targets') || Schema::hasColumn('daily_targets', 'branch_id')) {
            return;
        }

        Schema::table('daily_targets', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('id')
                ->constrained('branches')
                ->restrictOnDelete();
        });

        $defaultBranchId = (int) (DB::table('branches')->where('code', 'default')->value('id')
            ?: DB::table('branches')->orderBy('id')->value('id'));

        if ($defaultBranchId > 0) {
            DB::table('daily_targets')
                ->whereNull('branch_id')
                ->update(['branch_id' => $defaultBranchId]);
        }

        Schema::table('daily_targets', function (Blueprint $table) {
            $table->dropUnique(['target_date']);
            $table->unique(['branch_id', 'target_date'], 'daily_targets_branch_date_unique');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('daily_targets') || ! Schema::hasColumn('daily_targets', 'branch_id')) {
            return;
        }

        Schema::table('daily_targets', function (Blueprint $table) {
            $table->dropUnique('daily_targets_branch_date_unique');
            $table->dropIndex(['branch_id']);
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
