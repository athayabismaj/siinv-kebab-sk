<?php

namespace App\DTOs;

use App\Models\DailyStockSession;

final readonly class CashierOperationalContext
{
    /**
     * @param array<int, int> $allowedBranchIds
     */
    public function __construct(
        public int $userId,
        public array $allowedBranchIds,
        public string $sessionDate,
        public ?DailyStockSession $session = null,
        public bool $ambiguous = false,
    ) {
    }

    public function operationalBranchId(): ?int
    {
        $branchId = (int) ($this->session?->branch_id ?? 0);

        return $branchId > 0 ? $branchId : null;
    }

    public function sessionId(): ?int
    {
        return $this->session ? (int) $this->session->id : null;
    }
}
