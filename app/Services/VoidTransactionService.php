<?php

namespace App\Services;

use App\Actions\Sales\VoidTransactionAction;
use App\Contracts\Services\VoidTransactionServiceInterface;
use App\DTOs\VoidTransactionRequestDto;

class VoidTransactionService implements VoidTransactionServiceInterface
{
    public function __construct(
        private readonly VoidTransactionAction $voidTransactionAction,
    ) {
    }

    public function voidTransaction(VoidTransactionRequestDto $requestDto): float
    {
        return $this->voidTransactionAction->execute($requestDto);
    }
}
