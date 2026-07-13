<?php

namespace App\Actions\DailyStock;

use App\Models\DailyStockSession;
use App\Models\User;
use App\Support\BranchScope;
use Carbon\Carbon;
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
        return DB::transaction(function () use ($sessionDate, $cashierId, $openedBy, $notes, $branchId) {
            $date = $sessionDate instanceof Carbon
                ? $sessionDate->copy()->startOfDay()->toDateString()
                : Carbon::parse((string) $sessionDate)->startOfDay()->toDateString();
            $resolvedBranchId = $branchId ?: BranchScope::userBranchId(
                User::query()->with('role')->find($cashierId)
            );

            $session = DailyStockSession::query()
                ->where('session_date', $date)
                ->where('cashier_id', $cashierId)
                ->when($resolvedBranchId, fn ($query) => $query->where('branch_id', $resolvedBranchId))
                ->lockForUpdate()
                ->first();

            if ($session) {
                if ($session->status !== 'open') {
                    throw new RuntimeException('Sesi stok harian untuk tanggal ini sudah ditutup.');
                }

                if ($notes !== null && trim($notes) !== '') {
                    $session->notes = $notes;
                    $session->save();
                }

                return $session;
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
    }
}
