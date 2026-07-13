<?php

namespace App\Actions\Sales;

use App\DTOs\VoidTransactionRequestDto;
use App\Enums\VoidInventoryActionEnum;
use App\Models\Branch;
use App\Models\CashflowEntry;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Services\Analytics\DailySalesSummaryService;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VoidTransactionAction
{
    public function __construct(
        private readonly DailySalesSummaryService $dailySalesSummaryService,
    ) {
    }

    public function execute(VoidTransactionRequestDto $requestDto): float
    {
        $idempotencyKey = 'void_tx_lock_' . $requestDto->idempotencyKey;
        if (! Cache::add($idempotencyKey, true, now()->addMinutes(5))) {
            throw new \Exception("Idempotency conflict: Permintaan void untuk key {$requestDto->idempotencyKey} sedang diproses atau sudah selesai.");
        }

        try {
            $transaction = Transaction::with('details.menuVariant.ingredients')->findOrFail($requestDto->transactionId);

            if (strtoupper((string) $transaction->status) === 'VOID') {
                throw new \Exception('Transaksi ini sudah dibatalkan sebelumnya.');
            }

            if (! $this->authorizeActor($requestDto->actor, $transaction, $requestDto->currentSessionId)) {
                throw new \Exception('Unauthorized: Anda tidak memiliki otoritas untuk mem-void transaksi ini pada sesi kasir tersebut.');
            }

            return DB::transaction(function () use ($requestDto, $transaction) {
                $lockedTransaction = Transaction::whereKey($requestDto->transactionId)->lockForUpdate()->firstOrFail();
                $lockedSession = DailyStockSession::whereKey($requestDto->currentSessionId)->lockForUpdate()->firstOrFail();

                if (strtoupper((string) $lockedTransaction->status) === 'VOID') {
                    throw new \Exception('Transaksi ini sudah dibatalkan sebelumnya.');
                }

                if (
                    (int) $lockedTransaction->daily_stock_session_id !== (int) $lockedSession->id
                    || (int) $lockedSession->cashier_id !== (int) $lockedTransaction->user_id
                    || (int) $lockedSession->branch_id !== (int) $lockedTransaction->branch_id
                ) {
                    throw new \Exception('Unauthorized: Anda tidak memiliki otoritas untuk mem-void transaksi ini pada sesi kasir tersebut.');
                }

                foreach ($transaction->details as $detail) {
                    $variant = $detail->menuVariant;

                    if (! $variant || ! $variant->ingredients) {
                        continue;
                    }

                    foreach ($variant->ingredients as $ingredient) {
                        $totalQuantity = (float) $ingredient->pivot->quantity * (float) $detail->quantity;
                        if ($totalQuantity <= 0) {
                            continue;
                        }

                        $lockedIngredient = Ingredient::whereKey($ingredient->id)->lockForUpdate()->first();
                        if (! $lockedIngredient) {
                            continue;
                        }

                        if ($requestDto->inventoryAction === VoidInventoryActionEnum::RESTOCK) {
                            $dailyItem = DailyStockItem::query()
                                ->where('daily_stock_session_id', $lockedSession->id)
                                ->where('ingredient_id', $lockedIngredient->id)
                                ->lockForUpdate()
                                ->first();

                            if ($dailyItem) {
                                $dailyItem->increment('remaining_qty', $totalQuantity);
                                $dailyItem->decrement('used_qty', $totalQuantity);
                            }

                            StockLog::create([
                                'branch_id' => $lockedTransaction->branch_id,
                                'ingredient_id' => $lockedIngredient->id,
                                'type' => 'daily_return',
                                'quantity' => $totalQuantity,
                                'reference_id' => $lockedTransaction->id,
                                'note' => "Pengembalian stok dari pembatalan transaksi {$lockedTransaction->transaction_code}",
                            ]);
                        } elseif ($requestDto->inventoryAction === VoidInventoryActionEnum::WASTE) {
                            $costLoss = (float) $lockedIngredient->cost_price * $totalQuantity;

                            DB::table('waste_logs')->insert([
                                'branch_id' => $lockedTransaction->branch_id,
                                'daily_stock_session_id' => $lockedSession->id,
                                'ingredient_id' => $lockedIngredient->id,
                                'quantity' => $totalQuantity,
                                'cost_loss' => $costLoss,
                                'notes' => "Bahan terbuang dari pembatalan transaksi {$lockedTransaction->transaction_code}",
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                CashflowEntry::create([
                    'branch_id' => $lockedTransaction->branch_id,
                    'entry_date' => now()->toDateString(),
                    'type' => 'expense',
                    'amount' => $lockedTransaction->total_amount,
                    'source' => 'Transaction Void',
                    'note' => "Refund untuk Void Transaksi: {$lockedTransaction->transaction_code} pada Sesi {$lockedSession->id}; alasan: {$requestDto->inventoryAction->value}",
                    'created_by' => $requestDto->actor->id,
                ]);

                $lockedTransaction->status = 'VOID';
                $lockedTransaction->voided_by = $requestDto->actor->id;
                $lockedTransaction->voided_at = now();
                $lockedTransaction->void_reason = $requestDto->inventoryAction->value;
                $lockedTransaction->save();

                $branch = Branch::query()->findOrFail($lockedTransaction->branch_id);
                $saleDate = Carbon::parse($lockedTransaction->created_at, config('app.timezone', 'Asia/Jakarta'));
                $this->dailySalesSummaryService->rebuildForDate($branch, $saleDate);

                return (float) Transaction::query()
                    ->where('user_id', $lockedSession->cashier_id)
                    ->where('status', 'SUCCESS')
                    ->where('daily_stock_session_id', $lockedSession->id)
                    ->sum('total_amount');
            });
        } catch (\Throwable $exception) {
            Cache::forget($idempotencyKey);

            throw $exception;
        }
    }

    private function authorizeActor($actor, Transaction $transaction, int|string $currentSessionId): bool
    {
        $scopedBranchId = BranchScope::scopedBranchIdFor($actor);
        if ($scopedBranchId && (int) $transaction->branch_id !== (int) $scopedBranchId) {
            return false;
        }

        if ($transaction->daily_stock_session_id !== null
            && (int) $transaction->daily_stock_session_id !== (int) $currentSessionId) {
            return false;
        }

        $roleName = strtolower((string) optional($actor->role)->name);

        if (in_array($roleName, ['owner', 'admin'], true)) {
            return true;
        }

        return $roleName === 'kasir' && (int) $actor->id === (int) $transaction->user_id;
    }
}
