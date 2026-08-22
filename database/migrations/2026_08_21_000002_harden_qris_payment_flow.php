<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('payment_confirmed_at')->nullable()->after('status');
            $table->foreignId('payment_confirmed_by')->nullable()->after('payment_confirmed_at')
                ->constrained('users')->nullOnDelete();
            $table->string('payment_confirmation_source', 30)->nullable()->after('payment_confirmed_by');
            $table->string('payment_provider_reference', 100)->nullable()->after('payment_confirmation_source');
        });

        Schema::create('qris_payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('reference', 40)->unique();
            $table->decimal('amount', 14, 2);
            $table->char('payload_hash', 64);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('generated_at');
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('confirmation_source', 30)->nullable();
            $table->string('provider_reference', 100)->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status'], 'qris_attempt_transaction_status_idx');
            $table->index(['status', 'expires_at'], 'qris_attempt_status_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qris_payment_attempts');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_confirmed_by');
            $table->dropColumn([
                'payment_confirmed_at',
                'payment_confirmation_source',
                'payment_provider_reference',
            ]);
        });
    }
};
