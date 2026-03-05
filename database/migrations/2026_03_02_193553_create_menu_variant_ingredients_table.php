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
        Schema::create('menu_variant_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_variant_id')
                ->constrained('menu_variants')
                ->cascadeOnDelete();
            $table->foreignId('ingredient_id')
                ->constrained('ingredients')
                ->cascadeOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->timestamps();
            $table->unique(['menu_variant_id', 'ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_variant_ingredients');
    }
};
