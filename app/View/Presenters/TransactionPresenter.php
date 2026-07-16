<?php

namespace App\View\Presenters;

final class TransactionPresenter
{
    public function present(?string $status, ?string $paymentMethodName, ?string $voidReason): TransactionPresentation
    {
        $statusKey = $this->normalizeStatus($status);
        $tone = $this->statusTone($statusKey);

        return new TransactionPresentation(
            statusKey: $statusKey,
            statusLabel: $this->statusLabel($status),
            isSuccess: $statusKey === 'success',
            isVoid: $statusKey === 'void',
            paymentLabel: $this->paymentLabel($paymentMethodName),
            voidReasonLabel: $this->voidReasonLabel($voidReason),
            tone: $tone,
            badgeClass: $this->badgeClass($tone),
            dotClass: $this->dotClass($tone),
            haloClass: $this->haloClass($tone),
            iconWrapClass: $this->iconWrapClass($tone),
            iconClass: $this->iconClass($tone),
            detailBadgeClass: $this->detailBadgeClass($tone),
            detailDotClass: $this->detailDotClass($tone),
        );
    }

    public function normalizeStatus(?string $status): string
    {
        return strtolower(trim($status ?? 'success'));
    }

    public function statusLabel(?string $status): string
    {
        return match ($statusKey = $this->normalizeStatus($status)) {
            'success' => 'Berhasil',
            'void' => 'Dibatalkan',
            default => ucwords(str_replace('_', ' ', $statusKey)),
        };
    }

    public function paymentLabel(?string $paymentMethodName): string
    {
        $value = trim((string) $paymentMethodName);

        if ($value === '') {
            return '-';
        }

        return in_array(strtolower($value), ['cash', 'tunai'], true) ? 'Tunai' : $value;
    }

    public function voidReasonLabel(?string $voidReason): ?string
    {
        return match (strtolower(trim((string) $voidReason))) {
            'restock', 'kembali_stok', 'kembali stok' => 'Kembali ke Stok',
            'waste' => 'Bahan Terbuang',
            'input_error' => 'Kesalahan Input',
            'customer_cancel' => 'Pembatalan Pesanan',
            'other', 'lainnya' => 'Lainnya',
            default => null,
        };
    }

    public function statusTone(string $statusKey): string
    {
        return match ($statusKey) {
            'success' => 'success',
            'void' => 'warning',
            default => 'danger',
        };
    }

    private function badgeClass(string $tone): string
    {
        return match ($tone) {
            'success' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/25',
            'warning' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/25',
            default => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/25',
        };
    }

    private function dotClass(string $tone): string
    {
        return match ($tone) {
            'success' => 'bg-emerald-500',
            'warning' => 'bg-amber-500',
            default => 'bg-rose-500',
        };
    }

    private function haloClass(string $tone): string
    {
        return match ($tone) {
            'success' => 'bg-emerald-500/5 dark:bg-emerald-400/5',
            'warning' => 'bg-amber-500/5 dark:bg-amber-400/5',
            default => 'bg-red-500/5 dark:bg-red-400/5',
        };
    }

    private function iconWrapClass(string $tone): string
    {
        return match ($tone) {
            'success' => 'bg-emerald-50 dark:bg-emerald-900/20',
            'warning' => 'bg-amber-50 dark:bg-amber-900/20',
            default => 'bg-red-50 dark:bg-red-900/20',
        };
    }

    private function iconClass(string $tone): string
    {
        return match ($tone) {
            'success' => 'text-emerald-600 dark:text-emerald-400',
            'warning' => 'text-amber-600 dark:text-amber-400',
            default => 'text-red-600 dark:text-red-400',
        };
    }

    private function detailBadgeClass(string $tone): string
    {
        return match ($tone) {
            'success' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
            'warning' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
            default => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        };
    }

    private function detailDotClass(string $tone): string
    {
        return match ($tone) {
            'success' => 'bg-emerald-500',
            'warning' => 'bg-amber-500',
            default => 'bg-red-500',
        };
    }
}
