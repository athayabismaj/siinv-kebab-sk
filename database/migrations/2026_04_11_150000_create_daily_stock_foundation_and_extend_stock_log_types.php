<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_stock_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('session_date');
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['session_date', 'cashier_id'], 'daily_stock_session_date_cashier_unique');
            $table->index(['status', 'session_date'], 'daily_stock_status_date_idx');
            $table->index('cashier_id', 'daily_stock_cashier_idx');
        });

        Schema::create('daily_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_stock_session_id')
                ->constrained('daily_stock_sessions')
                ->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->decimal('opening_qty', 12, 2)->default(0);
            $table->decimal('remaining_qty', 12, 2)->default(0);
            $table->decimal('used_qty', 12, 2)->default(0);
            $table->decimal('returned_qty', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['daily_stock_session_id', 'ingredient_id'], 'daily_stock_item_session_ingredient_unique');
            $table->index(['ingredient_id', 'created_at'], 'daily_stock_item_ingredient_created_idx');
        });

        $this->extendStockLogTypeValues();
    }

    public function down(): void
    {
        $this->rollbackStockLogTypeValues();

        Schema::dropIfExists('daily_stock_items');
        Schema::dropIfExists('daily_stock_sessions');
    }

    private function extendStockLogTypeValues(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            $constraints = DB::select(<<<'SQL'
                SELECT con.conname
                FROM pg_constraint con
                JOIN pg_class rel ON rel.oid = con.conrelid
                JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
                JOIN pg_attribute att ON att.attrelid = rel.oid AND att.attnum = ANY(con.conkey)
                WHERE rel.relname = 'stock_logs'
                  AND nsp.nspname = current_schema()
                  AND con.contype = 'c'
                  AND att.attname = 'type'
            SQL);

            foreach ($constraints as $constraint) {
                DB::statement('ALTER TABLE stock_logs DROP CONSTRAINT IF EXISTS "' . $constraint->conname . '"');
            }

            DB::statement(
                "ALTER TABLE stock_logs ADD CONSTRAINT stock_logs_type_check " .
                "CHECK (type IN ('in','out','adjustment','transfer_daily','daily_usage','daily_return'))"
            );

            return;
        }

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE stock_logs MODIFY type ENUM(" .
                "'in','out','adjustment','transfer_daily','daily_usage','daily_return'" .
                ") NOT NULL"
            );
        }
    }

    private function rollbackStockLogTypeValues(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE stock_logs DROP CONSTRAINT IF EXISTS stock_logs_type_check');
            DB::statement(
                "ALTER TABLE stock_logs ADD CONSTRAINT stock_logs_type_check " .
                "CHECK (type IN ('in','out','adjustment'))"
            );

            return;
        }

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE stock_logs MODIFY type ENUM('in','out','adjustment') NOT NULL"
            );
        }
    }
};

