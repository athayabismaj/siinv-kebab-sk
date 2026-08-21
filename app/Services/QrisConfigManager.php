<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\QrisConfig;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QrisConfigManager
{
    public function __construct(private readonly QrisService $qrisService) {}

    public function replace(Branch $branch, string $payload, User $actor): QrisConfig
    {
        $validation = $this->qrisService->validate($payload);
        if (! $validation['valid']) {
            throw new InvalidArgumentException(implode(' ', $validation['errors']));
        }

        return DB::transaction(function () use ($branch, $payload, $actor, $validation) {
            Branch::query()->whereKey($branch->id)->lockForUpdate()->firstOrFail();
            $now = now();

            QrisConfig::query()
                ->where('branch_id', $branch->id)
                ->active()
                ->lockForUpdate()
                ->update([
                    'is_active' => false,
                    'deactivated_at' => $now,
                    'updated_by' => $actor->id,
                    'updated_at' => $now,
                ]);

            return QrisConfig::query()->create([
                'branch_id' => $branch->id,
                'merchant_name' => $validation['merchant_name'],
                'merchant_display_name' => $this->resolveTruncatedMerchantName(
                    (string) $validation['merchant_name'],
                    (string) $branch->name,
                ),
                'merchant_city' => $validation['merchant_city'],
                'qris_payload' => trim($payload),
                'is_active' => true,
                'activated_at' => $now,
                'deactivated_at' => null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        }, 3);
    }

    public function activate(QrisConfig $config, User $actor): QrisConfig
    {
        $validation = $this->qrisService->validate((string) $config->qris_payload);
        if (! $validation['valid']) {
            throw new InvalidArgumentException('Konfigurasi QRIS lama tidak lagi valid.');
        }

        return DB::transaction(function () use ($config, $actor) {
            Branch::query()->whereKey($config->branch_id)->lockForUpdate()->firstOrFail();
            $config = QrisConfig::query()->whereKey($config->id)->lockForUpdate()->firstOrFail();
            $now = now();

            QrisConfig::query()
                ->where('branch_id', $config->branch_id)
                ->whereKeyNot($config->id)
                ->active()
                ->update([
                    'is_active' => false,
                    'deactivated_at' => $now,
                    'updated_by' => $actor->id,
                    'updated_at' => $now,
                ]);

            $config->forceFill([
                'is_active' => true,
                'activated_at' => $now,
                'deactivated_at' => null,
                'updated_by' => $actor->id,
            ])->save();

            return $config;
        }, 3);
    }

    public function deactivate(QrisConfig $config, User $actor): QrisConfig
    {
        return DB::transaction(function () use ($config, $actor) {
            Branch::query()->whereKey($config->branch_id)->lockForUpdate()->firstOrFail();
            $config = QrisConfig::query()->whereKey($config->id)->lockForUpdate()->firstOrFail();

            if ($config->is_active) {
                $config->forceFill([
                    'is_active' => false,
                    'deactivated_at' => now(),
                    'updated_by' => $actor->id,
                ])->save();
            }

            return $config;
        }, 3);
    }

    public function resolveTruncatedMerchantName(string $merchantName, string $branchName): ?string
    {
        $merchantName = trim($merchantName);
        $branchName = trim($branchName);

        if ($branchName === '' || preg_match('/(?:\.{3,}|…)\s*$/u', $merchantName) !== 1) {
            return null;
        }

        $prefix = trim((string) preg_replace('/(?:\s+\S*)?(?:\.{3,}|…)\s*$/u', '', $merchantName));
        if ($prefix === '') {
            return $branchName;
        }

        if (str_contains(mb_strtolower($prefix), mb_strtolower($branchName))) {
            return $prefix;
        }

        return $prefix.' '.$branchName;
    }
}
