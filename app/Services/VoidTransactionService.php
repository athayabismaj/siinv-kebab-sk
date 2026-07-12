<?php

namespace App\Services;

use App\Contracts\Services\VoidTransactionServiceInterface;
use App\DTOs\VoidTransactionRequestDto;
use App\Enums\VoidInventoryActionEnum;
use App\Models\Transaction;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\MenuVariant;
use App\Support\BranchScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
        $idempotencyKey = 'void_tx_lock_' . $requestDto->idempotencyKey;
        if (!Cache::add($idempotencyKey, true, now()->addMinutes(5))) {
            throw new \Exception("Idempotency conflict: Permintaan void untuk key {$requestDto->idempotencyKey} sedang diproses atau sudah selesai.");
        }

        $transactionStarted = false;

        try {
            // 2. Fetch transaksi & Validasi
            // Eager load: details -> menuVariant -> ingredients (pivot: menu_variant_ingredients)
            $transaction = Transaction::with('details.menuVariant.ingredients')->findOrFail($requestDto->transactionId);

            // Fail-Fast: Jika status transaksi sudah 'VOID'
            if (strtoupper((string) $transaction->status) === 'VOID') {
                throw new \Exception("Transaksi ini sudah dibatalkan sebelumnya.");
            }

            // 3. Otorisasi Instance-Level
            if (!$this->authorizeActor($requestDto->actor, $transaction, $requestDto->currentSessionId)) {
                throw new \Exception("Unauthorized: Anda tidak memiliki otoritas untuk mem-void transaksi ini pada sesi kasir tersebut.");
            }

            DB::beginTransaction();
            $transactionStarted = true;

            // 4. Lock for Update: Baris Transaksi dan DailyStockSession
            $lockedTransaction = Transaction::where('id', $requestDto->transactionId)->lockForUpdate()->firstOrFail();
            $lockedSession = DailyStockSession::where('id', $requestDto->currentSessionId)->lockForUpdate()->firstOrFail();

            if (strtoupper((string) $lockedTransaction->status) === 'VOID') {
                throw new \Exception("Transaksi ini sudah dibatalkan sebelumnya.");
            }

            if (
                (int) $lockedTransaction->daily_stock_session_id !== (int) $lockedSession->id
                || (int) $lockedSession->cashier_id !== (int) $lockedTransaction->user_id
                || (int) $lockedSession->branch_id !== (int) $lockedTransaction->branch_id
            ) {
                throw new \Exception("Unauthorized: Anda tidak memiliki otoritas untuk mem-void transaksi ini pada sesi kasir tersebut.");
            }

            // 5. Looping Resep Bahan Baku via MenuVariant (Auto-Recipe Logic)
            // Jalur relasi: TransactionDetail -> MenuVariant -> ingredients (pivot: menu_variant_ingredients)
            foreach ($transaction->details as $detail) {
                $variant = $detail->menuVariant;

                // Jika variant tidak ada (data lama tanpa menu_variant_id), skip gracefully
                if (!$variant || !$variant->ingredients) {
                    continue;
                }

                foreach ($variant->ingredients as $ingredient) {
                    // Kuantitas Bahan = Jumlah di Resep (pivot) × Jumlah Porsi yang Dibeli (detail)
                    $recipeQty = (float) $ingredient->pivot->quantity;
                    $totalKuantitas = $recipeQty * (float) $detail->quantity;

                    if ($totalKuantitas <= 0) {
                        continue;
                    }

                    // Lock ingredient secara spesifik untuk mencegah race condition
                    $lockedIngredient = Ingredient::where('id', $ingredient->id)->lockForUpdate()->first();

                    if (!$lockedIngredient) {
                        continue;
                    }

                    if ($requestDto->inventoryAction === VoidInventoryActionEnum::RESTOCK) {
                        // Kembalikan bahan baku ke stok harian kasir
                        $dailyItem = \App\Models\DailyStockItem::query()
                            ->where('daily_stock_session_id', $lockedSession->id)
                            ->where('ingredient_id', $lockedIngredient->id)
                            ->lockForUpdate()
                            ->first();

                        if ($dailyItem) {
                            $dailyItem->increment('remaining_qty', $totalKuantitas);
                            $dailyItem->decrement('used_qty', $totalKuantitas);
                        }

                        // Catat ke stock_logs dengan tipe 'daily_return'
                        \App\Models\StockLog::create([
                            'branch_id' => $lockedTransaction->branch_id,
                            'ingredient_id' => $lockedIngredient->id,
                            'type' => 'daily_return',
                            'quantity' => $totalKuantitas,
                            'reference_id' => $lockedTransaction->id,
                            'note' => "Pengembalian stok dari pembatalan transaksi {$lockedTransaction->transaction_code}",
                        ]);

                    } elseif ($requestDto->inventoryAction === VoidInventoryActionEnum::WASTE) {
                        // JANGAN ubah stok (fisik sudah hilang/dibuang)
                        // Catat kerugian ke waste_logs sesuai skema: daily_stock_session_id, ingredient_id, quantity, cost_loss, notes
                        $costLoss = (float) $lockedIngredient->cost_price * $totalKuantitas;

                        DB::table('waste_logs')->insert([
                            'branch_id' => $lockedTransaction->branch_id,
                            'daily_stock_session_id' => $lockedSession->id,
                            'ingredient_id' => $lockedIngredient->id,
                            'quantity' => $totalKuantitas,
                            'cost_loss' => $costLoss,
                            'notes' => "Bahan terbuang dari pembatalan transaksi {$lockedTransaction->transaction_code}",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // 6. Catat refund sebagai expense di CashflowEntry
            \App\Models\CashflowEntry::create([
                'branch_id' => $lockedTransaction->branch_id,
                'entry_date' => now()->toDateString(),
                'type' => 'expense',
                'amount' => $lockedTransaction->total_amount,
                'source' => 'Transaction Void',
                'note' => "Refund untuk Void Transaksi: {$lockedTransaction->transaction_code} pada Sesi {$lockedSession->id}; alasan: {$requestDto->inventoryAction->value}",
                'created_by' => $requestDto->actor->id,
            ]);

            // 7. Ubah status transaksi menjadi VOID
            $lockedTransaction->status = 'VOID';
            $lockedTransaction->voided_by = $requestDto->actor->id;
            $lockedTransaction->voided_at = now();
            $lockedTransaction->void_reason = $requestDto->inventoryAction->value;
            $lockedTransaction->save();

            // 8. Kalkulasi Saldo Kasir Absolut (Single Source of Truth)
            $grossSales = Transaction::where('user_id', $lockedSession->cashier_id)
                ->where('status', 'SUCCESS')
                ->where('daily_stock_session_id', $lockedSession->id)
                ->sum('total_amount');

            $newDrawerBalance = (float) $grossSales;

            DB::commit();

            return $newDrawerBalance;

        } catch (\Throwable $e) {
            if ($transactionStarted && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Cache::forget($idempotencyKey);
            throw $e;
        }
    }

    /**
     * Memeriksa otorisasi instance-level.
     */
    private function authorizeActor($actor, Transaction $transaction, int|string $currentSessionId): bool
    {
        $scopedBranchId = BranchScope::scopedBranchIdFor($actor);
        if ($scopedBranchId && (int) $transaction->branch_id !== (int) $scopedBranchId) {
            return false;
        }

        // Validasi Mutlak: Transaksi ini harus berasal dari sesi kasir yang sedang aktif.
        // Jika daily_stock_session_id null (data lama), izinkan owner/admin saja.
        if ($transaction->daily_stock_session_id !== null) {
            if ((int) $transaction->daily_stock_session_id !== (int) $currentSessionId) {
                return false;
            }
        }

        // Cek role melalui relasi Eloquent
        $roleName = strtolower((string) optional($actor->role)->name);

        if (in_array($roleName, ['owner', 'admin'], true)) {
            return true;
        }

        // Kasir hanya boleh void transaksi miliknya sendiri
        if ($roleName === 'kasir' && (int) $actor->id === (int) $transaction->user_id) {
            return true;
        }

        return false;
    }
}
