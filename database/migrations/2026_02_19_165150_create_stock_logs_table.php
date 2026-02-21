<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /*** Run the migrations. ***/
    public function up(): void {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')
                ->constrained('ingredients')
                ->restrictOnDelete();
            $table->enum('type', ['in','out','adjustment']);
            $table->decimal('quantity', 12, 2);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /** * Reverse the migrations. ***/
    public function down(): void {
        Schema::dropIfExists('stock_logs');
    }
};
