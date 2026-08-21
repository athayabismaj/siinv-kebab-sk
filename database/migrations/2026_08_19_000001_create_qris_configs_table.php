<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qris_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('merchant_name', 255);
            $table->string('merchant_city', 255)->nullable();
            $table->text('qris_payload');
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'created_at'], 'qris_configs_branch_history_idx');
        });

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX qris_configs_one_active_per_branch ON qris_configs (branch_id) WHERE is_active = true');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX qris_configs_one_active_per_branch ON qris_configs (branch_id) WHERE is_active = 1');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qris_configs');
    }
};
