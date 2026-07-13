<?php

namespace App\Services;

use App\Actions\DailyStock\CloseDailyStockSessionAction;
use App\Actions\DailyStock\OpenDailyStockSessionAction;
use App\Actions\DailyStock\TransferToDailyStockAction;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\StockLog;
use App\Support\AdminCache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DailyStockService
{
    public function __construct(
        private readonly OpenDailyStockSessionAction $openDailyStockSession,
        private readonly CloseDailyStockSessionAction $closeDailyStockSession,
        private readonly TransferToDailyStockAction $transferToDailyStock,
    ) {
    }

    public function reconcileSessionUsage(int $sessionId): DailyStockSession
    {
        $session = DB::transaction(function () use ($sessionId) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            $items = DailyStockItem::query()
                ->where('daily_stock_session_id', $session->id)
                ->lockForUpdate()
                ->get();

            $usageByIngredient = $this->inferUsageFromTransactions(
                (int) $session->cashier_id,
                $session->session_date->toDateString(),
                $items->pluck('ingredient_id')->map(fn ($id) => (int) $id)->all()
            );

            foreach ($items as $item) {
                $opening = (float) $item->opening_qty;
                $used = max(
                    (float) $item->used_qty,
                    (float) ($usageByIngredient[(int) $item->ingredient_id] ?? 0)
                );

                if ($used > $opening) {
                    $opening = $used;
                }

                $item->update([
                    'opening_qty' => $opening,
                    'used_qty' => $used,
                    'remaining_qty' => max(0, $opening - $used),
                ]);
            }

            return $session->fresh(['items.ingredient', 'cashier', 'openedBy', 'closedBy']);
        });

        AdminCache::bumpDailyStock();
        AdminCache::bumpDashboard();
        AdminCache::bumpUsage();
        AdminCache::bumpCatalog();

        return $session;
    }

    public function openSession(
        \Carbon\Carbon|string $sessionDate,
        int $cashierId,
        int $openedBy,
        ?string $notes = null,
        ?int $branchId = null
    ): DailyStockSession {
        $session = $this->openDailyStockSession->execute(
            $sessionDate,
            $cashierId,
            $openedBy,
            $notes,
            $branchId
        );

        AdminCache::bumpDailyStock();
        AdminCache::bumpCatalog();

        return $session;
    }

    public function transferToDaily(
        int $sessionId,
        int $ingredientId,
        float $quantity,
        int $actorId,
        ?string $note = null,
        ?int $branchId = null
    ): DailyStockItem {
        $result = $this->transferToDailyStock->executeBatch(
            $sessionId,
            [$ingredientId => ['qty' => $quantity, 'note' => $note]],
            $actorId,
            $branchId
        );

        if ($result['processed'] !== 1) {
            $skipped = $result['skipped'][0] ?? null;

            if ($skipped) {
                throw new RuntimeException("Stok gudang {$skipped['name']} tidak cukup untuk transfer.");
            }

            throw new RuntimeException('Jumlah transfer harus lebih dari 0.');
        }

        $item = DailyStockItem::query()
            ->where('daily_stock_session_id', $sessionId)
            ->where('ingredient_id', $ingredientId)
            ->firstOrFail();

        AdminCache::bumpDailyStock();
        AdminCache::bumpDashboard();
        AdminCache::bumpStock();
        AdminCache::bumpCatalog();

        return $item;
    }

    /**
     * @param array<int, array{qty: float, note: ?string}> $transfers  Key: ingredient_id, Value: array of qty (base unit) and note
     * @return array{
     *     session: DailyStockSession,
     *     processed: int,
     *     skipped: array<int, array{name: string, requested: float, available: float, unit: string}>
     * }
     */
    public function batchTransferToDaily(
        int $sessionId,
        array $transfers,
        int $actorId,
        ?int $branchId = null
    ): array {
        $result = $this->transferToDailyStock->executeBatch($sessionId, $transfers, $actorId, $branchId);

        if ($result['processed'] > 0) {
            AdminCache::bumpDailyStock();
            AdminCache::bumpDashboard();
            AdminCache::bumpStock();
            AdminCache::bumpCatalog();
        }

        return $result;
    }

    /**
     * @param array<int, float|int|string> $remainingByIngredient
     */
    public function closeSession(
        int $sessionId,
        array $remainingByIngredient,
        int $closedBy,
        ?string $notes = null,
        ?int $branchId = null
    ): DailyStockSession {
        $session = $this->closeDailyStockSession->execute(
            $sessionId,
            $remainingByIngredient,
            $closedBy,
            $notes,
            $branchId
        );

        AdminCache::bumpDailyStock();
        AdminCache::bumpDashboard();
        AdminCache::bumpUsage();
        AdminCache::bumpStock();
        AdminCache::bumpCatalog();

        return $session;
    }

    public function reopenSession(int $sessionId, int $reopenedBy, ?string $notes = null): DailyStockSession
    {
        $session = DB::transaction(function () use ($sessionId, $reopenedBy, $notes) {
            $session = DailyStockSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status !== 'closed') {
                throw new RuntimeException('Sesi belum ditutup, tidak perlu reopen.');
            }

            $items = DailyStockItem::query()
                ->where('daily_stock_session_id', $session->id)
                ->lockForUpdate()
                ->get();

            $ingredientIds = $items->pluck('ingredient_id')->unique()->sort()->values()->all();

            $ingredients = Ingredient::query()
                ->whereIn('id', $ingredientIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $returned = (float) $item->returned_qty;

                if ($returned > 0) {
                    $ingredient = $ingredients->get($item->ingredient_id);
                    if (! $ingredient) {
                        throw new RuntimeException("Bahan dengan ID {$item->ingredient_id} tidak ditemukan.");
                    }

                    if ((float) $ingredient->stock < $returned) {
                        throw new RuntimeException(
                            "Stok gudang {$ingredient->name} sudah berubah, sesi tidak bisa di-reopen otomatis."
                        );
                    }

                    $ingredient->decrement('stock', $returned);
                }

                $item->update([
                    // Saat reopen, jangan reset pemakaian agar ringkasan terpakai/nilai tetap konsisten.
                    'returned_qty' => 0,
                ]);
            }

            $session = $this->reconcileSessionUsage($session->id);

            StockLog::query()
                ->where('reference_id', $session->id)
                ->where('type', 'daily_return')
                ->delete();

            $session->update([
                'status' => 'open',
                'closed_by' => null,
                'closed_at' => null,
                'notes' => $notes ?: trim(($session->notes ? ($session->notes . ' | ') : '') . "Reopen oleh user #{$reopenedBy}"),
            ]);

            return $session->fresh(['items.ingredient', 'cashier', 'openedBy', 'closedBy']);
        });

        AdminCache::bumpDailyStock();
        AdminCache::bumpDashboard();
        AdminCache::bumpUsage();
        AdminCache::bumpStock();
        AdminCache::bumpCatalog();

        return $session;
    }

    /**
     * @param array<int, int> $ingredientIds
     * @return array<int, float>
     */
    private function inferUsageFromTransactions(int $cashierId, string $sessionDate, array $ingredientIds): array
    {
        if ($cashierId <= 0 || empty($ingredientIds)) {
            return [];
        }

        $rows = DB::table('stock_logs as sl')
            ->join('transactions as t', 't.id', '=', 'sl.reference_id')
            ->where('sl.type', 'daily_usage')
            ->where('t.user_id', $cashierId)
            ->whereDate('t.created_at', $sessionDate)
            ->whereIn('sl.ingredient_id', $ingredientIds)
            ->groupBy('sl.ingredient_id')
            ->selectRaw('sl.ingredient_id, SUM(ABS(sl.quantity)) as used_total')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->ingredient_id] = (float) $row->used_total;
        }

        return $result;
    }

}
