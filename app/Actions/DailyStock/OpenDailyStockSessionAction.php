<?php

namespace App\Actions\DailyStock;

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

                return DailyStockSession::query()->create([
                    'session_date' => $date,
                    'branch_id' => $resolvedBranchId,
                    'cashier_id' => $cashierId,
                    'opened_by' => $openedBy,
                    'status' => 'open',
                    'notes' => $notes,
                    'opened_at' => now(),
                ]);
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
