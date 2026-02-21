<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    /*** Run the migrations. ***/
    public function up(): void {
        Schema::create('menu_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')
                ->constrained('menus')
                ->restrictOnDelete();
            $table->foreignId('ingredient_id')
                ->constrained('ingredients')
                ->restrictOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->timestamps();   
            $table->unique(['menu_id', 'ingredient_id']);
        });
    }

    /*** Reverse the migrations. ***/
    public function down(): void {
        Schema::dropIfExists('menu_ingredients');
    }
};
