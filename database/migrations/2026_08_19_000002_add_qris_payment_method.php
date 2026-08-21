<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        $this->ensurePaymentMethod('QRIS');
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods') || ! Schema::hasTable('transactions')) {
            return;
        }

        $qrisId = DB::table('payment_methods')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['qris'])
            ->value('id');

        if ($qrisId && ! DB::table('transactions')->where('payment_method_id', $qrisId)->exists()) {
            DB::table('payment_methods')->where('id', $qrisId)->delete();
        }
    }

    private function ensurePaymentMethod(string $name): void
    {
        $existingId = DB::table('payment_methods')
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->value('id');

        if ($existingId) {
            DB::table('payment_methods')->where('id', $existingId)->update([
                'name' => $name,
                'deleted_at' => null,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('payment_methods')->insert([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
};
