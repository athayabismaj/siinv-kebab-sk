<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Support\BranchScope;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        $roleName = strtolower(trim((string) optional($user->role)->name));

        if ($roleName === 'owner') {
            return true;
        }

        $branchId = BranchScope::scopedBranchIdFor($user);

        if (! $branchId || (int) $transaction->branch_id !== $branchId) {
            return false;
        }

        return $roleName === 'admin'
            || ($roleName === 'kasir' && (int) $transaction->user_id === (int) $user->id);
    }
}
