<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('voided_by')
                ->nullable()
                ->after('daily_stock_session_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->string('void_reason', 30)->nullable()->after('voided_at');

            $table->index(['status', 'voided_at'], 'transactions_status_voided_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_status_voided_at_idx');
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });
    }
};
