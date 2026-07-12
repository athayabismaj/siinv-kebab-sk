<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['branch_id', 'user_id']);
                $table->index(['user_id', 'branch_id']);
            });
        }

        if (DB::connection()->pretending() || ! Schema::hasTable('roles') || ! Schema::hasColumn('users', 'branch_id')) {
            return;
        }

        $adminRoleIds = DB::table('roles')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['admin'])
            ->pluck('id');

        if ($adminRoleIds->isEmpty()) {
            return;
        }

        $now = now();
        DB::table('users')
            ->whereIn('role_id', $adminRoleIds)
            ->whereNotNull('branch_id')
            ->orderBy('id')
            ->select(['id', 'branch_id'])
            ->chunk(100, function ($users) use ($now) {
                $rows = $users->map(fn ($user) => [
                    'branch_id' => (int) $user->branch_id,
                    'user_id' => (int) $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('branch_user')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
