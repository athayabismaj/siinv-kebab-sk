<?php

namespace App\View\Presenters;

final readonly class StockSessionPresentation
{
    public function __construct(
        public string $label,
        public string $dotClass,
        public string $textClass,
        public string $badgeClass,
    ) {}
}
