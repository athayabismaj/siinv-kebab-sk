<?php

namespace App\Support;

use App\Models\StockLog;
use Carbon\Carbon;

class StockLogView
{
    public static function normalizePeriod(?string $period): string
    {
        return in_array($period, ['daily', 'weekly', 'monthly'], true) ? $period : 'daily';
    }

    public static function parseSelectedDate(?string $date): Carbon
    {
        try {
            return $date ? Carbon::parse($date)->startOfDay() : now()->startOfDay();
        } catch (\Throwable $e) {
            return now()->startOfDay();
        }
    }

    public static function resolveRange(string $period, Carbon $selectedDate): array
    {
        if ($period === 'weekly') {
            return [$selectedDate->copy()->startOfWeek(), $selectedDate->copy()->endOfWeek()];
        }

        if ($period === 'monthly') {
            return [$selectedDate->copy()->startOfMonth(), $selectedDate->copy()->endOfMonth()];
        }

        return [$selectedDate->copy()->startOfDay(), $selectedDate->copy()->endOfDay()];
    }

    public static function dateDisplay(string $period, Carbon $selectedDate, Carbon $rangeStart, Carbon $rangeEnd): string
    {
        if ($period === 'weekly') {
            return $rangeStart->translatedFormat('d M Y') . ' - ' . $rangeEnd->translatedFormat('d M Y');
        }

        if ($period === 'monthly') {
            return $rangeStart->translatedFormat('F Y');
        }

        return $selectedDate->translatedFormat('d M Y');
    }

    public static function navigationDate(string $period, Carbon $selectedDate, string $direction): Carbon
    {
        $date = $selectedDate->copy();
        $isNext = $direction === 'next';

        if ($period === 'weekly') {
            return $isNext ? $date->addWeek() : $date->subWeek();
        }

        if ($period === 'monthly') {
            return $isNext ? $date->addMonth() : $date->subMonth();
        }

        return $isNext ? $date->addDay() : $date->subDay();
    }

    public static function summaryCards(array $summary): array
    {
        return [
            [
                'label' => 'Total Log',
                'value' => (int) ($summary['total'] ?? 0),
                'bar_class' => 'bg-slate-500/20',
            ],
            [
                'label' => 'Restok',
                'value' => (int) ($summary['restock'] ?? 0),
                'bar_class' => 'bg-emerald-500/20',
            ],
            [
                'label' => 'Pemakaian',
                'value' => (int) ($summary['usage'] ?? 0),
                'bar_class' => 'bg-rose-500/20',
            ],
            [
                'label' => 'Penyesuaian',
                'value' => (int) ($summary['adjustment'] ?? 0),
                'bar_class' => 'bg-amber-500/20',
            ],
        ];
    }

    public static function typeTabs(?string $activeType): array
    {
        return [
            [
                'key' => null,
                'label' => 'Semua Log',
                'active' => ! $activeType,
                'dot_class' => null,
                'active_class' => 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400',
            ],
            [
                'key' => StockLogTypeMap::TAB_RESTOCK,
                'label' => 'Restok',
                'active' => $activeType === StockLogTypeMap::TAB_RESTOCK,
                'dot_class' => 'bg-emerald-500',
                'active_class' => 'border-emerald-500 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400',
            ],
            [
                'key' => StockLogTypeMap::TAB_USAGE,
                'label' => 'Pemakaian',
                'active' => $activeType === StockLogTypeMap::TAB_USAGE,
                'dot_class' => 'bg-rose-500',
                'active_class' => 'border-rose-500 text-rose-600 dark:border-rose-400 dark:text-rose-400',
            ],
            [
                'key' => StockLogTypeMap::TAB_ADJUSTMENT,
                'label' => 'Penyesuaian',
                'active' => $activeType === StockLogTypeMap::TAB_ADJUSTMENT,
                'dot_class' => 'bg-amber-500',
                'active_class' => 'border-amber-500 text-amber-600 dark:border-amber-400 dark:text-amber-400',
            ],
        ];
    }

    public static function decorate(StockLog $log): StockLog
    {
        $rawQty = (float) $log->quantity;
        $displayUnit = strtolower(trim((string) ($log->ingredient->display_unit ?? $log->ingredient->base_unit ?? '')));
        $qtyDisplay = in_array($displayUnit, ['kg', 'l'], true) ? $rawQty / 1000 : $rawQty;

        $formattedQty = self::formatNumber(abs($qtyDisplay));

        $packText = null;
        if ($displayUnit === 'pcs') {
            $packSize = max(1, (int) ($log->ingredient->pack_size ?? 1));
            if ($packSize > 1) {
                $packText = self::formatNumber(abs($qtyDisplay) / $packSize) . ' pack';
            }
        }

        $typeConfig = self::typeConfig($log);

        $log->display_unit = $displayUnit;
        $log->display_qty = $formattedQty;
        $log->display_pack_text = $packText;
        $log->display_type_label = $typeConfig['label'];
        $log->display_qty_prefix = $typeConfig['prefix'];
        $log->display_source = $typeConfig['source'];
        $log->display_type_badge_class = $typeConfig['badge_class'];
        $log->display_type_dot_class = $typeConfig['dot_class'];
        $log->display_qty_text_class = $typeConfig['qty_class'];
        $log->group_date = $log->created_at->toDateString();

        return $log;
    }

    public static function exportQuantity(StockLog $log): string
    {
        $rawQty = (float) $log->quantity;
        $displayUnit = strtolower(trim((string) ($log->ingredient->display_unit ?? $log->ingredient->base_unit ?? '')));
        $qtyDisplay = in_array($displayUnit, ['kg', 'l'], true) ? $rawQty / 1000 : $rawQty;
        $formattedQty = number_format($qtyDisplay, 2, '.', '');
        $packSuffix = '';

        if ($displayUnit === 'pcs') {
            $packSize = max(1, (int) ($log->ingredient->pack_size ?? 1));
            if ($packSize > 1) {
                $packSuffix = ' (' . self::formatNumber($qtyDisplay / $packSize) . ' pack)';
            }
        }

        return $formattedQty . ' ' . $displayUnit . $packSuffix;
    }

    public static function typeConfig(StockLog $log): array
    {
        $rawQty = (float) $log->quantity;

        return match ($log->type) {
            'in' => [
                'label' => 'Restok',
                'prefix' => '+',
                'source' => 'Manual Restok',
                'badge_class' => 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                'dot_class' => 'bg-emerald-500',
                'qty_class' => 'text-emerald-600 dark:text-emerald-400',
            ],
            'adjustment' => [
                'label' => 'Penyesuaian',
                'prefix' => $rawQty >= 0 ? '+' : '-',
                'source' => 'Manual Adjust',
                'badge_class' => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                'dot_class' => 'bg-amber-500',
                'qty_class' => 'text-amber-600 dark:text-amber-400',
            ],
            'transfer_daily' => [
                'label' => 'Transfer Harian',
                'prefix' => '-',
                'source' => 'Gudang -> Stok Harian',
                'badge_class' => 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20',
                'dot_class' => 'bg-indigo-500',
                'qty_class' => 'text-indigo-600 dark:text-indigo-400',
            ],
            'daily_usage' => [
                'label' => 'Pemakaian Harian',
                'prefix' => '-',
                'source' => 'Stok Harian Kasir',
                'badge_class' => 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20',
                'dot_class' => 'bg-rose-500',
                'qty_class' => 'text-rose-600 dark:text-rose-400',
            ],
            'daily_return' => [
                'label' => 'Pengembalian Harian',
                'prefix' => '+',
                'source' => 'Stok Harian -> Gudang',
                'badge_class' => 'bg-cyan-50 text-cyan-600 border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:border-cyan-500/20',
                'dot_class' => 'bg-cyan-500',
                'qty_class' => 'text-cyan-600 dark:text-cyan-400',
            ],
            'out' => [
                'label' => 'Pemakaian',
                'prefix' => '-',
                'source' => $log->reference_id ? 'TRX-' . $log->reference_id : 'Transaksi',
                'badge_class' => 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20',
                'dot_class' => 'bg-rose-500',
                'qty_class' => 'text-rose-600 dark:text-rose-400',
            ],
            default => [
                'label' => 'Unknown',
                'prefix' => '',
                'source' => '-',
                'badge_class' => 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-slate-500/10 dark:text-slate-400 dark:border-slate-500/20',
                'dot_class' => 'bg-slate-500',
                'qty_class' => 'text-slate-600 dark:text-slate-400',
            ],
        };
    }

    private static function formatNumber(float $value): string
    {
        $trimmed = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $trimmed === '' ? '0' : $trimmed;
    }
}
