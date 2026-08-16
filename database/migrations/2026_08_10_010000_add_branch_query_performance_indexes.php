<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['status', 'created_at', 'id'], 'transactions_status_created_id_idx');
            $table->index(['branch_id', 'status', 'created_at', 'id'], 'transactions_branch_status_created_id_idx');
            $table->index(['branch_id', 'created_at', 'id'], 'transactions_branch_created_id_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->index(['branch_id', 'type', 'created_at', 'id'], 'stock_logs_branch_type_created_id_idx');
            $table->index(['branch_id', 'created_at', 'id'], 'stock_logs_branch_created_id_idx');
        });

        Schema::table('daily_stock_sessions', function (Blueprint $table) {
            $table->index(['branch_id', 'session_date', 'status'], 'daily_stock_branch_date_status_idx');
        });

        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->index(['branch_id', 'type', 'entry_date'], 'cashflow_branch_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->dropIndex('cashflow_branch_type_date_idx');
        });

        Schema::table('daily_stock_sessions', function (Blueprint $table) {
            $table->dropIndex('daily_stock_branch_date_status_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropIndex('stock_logs_branch_type_created_id_idx');
            $table->dropIndex('stock_logs_branch_created_id_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_status_created_id_idx');
            $table->dropIndex('transactions_branch_status_created_id_idx');
            $table->dropIndex('transactions_branch_created_id_idx');
        });
    }
};
