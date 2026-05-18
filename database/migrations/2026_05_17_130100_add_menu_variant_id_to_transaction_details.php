<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            // Menyimpan variant_id agar void bisa melacak resep bahan baku yang benar
            $table->unsignedBigInteger('menu_variant_id')->nullable()->after('menu_id');
            $table->index('menu_variant_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropIndex(['menu_variant_id']);
            $table->dropColumn('menu_variant_id');
        });
    }
};
