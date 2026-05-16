<?php

namespace App\Contracts\Services;

use App\Models\DailyStockSession;

interface CloseSessionServiceInterface
{
    /**
     * Menutup sesi kasir harian dengan validasi finansial dan inventaris.
     *
     * HUKUM MUTLAK BAGI IMPLEMENTATOR:
     * 1. WAJIB menggunakan DB::transaction() dan lockForUpdate() pada row sesi.
     * 2. WAJIB melempar Exception jika status sesi sudah 'CLOSED' (SessionAlreadyClosedException).
     * 3. WAJIB melempar Exception jika terdapat daily_stock_items dengan actual_stock NULL (UnreconciledInventoryException).
     * 4. WAJIB menghitung $systemCalculatedCash dengan rumus: opening_balance + successCash - expenses.
     *    Transaksi VOID sudah ter-exclude oleh filter status = 'SUCCESS', DILARANG menguranginya lagi.
     * 5. WAJIB mencatat audit trail otomatis jika variance melebihi batas toleransi.
     *
     * @param int|string $sessionId
     * @param float $actualCash Uang fisik riil yang dihitung kasir
     * @param int $closedBy ID User yang melakukan penutupan
     * @return DailyStockSession
     * @throws \Exception
     */
    public function closeSession(int|string $sessionId, float $actualCash, int $closedBy): DailyStockSession;
}
