<?php

namespace App\Services\Exports;

use App\Models\CashflowEntry;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ExpenseExportQuery
{
    public function build(Carbon $start, Carbon $end, ?int $branchId, string $search = ''): Builder
    {
        $query = CashflowEntry::query()
            ->with(['creator:id,name', 'branch:id,name'])
            ->where('type', 'expense')
            ->whereBetween('entry_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->latest('entry_date')
            ->latest('id');

        BranchScope::apply($query, $branchId, 'branch_id');

        $search = trim($search);
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('source', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('creator', function (Builder $creator) use ($search) {
                        $creator->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    public function summary(Builder $query): array
    {
        $aggregate = (clone $query)
            ->reorder()
            ->selectRaw('COALESCE(SUM(amount), 0) as expense_total, COUNT(*) as expense_count')
            ->first();

        return [
            'expenseTotal' => (float) ($aggregate->expense_total ?? 0),
            'expenseCount' => (int) ($aggregate->expense_count ?? 0),
        ];
    }
}
