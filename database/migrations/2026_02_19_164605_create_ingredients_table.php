<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    /*** Run the migrations. ***/
    public function up(): void {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('unit', 50);
            $table->decimal('stock', 12, 2)->default(0);
            $table->decimal('minimum_stock', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /*** Reverse the migrations. ***/
    public function down(): void {
        Schema::dropIfExists('ingredients');
    }
};
