<?php

namespace App\Services\Exports;

use App\Models\StockLog;
use App\Support\BranchScope;
use App\Support\StockLogTypeMap;
use Illuminate\Database\Eloquent\Builder;

class StockLogExportQuery
{
    public function build($rangeStart, $rangeEnd, ?string $typeFilter, ?int $branchId): Builder
    {
        $query = StockLog::query()
            ->with(['ingredient:id,name,display_unit,base_unit,pack_size', 'referenceTransaction:id,transaction_code'])
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->latest();

        BranchScope::apply($query, $branchId, 'branch_id');

        if (in_array($typeFilter, StockLogTypeMap::allowedTabs(), true)) {
            $query->whereIn('type', StockLogTypeMap::tabTypes($typeFilter));
        }

        return $query;
    }
}
