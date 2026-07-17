<?php

namespace App\Services\Api;

use App\DTOs\CashierOperationalContext;
use App\Models\DailyStockSession;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;

final class CashierOperationalContextResolver
{
    /**
     * @param array<int, string> $relations
     */
    public function resolve(
        User $user,
        array $relations = [],
        ?CarbonInterface $businessTime = null,
    ): CashierOperationalContext {
        $allowedBranchIds = $user->assignedBranches()
            ->where('branches.is_active', true)
            ->orderBy('branches.name')
            ->pluck('branches.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $primaryBranchId = (int) ($user->branch_id ?? 0);
        if ($primaryBranchId > 0 && ! in_array($primaryBranchId, $allowedBranchIds, true)) {
            $allowedBranchIds[] = $primaryBranchId;
        }
        $allowedBranchIds = array_values(array_unique($allowedBranchIds));
        $sessionDate = ($businessTime ? $businessTime->copy() : now(config('app.timezone', 'Asia/Jakarta')))
            ->setTimezone(config('app.timezone', 'Asia/Jakarta'))
            ->toDateString();

        if ($allowedBranchIds === []) {
            return new CashierOperationalContext((int) $user->id, [], $sessionDate);
        }

        $sessions = DailyStockSession::query()
            ->with($relations)
            ->where('cashier_id', $user->id)
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereDate('session_date', $sessionDate)
            ->whereRaw("LOWER(TRIM(status)) = 'open'")
            ->orderBy('branch_id')
            ->orderBy('id')
            ->limit(2)
            ->get();

        if ($sessions->count() > 1) {
            Log::warning('Ambiguous active cashier operational sessions.', [
                'user_id' => (int) $user->id,
                'session_date' => $sessionDate,
                'session_ids' => $sessions->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'branch_ids' => $sessions->pluck('branch_id')->map(fn ($id) => (int) $id)->all(),
            ]);

            return new CashierOperationalContext(
                (int) $user->id,
                $allowedBranchIds,
                $sessionDate,
                null,
                true,
            );
        }

        return new CashierOperationalContext(
            (int) $user->id,
            $allowedBranchIds,
            $sessionDate,
            $sessions->first(),
        );
    }
}
