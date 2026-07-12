<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchScope
{
    public static function defaultBranchId(): ?int
    {
        if (! self::hasBranchesTable()) {
            return null;
        }

        $id = DB::table('branches')->where('code', 'default')->value('id')
            ?: DB::table('branches')->orderBy('id')->value('id');

        return $id ? (int) $id : null;
    }

    public static function userBranchId(?User $user): ?int
    {
        $branchId = (int) ($user?->branch_id ?? 0);

        return $branchId > 0 ? $branchId : self::defaultBranchId();
    }

    public static function scopedBranchIdFor(?User $user): ?int
    {
        $role = strtolower(trim((string) optional($user?->role)->name));

        if ($role === 'admin') {
            return self::activeBranchIdFor($user);
        }

        if ($role === 'kasir') {
            return self::userBranchId($user);
        }

        return null;
    }

    public static function scopedBranchIdsFor(?User $user): array
    {
        $role = strtolower(trim((string) optional($user?->role)->name));

        if ($role === 'admin') {
            return self::assignedBranchIds($user);
        }

        if ($role === 'kasir') {
            return array_values(array_filter([self::userBranchId($user)]));
        }

        return [];
    }

    public static function activeBranchIdFor(?User $user): ?int
    {
        $allowedIds = self::assignedBranchIds($user);

        if (empty($allowedIds)) {
            return self::userBranchId($user);
        }

        $sessionBranchId = self::sessionBranchId();
        if ($sessionBranchId && in_array($sessionBranchId, $allowedIds, true)) {
            return $sessionBranchId;
        }

        $primaryBranchId = self::userBranchId($user);
        if ($primaryBranchId && in_array($primaryBranchId, $allowedIds, true)) {
            self::putSessionBranchId($primaryBranchId);

            return $primaryBranchId;
        }

        self::putSessionBranchId($allowedIds[0]);

        return $allowedIds[0];
    }

    public static function assignedBranchIds(?User $user): array
    {
        if (! $user || ! self::supportsUserBranches()) {
            return [];
        }

        $fallbackBranchId = self::userBranchId($user);

        if (! self::supportsUserBranchAssignments()) {
            return array_values(array_filter([$fallbackBranchId]));
        }

        $assignedIds = DB::table('branch_user')
            ->join('branches', 'branches.id', '=', 'branch_user.branch_id')
            ->where('branch_user.user_id', $user->id)
            ->where('branches.is_active', true)
            ->orderBy('branches.name')
            ->pluck('branch_user.branch_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($fallbackBranchId && ! in_array($fallbackBranchId, $assignedIds, true)) {
            $assignedIds[] = $fallbackBranchId;
        }

        return array_values(array_unique(array_filter($assignedIds)));
    }

    public static function requestBranchId(?int $requestedBranchId): ?int
    {
        if (! self::hasBranchesTable()) {
            return null;
        }

        if (($requestedBranchId ?? 0) <= 0) {
            return null;
        }

        return Branch::query()
            ->whereKey($requestedBranchId)
            ->where('is_active', true)
            ->value('id');
    }

    public static function apply(EloquentBuilder|QueryBuilder $query, ?int $branchId, string $column = 'branch_id'): EloquentBuilder|QueryBuilder
    {
        if (($branchId ?? 0) > 0) {
            $query->where($column, (int) $branchId);
        }

        return $query;
    }

    public static function options()
    {
        if (! self::hasBranchesTable()) {
            return collect();
        }

        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public static function optionsFor(?User $user)
    {
        if (! self::hasBranchesTable()) {
            return collect();
        }

        $role = strtolower(trim((string) optional($user?->role)->name));

        if ($role !== 'admin') {
            return self::options();
        }

        $branchIds = self::assignedBranchIds($user);

        if (empty($branchIds)) {
            return collect();
        }

        return Branch::query()
            ->whereIn('id', $branchIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public static function switchActiveBranch(User $user, int $branchId): bool
    {
        $allowedIds = self::assignedBranchIds($user);

        if (! in_array($branchId, $allowedIds, true)) {
            return false;
        }

        self::putSessionBranchId($branchId);

        return true;
    }

    public static function hasBranchesTable(): bool
    {
        return Schema::hasTable('branches');
    }

    public static function supportsUserBranches(): bool
    {
        return self::hasBranchesTable()
            && Schema::hasTable('users')
            && Schema::hasColumn('users', 'branch_id');
    }

    public static function supportsUserBranchAssignments(): bool
    {
        return self::supportsUserBranches()
            && Schema::hasTable('branch_user');
    }

    private static function sessionBranchId(): ?int
    {
        if (app()->runningInConsole() || ! app()->bound('session')) {
            return null;
        }

        $branchId = (int) session('active_branch_id', 0);

        return $branchId > 0 ? $branchId : null;
    }

    private static function putSessionBranchId(?int $branchId): void
    {
        if (! $branchId || app()->runningInConsole() || ! app()->bound('session')) {
            return;
        }

        session(['active_branch_id' => (int) $branchId]);
    }
}
