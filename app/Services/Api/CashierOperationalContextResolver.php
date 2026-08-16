<?php

namespace App\Services\Api;

use App\DTOs\CashierOperationalContext;
use App\Models\DailyStockSession;
use App\Models\User;
use App\Support\DailyStockClosingWindow;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CashierOperationalContextResolver
{
    /**
     * @param  array<int|string, mixed>  $relations
     */
    public function resolve(
        User $user,
        array $relations = [],
        ?CarbonInterface $businessTime = null,
        bool $lockForUpdate = false,
        bool $allowPreviousDayClosingGrace = false,
    ): CashierOperationalContext {
        $primaryBranchId = (int) ($user->branch_id ?? 0);
        $localTime = ($businessTime ? $businessTime->copy() : now(config('app.timezone', 'Asia/Jakarta')))
            ->setTimezone(config('app.timezone', 'Asia/Jakarta'))
            ->toImmutable();
        $sessionDates = $allowPreviousDayClosingGrace
            ? DailyStockClosingWindow::candidateSessionDates($localTime)
            : [$localTime->toDateString()];

        $sessions = DailyStockSession::query()
            ->with($relations)
            ->where('cashier_id', $user->id)
            ->where(function ($query) use ($user, $primaryBranchId): void {
                $assignedBranchExists = fn ($subquery) => $subquery
                    ->selectRaw('1')
                    ->from('branch_user')
                    ->join('branches', 'branches.id', '=', 'branch_user.branch_id')
                    ->where('branch_user.user_id', $user->id)
                    ->where('branches.is_active', true)
                    ->whereColumn('branch_user.branch_id', 'daily_stock_sessions.branch_id');

                if ($primaryBranchId > 0) {
                    $query->where('daily_stock_sessions.branch_id', $primaryBranchId)
                        ->orWhereExists($assignedBranchExists);

                    return;
                }

                $query->whereExists($assignedBranchExists);
            })
            ->where(function ($query) use ($sessionDates): void {
                foreach ($sessionDates as $sessionDate) {
                    if (DB::getDriverName() === 'pgsql') {
                        $query->orWhere('session_date', $sessionDate);
                    } else {
                        $query->orWhereDate('session_date', $sessionDate);
                    }
                }
            })
            ->where('status', 'open')
            ->orderBy('session_date')
            ->orderBy('branch_id')
            ->orderBy('id')
            ->limit(3)
            ->when($lockForUpdate, fn ($query) => $query->lockForUpdate())
            ->get();

        $selectedSessionDate = $sessions->first()?->session_date?->toDateString()
            ?? $sessionDates[0];
        $sessions = $sessions
            ->filter(fn (DailyStockSession $session) => $session->session_date->toDateString() === $selectedSessionDate)
            ->values();

        $allowedBranchIds = $sessions->pluck('branch_id')
            ->map(fn ($id) => (int) $id)
            ->when($primaryBranchId > 0, fn ($ids) => $ids->push($primaryBranchId))
            ->unique()
            ->values()
            ->all();

        if ($sessions->count() > 1) {
            Log::warning('Ambiguous active cashier operational sessions.', [
                'user_id' => (int) $user->id,
                'session_date' => $selectedSessionDate,
                'session_ids' => $sessions->pluck('id')->map(fn ($id) => (int) $id)->all(),
                'branch_ids' => $sessions->pluck('branch_id')->map(fn ($id) => (int) $id)->all(),
            ]);

            return new CashierOperationalContext(
                (int) $user->id,
                $allowedBranchIds,
                $selectedSessionDate,
                null,
                true,
            );
        }

        return new CashierOperationalContext(
            (int) $user->id,
            $allowedBranchIds,
            $selectedSessionDate,
            $sessions->first(),
        );
    }
}
