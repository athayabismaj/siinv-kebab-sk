<?php

namespace App\Services;

use App\Contracts\Services\CloseSessionServiceInterface;
use App\Models\DailyStockSession;
use App\Models\Transaction;
use App\Models\CashflowEntry;
use Illuminate\Support\Facades\DB;
use Exception;

class CloseSessionService implements CloseSessionServiceInterface
{
    /**
     * Executes the closing of a daily stock session with strict financial and inventory validation.
     *
     * @param int|string $sessionId
     * @param float $actualCash Uang fisik yang dihitung riil oleh kasir
     * @param int $closedBy ID User yang melakukan penutupan
     * @return DailyStockSession
     * @throws Exception
     */
    public function closeSession(int|string $sessionId, float $actualCash, int $closedBy): DailyStockSession
    {
        return DB::transaction(function () use ($sessionId, $actualCash, $closedBy) {
            
            // 1. Pessimistic Locking untuk mencegah race condition (misal: tombol Ditekan 2x)
            $session = DailyStockSession::lockForUpdate()->findOrFail($sessionId);

            // 2. Fail-Fast 1: Verifikasi Status
            if ($session->status === 'CLOSED') {
                throw new Exception("SessionAlreadyClosedException: Sesi kasir ini sudah ditutup sebelumnya pada {$session->closed_at}.");
            }

            // 3. Fail-Fast 2: Validasi Stok F&B (Hanging Inventory)
            // Memastikan tidak ada item yang belum dihitung fisik akhirnya oleh kasir.
            $unreconciledItems = $session->items()->whereNull('actual_stock')->exists();
            if ($unreconciledItems) {
                throw new Exception("UnreconciledInventoryException: Tidak dapat menutup shift. Ada bahan baku yang belum dihitung sisa fisik akhirnya.");
            }

            // 4. Opening Balance
            $openingBalance = (float) ($session->opening_balance ?? 0);

            // 5. Kalkulasi Agregat Mutlak (Relasi Absolut via Foreign Key)
            // a. DITAMBAH uang tunai dari transaksi SUKSES saja.
            //    Transaksi VOID secara logis sudah ter-exclude oleh filter status = 'SUCCESS'.
            //    Menguranginya lagi akan menyebabkan pengurangan ganda (double subtraction).
            $successCash = (float) Transaction::where('daily_stock_session_id', $session->id)
                ->where('status', 'SUCCESS')
                ->whereHas('paymentMethod', function ($q) {
                    $q->whereRaw('LOWER(name) = ?', ['cash']);
                })
                ->sum('total_amount');

            // b. DIKURANGI pengeluaran operasional (dari tabel cashflow_entries)
            $expenses = (float) CashflowEntry::where('daily_stock_session_id', $session->id)
                ->where('type', 'expense')
                ->sum('amount');

            // 6. Rumus Saldo Sistem (Dikoreksi: Opening + Success Cash - Expenses)
            $systemCalculatedCash = $openingBalance + $successCash - $expenses;

            // 7. Hitung Variance (Fisik vs Sistem)
            $cashVariance = $actualCash - $systemCalculatedCash;

            // 8. Smart Audit Trail untuk Variance Ekstrem
            // Apabila selisih kas fisik vs sistem tidak wajar secara absolut (> Rp 50.000)
            if (abs($cashVariance) > 50000) {
                $session->notes = trim($session->notes . "\n[SYSTEM WARNING] Selisih kas melebihi batas toleransi.");
            }

            // 9. Update Row Sesi
            $session->closed_at = now();
            $session->status = 'CLOSED';
            $session->closed_by = $closedBy;
            $session->system_cash = $systemCalculatedCash;
            $session->actual_cash = $actualCash;
            $session->cash_variance = $cashVariance;
            
            $session->save();

            return $session;
        });
    }
}
