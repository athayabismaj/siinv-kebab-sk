<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Status transaksi: SUCCESS (default) atau VOID
            $table->string('status', 20)->default('SUCCESS')->after('change_amount');

            // FK ke sesi kasir harian agar void bisa diverifikasi per-sesi
            $table->unsignedBigInteger('daily_stock_session_id')->nullable()->after('status');

            // Index untuk query agregat saldo kasir
            $table->index(['user_id', 'status', 'created_at']);
            $table->index('daily_stock_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status', 'created_at']);
            $table->dropIndex(['daily_stock_session_id']);
            $table->dropColumn(['status', 'daily_stock_session_id']);
        });
    }
};
