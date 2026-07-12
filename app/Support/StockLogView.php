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
                'label' => 'Total Riwayat',
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
                'label' => 'Pengembalian',
                'value' => (int) ($summary['return'] ?? 0),
                'bar_class' => 'bg-cyan-500/20',
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
                'label' => 'Semua Riwayat',
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
                'key' => StockLogTypeMap::TAB_RETURN,
                'label' => 'Pengembalian',
                'active' => $activeType === StockLogTypeMap::TAB_RETURN,
                'dot_class' => 'bg-cyan-500',
                'active_class' => 'border-cyan-500 text-cyan-600 dark:border-cyan-400 dark:text-cyan-400',
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
        $unitLabel = self::normalizeDisplayUnit($displayUnit);
        $qtyDisplay = $rawQty;

        $formattedQty = self::formatNumber(abs($qtyDisplay));

        $packText = null;
        if ($unitLabel === 'pcs') {
            $packSize = max(1, (int) ($log->ingredient->pack_size ?? 1));
            if ($packSize > 1) {
                $packText = self::formatNumber(abs($qtyDisplay) / $packSize) . ' pak';
            }
        }

        $typeConfig = self::typeConfig($log);

        $log->display_unit = $unitLabel;
        $log->display_qty = $formattedQty;
        $log->display_pack_text = $packText;
        $log->display_type_label = $typeConfig['label'];
        $log->display_qty_prefix = $typeConfig['prefix'];
        $log->display_source = $typeConfig['source'];
        $log->display_note = self::displayNote($log);
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
        $unitLabel = self::normalizeDisplayUnit($displayUnit);
        $qtyDisplay = $rawQty;
        $formattedQty = self::formatNumber($qtyDisplay);
        $packSuffix = '';

        if ($unitLabel === 'pcs') {
            $packSize = max(1, (int) ($log->ingredient->pack_size ?? 1));
            if ($packSize > 1) {
                $packSuffix = ' (' . self::formatNumber($qtyDisplay / $packSize) . ' pak)';
            }
        }

        return $formattedQty . ' ' . $unitLabel . $packSuffix;
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
                'source' => 'Penyesuaian Manual',
                'badge_class' => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                'dot_class' => 'bg-amber-500',
                'qty_class' => 'text-amber-600 dark:text-amber-400',
            ],
            'transfer_daily' => [
                'label' => 'Pemakaian',
                'prefix' => '-',
                'source' => 'Gudang → Stok Harian',
                'badge_class' => 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20',
                'dot_class' => 'bg-indigo-500',
                'qty_class' => 'text-indigo-600 dark:text-indigo-400',
            ],
            'daily_usage' => [
                'label' => 'Pemakaian',
                'prefix' => '-',
                'source' => self::transactionCode($log)
                    ? 'Transaksi Penjualan'
                    : 'Stok Harian Kasir',
                'badge_class' => 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20',
                'dot_class' => 'bg-rose-500',
                'qty_class' => 'text-rose-600 dark:text-rose-400',
            ],
            'daily_return' => [
                'label' => 'Pengembalian Stok Harian',
                'prefix' => '+',
                'source' => self::isVoidReturn($log)
                    ? 'Pembatalan Transaksi'
                    : 'Stok Harian → Gudang',
                'badge_class' => 'bg-cyan-50 text-cyan-600 border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:border-cyan-500/20',
                'dot_class' => 'bg-cyan-500',
                'qty_class' => 'text-cyan-600 dark:text-cyan-400',
            ],
            'out' => [
                'label' => 'Pemakaian',
                'prefix' => '-',
                'source' => self::transactionCode($log)
                    ? 'Transaksi Penjualan'
                    : ($log->reference_id ? 'Transaksi #' . $log->reference_id : 'Transaksi'),
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
        $trimmed = rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
        return $trimmed === '' ? '0' : $trimmed;
    }

    private static function normalizeDisplayUnit(string $unit): string
    {
        $unit = strtolower(trim($unit));

        return match ($unit) {
            'kg', 'g', 'gr', 'gram' => 'g',
            'l', 'ml', 'milliliter', 'mililiter' => 'ml',
            'pack', 'pak' => 'pak',
            'pcs', 'pc', 'piece', 'pieces' => 'pcs',
            default => $unit !== '' ? $unit : 'unit',
        };
    }

    private static function displayNote(StockLog $log): string
    {
        $note = trim((string) ($log->note ?? ''));
        $transactionCode = self::transactionCode($log);

        if ($log->type === 'daily_usage' && $transactionCode) {
            return 'Pemakaian bahan dari transaksi ' . $transactionCode;
        }

        if ($log->type === 'daily_return' && self::isVoidReturn($log)) {
            return 'Pengembalian stok dari pembatalan transaksi ' . ($transactionCode ?: self::extractTransactionCode($note));
        }

        if ($note === '') {
            return '-';
        }

        $note = str_replace('RESTOCK to Daily Session from Void Transaction', 'Pengembalian stok dari pembatalan transaksi', $note);
        $note = str_replace('Void Transaction', 'Pembatalan Transaksi', $note);
        $note = str_replace('Daily Session', 'Stok Harian', $note);
        $note = str_replace('Warehouse', 'Gudang', $note);
        $note = str_replace('Adjustment', 'Penyesuaian', $note);
        $note = str_replace('Manual Adjust', 'Penyesuaian Manual', $note);

        return $note;
    }

    private static function transactionCode(StockLog $log): ?string
    {
        $note = strtolower((string) ($log->note ?? ''));
        $looksLikeTransaction = $log->type === 'out'
            || str_contains($note, 'transaksi')
            || str_contains($note, 'transaction');

        if (! $looksLikeTransaction) {
            return null;
        }

        return $log->relationLoaded('referenceTransaction')
            ? ($log->referenceTransaction?->transaction_code ?: null)
            : null;
    }

    private static function isVoidReturn(StockLog $log): bool
    {
        $note = strtolower((string) ($log->note ?? ''));

        return $log->type === 'daily_return'
            && (str_contains($note, 'void transaction') || str_contains($note, 'pembatalan transaksi'));
    }

    private static function extractTransactionCode(string $note): string
    {
        if (preg_match('/TRX-[A-Z0-9-]+/i', $note, $matches)) {
            return strtoupper($matches[0]);
        }

        return '';
    }
}
