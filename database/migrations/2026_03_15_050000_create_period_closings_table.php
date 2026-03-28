<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('period_closings', function (Blueprint $table) {
            $table->id();
            $table->string('period_type'); // 'monthly' or 'yearly'
            $table->date('period_date');   // Start date of the period
            $table->decimal('total_revenue', 15, 2);
            $table->integer('total_transactions');
            $table->text('notes')->nullable();
            $table->foreignId('closed_by_user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['period_type', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_closings');
    }
};
