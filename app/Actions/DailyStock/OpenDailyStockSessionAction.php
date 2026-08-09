<?php

namespace App\Actions\DailyStock;

use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\User;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OpenDailyStockSessionAction
{
    public function execute(
        Carbon|string $sessionDate,
        int $cashierId,
        int $openedBy,
        ?string $notes = null,
        ?int $branchId = null
    ): DailyStockSession {
        $date = $sessionDate instanceof Carbon
            ? $sessionDate->copy()->startOfDay()->toDateString()
            : Carbon::parse((string) $sessionDate)->startOfDay()->toDateString();
        $resolvedBranchId = $branchId ?: BranchScope::userBranchId(
            User::query()->with('role')->find($cashierId)
        );

        try {
            return DB::transaction(function () use ($date, $cashierId, $openedBy, $notes, $resolvedBranchId) {
                $session = $this->sessionQuery($date, $cashierId, $resolvedBranchId)
                    ->lockForUpdate()
                    ->first();

                if ($session) {
                    return $this->useExistingSession($session, $notes);
                }

                $sourceSession = $this->previousSession(
                    $date,
                    $cashierId,
                    $resolvedBranchId
                );

                if ($sourceSession?->status === 'open') {
                    $sourceDate = $sourceSession->session_date->format('d/m/Y');

                    throw new RuntimeException(
                        "Sesi stok harian sebelumnya (#{$sourceSession->id}, {$sourceDate}) masih terbuka. "
                        .'Tutup sesi tersebut terlebih dahulu agar sisa bahan dapat dibawa ke sesi baru.'
                    );
                }

                $carryForwardSource = $sourceSession?->status === 'closed'
                    && $sourceSession->stock_retained_at_outlet
                    ? $sourceSession
                    : null;

                $session = DailyStockSession::query()->create([
                    'session_date' => $date,
                    'branch_id' => $resolvedBranchId,
                    'cashier_id' => $cashierId,
                    'opened_by' => $openedBy,
                    'status' => 'open',
                    'stock_retained_at_outlet' => false,
                    'carry_forward_source_session_id' => $carryForwardSource?->id,
                    'notes' => $notes,
                    'opened_at' => now(),
                ]);

                if ($carryForwardSource) {
                    $this->copyRemainingStock($carryForwardSource, $session);
                }

                return $session->fresh(['items.ingredient', 'carryForwardSource']);
            });
        } catch (QueryException $exception) {
            if (! $this->isDailySessionUniqueViolation($exception)) {
                throw $exception;
            }

            // PostgreSQL aborts the failed transaction, so re-read only after rollback.
            return DB::transaction(function () use ($date, $cashierId, $notes, $resolvedBranchId, $exception) {
                $session = $this->sessionQuery($date, $cashierId, $resolvedBranchId)
                    ->lockForUpdate()
                    ->first();

                if (! $session) {
                    throw $exception;
                }

                return $this->useExistingSession($session, $notes);
            });
        }
    }

    private function sessionQuery(string $date, int $cashierId, ?int $branchId): Builder
    {
        return DailyStockSession::query()
            ->whereDate('session_date', $date)
            ->where('cashier_id', $cashierId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId));
    }

    private function useExistingSession(DailyStockSession $session, ?string $notes): DailyStockSession
    {
        if ($session->status !== 'open') {
            throw new RuntimeException('Sesi stok harian untuk tanggal ini sudah ditutup.');
        }

        if ($notes !== null && trim($notes) !== '') {
            $session->notes = $notes;
            $session->save();
        }

        return $session;
    }

    private function previousSession(
        string $date,
        int $cashierId,
        ?int $branchId
    ): ?DailyStockSession {
        return DailyStockSession::query()
            ->where('session_date', '<', $date)
            ->where('cashier_id', $cashierId)
            ->when($branchId, fn (Builder $query) => $query->where('branch_id', $branchId))
            ->latest('session_date')
            ->latest('closed_at')
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }

    private function copyRemainingStock(
        DailyStockSession $sourceSession,
        DailyStockSession $targetSession
    ): void {
        $sourceItems = DailyStockItem::query()
            ->where('daily_stock_session_id', $sourceSession->id)
            ->where('remaining_qty', '>', 0)
            ->orderBy('ingredient_id')
            ->lockForUpdate()
            ->get();

        foreach ($sourceItems as $sourceItem) {
            $remaining = round((float) $sourceItem->remaining_qty, 2);

            DailyStockItem::query()->create([
                'daily_stock_session_id' => $targetSession->id,
                'ingredient_id' => $sourceItem->ingredient_id,
                'carry_forward_qty' => $remaining,
                'opening_adjustment_qty' => 0,
                'transferred_qty' => 0,
                'opening_qty' => $remaining,
                'remaining_qty' => $remaining,
                'used_qty' => 0,
                'returned_qty' => 0,
                'note' => "Sisa sesi sebelumnya #{$sourceSession->id}",
            ]);
        }
    }

    protected function isDailySessionUniqueViolation(QueryException $exception): bool
    {
        $driver = $exception->getConnectionDetails()['driver']
            ?? config('database.connections.'.$exception->getConnectionName().'.driver');
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $sql = strtolower(ltrim($exception->getSql()));

        return $driver === 'pgsql'
            && $sqlState === '23505'
            && str_starts_with($sql, 'insert into "daily_stock_sessions"')
            && str_contains($exception->getMessage(), '"daily_stock_session_date_cashier_unique"');
    }
}
