<?php

namespace App\Services;

use App\Contracts\Services\VoidTransactionServiceInterface;
use App\DTOs\VoidTransactionRequestDto;
use App\Enums\VoidInventoryActionEnum;
use App\Models\Transaction;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class VoidTransactionService implements VoidTransactionServiceInterface
{
    /**
     * Executes a transaction voiding process.
     *
     * {@inheritdoc}
     */
    public function voidTransaction(VoidTransactionRequestDto $requestDto): float
    {
        // 1. Validasi Idempotency (Fail-Fast)
        // Mencegah double-click pada network lambat. Atomic lock menggunakan cache.
        $idempotencyKey = 'void_tx_lock_' . $requestDto->idempotencyKey;
        if (!Cache::add($idempotencyKey, true, now()->addMinutes(5))) {
            throw new Exception("Idempotency conflict: Permintaan void untuk key {$requestDto->idempotencyKey} sedang diproses atau sudah selesai.");
        }

        try {
            // 2. Fetch transaksi & Validasi
            // Memastikan transaksi benar-benar ada.
            $transaction = Transaction::with('details.menu.recipes')->findOrFail($requestDto->transactionId);

            // Validasi belum di-void (mencegah void ganda secara stateful)
            if (isset($transaction->status) && $transaction->status === 'VOID') {
                throw new Exception("Validation failed: Transaksi {$requestDto->transactionId} sudah dalam status VOID.");
            }

            // 3. Otorisasi Instance-Level
            // Memeriksa apakah $actor berhak mem-void transaksi pada sesi tertentu.
            // Asumsi: Model User (actor) memiliki method role/permission checking, atau kita lemparkan Exception jika kondisi bisnis gagal.
            // Karena ini arsitektur POS, biasanya admin/owner yang bisa void, atau kasir di sesi miliknya sendiri.
            if (!$this->authorizeActor($requestDto->actor, $transaction, $requestDto->currentSessionId)) {
                throw new Exception("Unauthorized: Anda tidak memiliki otoritas untuk mem-void transaksi ini pada sesi kasir tersebut.");
            }

            DB::beginTransaction();

            // 4 & 5. Ambil semua ingredient_id & Urutkan secara Ascending (Hukum Anti-Deadlock)
            // Kita ekstraksi ingredient_id dari relasi Menu -> Recipe.
            $ingredientIds = collect([]);
            foreach ($transaction->details as $detail) {
                if ($detail->menu && $detail->menu->recipes) {
                    foreach ($detail->menu->recipes as $recipe) {
                        $ingredientIds->push($recipe->ingredient_id);
                    }
                }
            }

            // MENGURUTKAN ID SECARA ASCENDING (Hukum Mutlak Anti-Deadlock)
            $sortedIngredientIds = $ingredientIds->unique()->sort()->values()->toArray();

            // 6. Lock for Update: Baris Transaksi, DailyStockSession, dan Ingredients
            $lockedTransaction = Transaction::where('id', $requestDto->transactionId)->lockForUpdate()->firstOrFail();
            $lockedSession = DailyStockSession::where('id', $requestDto->currentSessionId)->lockForUpdate()->firstOrFail();
            
            $lockedIngredients = [];
            if (!empty($sortedIngredientIds)) {
                $lockedIngredients = Ingredient::whereIn('id', $sortedIngredientIds)
                    ->orderBy('id', 'asc') // Sort DB level just to be absolutely sure
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
            }

            // 7. RESTOCK atau WASTE stok
            // Loop detail transaksi untuk mengembalikan stok jika RESTOCK.
            if ($requestDto->inventoryAction === VoidInventoryActionEnum::RESTOCK) {
                foreach ($transaction->details as $detail) {
                    if ($detail->menu && $detail->menu->recipes) {
                        foreach ($detail->menu->recipes as $recipe) {
                            $ingId = $recipe->ingredient_id;
                            if (isset($lockedIngredients[$ingId])) {
                                // Kembalikan stok sesuai recipe quantity * ordered quantity
                                // Asumsi Ingredient memiliki field 'stock'
                                $qtyToReturn = $recipe->quantity * $detail->quantity;
                                $lockedIngredients[$ingId]->stock += $qtyToReturn;
                                $lockedIngredients[$ingId]->save();
                            }
                        }
                    }
                }
            } else if ($requestDto->inventoryAction === VoidInventoryActionEnum::WASTE) {
                // Biarkan stok hilang, tetapi catat kerugiannya.
                // Pencatatan bisa disimpan dalam field notes sesi kasir atau tabel loss log khusus.
                $lossAmount = $lockedTransaction->total_amount;
                $lockedSession->notes = trim($lockedSession->notes . "\n[WASTE LOG] Transaksi {$lockedTransaction->id} di-void sebagai WASTE. Nilai kerugian: {$lossAmount}");
            }

            // 8. Kurangi saldo laci (Refund ke pelanggan)
            // Menerapkan logika sesuai skema database sesungguhnya tanpa fallback isset().
            // Karena tabel daily_stock_sessions sesungguhnya TIDAK memiliki kolom finansial denormalisasi (seperti revenue/cash),
            // pengurangan kas tunai akibat Void wajib dicatat sebagai expense (Refund) di CashflowEntry
            // agar laporan Cashflow (Net Cash) periodik tetap balance secara pembukuan F&B.
            \App\Models\CashflowEntry::create([
                'entry_date' => now()->toDateString(),
                'type' => 'expense',
                'amount' => $lockedTransaction->total_amount,
                'source' => 'Transaction Void',
                'note' => "Refund untuk Void Transaksi: {$lockedTransaction->transaction_code} pada Sesi {$lockedSession->id}",
                'created_by' => $requestDto->actor->id,
            ]);

            $lockedSession->save();

            // 9. Ubah status transaksi menjadi VOID
            $lockedTransaction->status = 'VOID';
            $lockedTransaction->save();

            // 10. Kalkulasi Saldo Kasir Absolut (Single Source of Truth)
            // Rekalkulasi secara terpusat agar Frontend hanya menerima state mutlak
            $grossSales = Transaction::where('user_id', $lockedSession->cashier_id)
                ->where('status', '!=', 'VOID')
                ->whereBetween('created_at', [$lockedSession->opened_at ?: now()->startOfDay(), now()])
                ->sum('total_amount');
                
            $totalRefunds = \App\Models\CashflowEntry::where('created_by', $lockedSession->cashier_id)
                ->where('type', 'expense')
                ->where('source', 'Transaction Void')
                ->where('entry_date', now()->toDateString())
                ->sum('amount');
                
            $newDrawerBalance = (float)($grossSales - $totalRefunds);

            DB::commit();

            return $newDrawerBalance;

        } catch (Exception $e) {
            DB::rollBack();
            // Lepas kunci idempotensi agar request tidak stuck permanent jika terjadi error bisnis/validation.
            Cache::forget($idempotencyKey);
            
            // Re-throw Exception tanpa menelannya diam-diam (Hukum Mutlak)
            throw $e;
        }
    }

    /**
     * Memeriksa otorisasi instance-level.
     * 
     * @param \App\Models\User $actor
     * @param \App\Models\Transaction $transaction
     * @param int|string $currentSessionId
     * @return bool
     */
    private function authorizeActor($actor, $transaction, $currentSessionId): bool
    {
        // Validasi Mutlak: Transaksi ini HANYA boleh di-void jika memang terjadi di Sesi Kasir yang sedang aktif ini.
        // Jika tidak, pembukuan lintas-sesi (kemarin vs hari ini) akan rusak.
        if ((string)$transaction->daily_stock_session_id !== (string)$currentSessionId) {
            return false;
        }

        if (in_array($actor->role, ['owner', 'admin'])) {
            return true;
        }

        // Jika kasir, pastikan ia mengoperasikan sesinya sendiri dan mencoba mem-void di sesi tersebut
        if ($actor->role === 'cashier' && (string)$actor->id === (string)$transaction->user_id) {
            // Verifikasi lebih lanjut apakah current session id cocok dll bisa disematkan di sini.
            return true;
        }

        return false;
    }
}
