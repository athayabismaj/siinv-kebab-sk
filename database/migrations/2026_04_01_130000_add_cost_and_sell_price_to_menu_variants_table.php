<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('menu_variants', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->default(0)->after('price');
            $table->decimal('sell_price', 12, 2)->default(0)->after('cost_price');
        });

        // Backfill agar data lama tetap konsisten.
        DB::table('menu_variants')->update([
            'sell_price' => DB::raw('price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('menu_variants', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'sell_price']);
        });
    }
};

