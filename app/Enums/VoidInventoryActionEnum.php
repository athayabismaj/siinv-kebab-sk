<?php

namespace App\Enums;

enum VoidInventoryActionEnum: string
{
    case RESTOCK = 'restock';
    case WASTE = 'waste';
}
