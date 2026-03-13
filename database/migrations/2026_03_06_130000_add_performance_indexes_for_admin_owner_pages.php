<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('created_at', 'transactions_created_at_idx');
            $table->index(['payment_method_id', 'created_at'], 'transactions_payment_created_idx');
            $table->index(['user_id', 'created_at'], 'transactions_user_created_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->index(['ingredient_id', 'created_at'], 'stock_logs_ingredient_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_created_at_idx');
            $table->dropIndex('transactions_payment_created_idx');
            $table->dropIndex('transactions_user_created_idx');
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropIndex('stock_logs_ingredient_created_idx');
        });
    }
};
