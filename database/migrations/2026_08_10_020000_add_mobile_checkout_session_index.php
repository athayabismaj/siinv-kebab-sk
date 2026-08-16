<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_stock_sessions', function (Blueprint $table) {
            $table->index(
                ['cashier_id', 'status', 'session_date', 'branch_id', 'id'],
                'daily_stock_cashier_status_date_branch_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('daily_stock_sessions', function (Blueprint $table) {
            $table->dropIndex('daily_stock_cashier_status_date_branch_idx');
        });
    }
};
