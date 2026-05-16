<?php

namespace App\Policies;

use App\Models\DailyStockSession;
use App\Models\User;

class DailyStockSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function open(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function transfer(User $user, DailyStockSession $session): bool
    {
        return $this->isHighLevel($user) || $user->id === $session->cashier_id;
    }

    public function close(User $user, DailyStockSession $session): bool
    {
        return $this->isHighLevel($user) || $user->id === $session->cashier_id;
    }

    public function reopen(User $user, DailyStockSession $session): bool
    {
        return $this->isHighLevel($user) || $user->id === $session->cashier_id;
    }

    public function viewReport(User $user): bool
    {
        return $this->isHighLevel($user);
    }

    private function isAdmin(User $user): bool
    {
        return strtolower((string) optional($user->role)->name) === 'admin';
    }

    private function isHighLevel(User $user): bool
    {
        $roleName = strtolower((string) optional($user->role)->name);
        return in_array($roleName, ['admin', 'manager'], true);
    }
}

