<?php

namespace App\DTOs;

use App\Models\User;
use App\Enums\VoidInventoryActionEnum;

readonly class VoidTransactionRequestDto
{
    public function __construct(
        public int|string $transactionId,
        public int|string $currentSessionId,
        public User $actor,
        public string $idempotencyKey,
        public VoidInventoryActionEnum $inventoryAction,
    ) {
    }
}
