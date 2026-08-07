<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_stock_sessions', function (Blueprint $table) {
            $table->boolean('stock_retained_at_outlet')
                ->default(false)
                ->after('status');
            $table->foreignId('carry_forward_source_session_id')
                ->nullable()
                ->after('stock_retained_at_outlet')
                ->constrained('daily_stock_sessions')
                ->nullOnDelete();
        });

        Schema::table('daily_stock_items', function (Blueprint $table) {
            $table->decimal('carry_forward_qty', 12, 2)
                ->default(0)
                ->after('ingredient_id');
            $table->decimal('opening_adjustment_qty', 12, 2)
                ->default(0)
                ->after('carry_forward_qty');
            $table->decimal('transferred_qty', 12, 2)
                ->default(0)
                ->after('opening_adjustment_qty');
            $table->timestamp('carry_forward_reconciled_at')
                ->nullable()
                ->after('returned_qty');
            $table->foreignId('carry_forward_reconciled_by')
                ->nullable()
                ->after('carry_forward_reconciled_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Sebelum fitur carry-forward, seluruh opening_qty berasal dari transfer gudang.
        DB::table('daily_stock_items')->update([
            'transferred_qty' => DB::raw('opening_qty'),
        ]);

        Schema::create('daily_stock_opening_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_stock_session_id')
                ->constrained('daily_stock_sessions')
                ->cascadeOnDelete();
            $table->foreignId('daily_stock_item_id')
                ->constrained('daily_stock_items')
                ->cascadeOnDelete();
            $table->foreignId('ingredient_id')
                ->constrained('ingredients')
                ->restrictOnDelete();
            $table->decimal('expected_qty', 12, 2);
            $table->decimal('actual_qty', 12, 2);
            $table->decimal('difference_qty', 12, 2);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(
                ['daily_stock_session_id', 'ingredient_id'],
                'daily_stock_opening_adjustment_session_ingredient_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_stock_opening_adjustments');

        Schema::table('daily_stock_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carry_forward_reconciled_by');
            $table->dropColumn([
                'carry_forward_qty',
                'opening_adjustment_qty',
                'transferred_qty',
                'carry_forward_reconciled_at',
            ]);
        });

        Schema::table('daily_stock_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('carry_forward_source_session_id');
            $table->dropColumn('stock_retained_at_outlet');
        });
    }
};
