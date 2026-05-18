<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_logs', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke session harian agar owner bisa memfilter waste per shift
            $table->unsignedBigInteger('daily_stock_session_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->double('quantity');
            $table->decimal('cost_loss', 15, 2); // Nilai total modal yang hangus
            $table->string('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints untuk menjaga integritas data
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_logs');
    }
};