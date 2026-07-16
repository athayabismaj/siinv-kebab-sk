<?php

namespace App\View\Presenters;

final readonly class TransactionPresentation
{
    public function __construct(
        public string $statusKey,
        public string $statusLabel,
        public bool $isSuccess,
        public bool $isVoid,
        public string $paymentLabel,
        public ?string $voidReasonLabel,
        public string $tone,
        public string $badgeClass,
        public string $dotClass,
        public string $haloClass,
        public string $iconWrapClass,
        public string $iconClass,
        public string $detailBadgeClass,
        public string $detailDotClass,
    ) {}
}
