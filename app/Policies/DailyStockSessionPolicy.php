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

    public function transfer(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function close(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function reopen(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function viewReport(User $user): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        return strtolower((string) optional($user->role)->name) === 'admin';
    }
}

