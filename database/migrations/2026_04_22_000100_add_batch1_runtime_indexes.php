<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('daily_stock_sessions', function (Blueprint $table) {
            $table->index(['session_date', 'id'], 'daily_stock_sessions_date_id_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->index('created_at', 'stock_logs_created_at_idx');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['created_at', 'id'], 'transactions_created_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_created_id_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropIndex('stock_logs_created_at_idx');
        });

        Schema::table('daily_stock_sessions', function (Blueprint $table) {
            $table->dropIndex('daily_stock_sessions_date_id_idx');
        });
    }
};

