<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qris_configs', function (Blueprint $table) {
            $table->string('merchant_display_name', 255)->nullable()->after('merchant_name');
        });

        DB::table('qris_configs')
            ->join('branches', 'branches.id', '=', 'qris_configs.branch_id')
            ->select('qris_configs.id', 'qris_configs.merchant_name', 'branches.name as branch_name')
            ->orderBy('qris_configs.id')
            ->each(function (object $config): void {
                $merchantName = trim((string) $config->merchant_name);
                $branchName = trim((string) $config->branch_name);

                if ($branchName === '' || preg_match('/(?:\.{3,}|…)\s*$/u', $merchantName) !== 1) {
                    return;
                }

                $prefix = trim((string) preg_replace('/(?:\s+\S*)?(?:\.{3,}|…)\s*$/u', '', $merchantName));
                $displayName = $prefix === '' || str_contains(mb_strtolower($prefix), mb_strtolower($branchName))
                    ? ($prefix ?: $branchName)
                    : $prefix.' '.$branchName;

                DB::table('qris_configs')->where('id', $config->id)->update([
                    'merchant_display_name' => $displayName,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('qris_configs', function (Blueprint $table) {
            $table->dropColumn('merchant_display_name');
        });
    }
};
