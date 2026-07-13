<?php

namespace App\Services\Analytics;

use App\Models\Branch;
use App\Models\DailySalesSummary;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DailySalesSummaryService
{
    private ?bool $summaryTableAvailable = null;

    public function getOrBuildForDate(Branch $branch, Carbon $date): array
    {
        if (! $this->hasSummaryTable()) {
            return $this->buildFromTransactionsForDate($branch, $date);
        }

        $dateKey = $date->toDateString();

        try {
            $summary = DailySalesSummary::query()
                ->where('branch_id', $branch->id)
                ->whereDate('sale_date', $dateKey)
                ->first();
        } catch (QueryException $exception) {
            if ($this->isMissingSummaryTableException($exception)) {
                $this->summaryTableAvailable = false;

                return $this->buildFromTransactionsForDate($branch, $date);
            }

            throw $exception;
        }

        return $summary ? $this->toPayload($summary) : $this->rebuildForDate($branch, $date);
    }

    public function getRange(Branch $branch, Carbon $startDate, Carbon $endDate, bool $live = false): array
    {
        if ($live || ! $this->hasSummaryTable()) {
            return $this->buildRangeFromTransactions($branch, $startDate, $endDate);
        }

        try {
            $rows = DailySalesSummary::query()
                ->where('branch_id', $branch->id)
                ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('sale_date')
                ->get();
        } catch (QueryException $exception) {
            if ($this->isMissingSummaryTableException($exception)) {
                $this->summaryTableAvailable = false;

                return $this->buildRangeFromTransactions($branch, $startDate, $endDate);
            }

            throw $exception;
        }

        $today = now()->startOfDay();
        if ($today->betweenIncluded($startDate->copy()->startOfDay(), $endDate->copy()->endOfDay())) {
            $todayPayload = $this->buildFromTransactionsForDate($branch, $today);
            $todayKey = $today->toDateString();

            $rows = $rows
                ->reject(fn (DailySalesSummary $row) => $row->sale_date->toDateString() === $todayKey)
                ->push(new DailySalesSummary([
                    'branch_id' => $branch->id,
                    'sale_date' => $todayKey,
                    'total_transactions' => $todayPayload['total_transactions'],
                    'total_revenue' => $todayPayload['total_revenue'],
                    'total_items_sold' => $todayPayload['total_items_sold'],
                ]))
                ->sortBy(fn (DailySalesSummary $row) => $row->sale_date->toDateString())
                ->values();
        }

        if ($rows->isEmpty()) {
            return $this->emptyRangePayload();
        }

        return [
            'total_transactions' => (int) $rows->sum('total_transactions'),
            'total_revenue' => (float) $rows->sum('total_revenue'),
            'total_items_sold' => (int) $rows->sum('total_items_sold'),
            'daily_breakdown' => $rows->map(fn (DailySalesSummary $row) => (object) [
                'date' => $row->sale_date->toDateString(),
                'trx_count' => (int) $row->total_transactions,
                'revenue' => (float) $row->total_revenue,
            ]),
        ];
    }

    public function rebuildForDate(Branch $branch, Carbon $date): array
    {
        $payload = $this->buildFromTransactionsForDate($branch, $date);

        if (! $this->hasSummaryTable()) {
            return $payload;
        }

        $timestamp = now();
        $summaryValues = [
            'total_transactions' => $payload['total_transactions'],
            'total_revenue' => $payload['total_revenue'],
            'total_items_sold' => $payload['total_items_sold'],
            'updated_at' => $timestamp,
        ];

        $updated = DB::table('daily_sales_summaries')
            ->where('branch_id', $branch->id)
            ->whereDate('sale_date', $date->toDateString())
            ->update($summaryValues);

        if ($updated === 0) {
            $inserted = DB::table('daily_sales_summaries')->insertOrIgnore([
                'branch_id' => $branch->id,
                'sale_date' => $date->toDateString(),
                ...$summaryValues,
                'created_at' => $timestamp,
            ]);

            if ($inserted === 0) {
                DB::table('daily_sales_summaries')
                    ->where('branch_id', $branch->id)
                    ->whereDate('sale_date', $date->toDateString())
                    ->update($summaryValues);
            }
        }

        return $payload;
    }

    private function buildFromTransactionsForDate(Branch $branch, Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $aggregate = $this->successfulTransactions()
            ->where('branch_id', $branch->id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        $itemsSold = (int) DB::table('transaction_details as details')
            ->join('transactions as transactions', 'transactions.id', '=', 'details.transaction_id')
            ->where('transactions.branch_id', $branch->id)
            ->whereBetween('transactions.created_at', [$start, $end])
            ->whereRaw("UPPER(COALESCE(transactions.status, '')) = ?", ['SUCCESS'])
            ->sum('details.quantity');

        return [
            'total_transactions' => (int) ($aggregate->total_transactions ?? 0),
            'total_revenue' => (float) ($aggregate->total_revenue ?? 0),
            'total_items_sold' => $itemsSold,
        ];
    }

    private function buildRangeFromTransactions(Branch $branch, Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        $dailyTransactions = $this->successfulTransactions()
            ->where('branch_id', $branch->id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as trx_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dailyItems = DB::table('transaction_details as details')
            ->join('transactions as transactions', 'transactions.id', '=', 'details.transaction_id')
            ->where('transactions.branch_id', $branch->id)
            ->whereBetween('transactions.created_at', [$start, $end])
            ->whereRaw("UPPER(COALESCE(transactions.status, '')) = ?", ['SUCCESS'])
            ->selectRaw('DATE(transactions.created_at) as date, COALESCE(SUM(details.quantity), 0) as items_sold')
            ->groupByRaw('DATE(transactions.created_at)')
            ->pluck('items_sold', 'date');

        if ($dailyTransactions->isEmpty()) {
            return $this->emptyRangePayload();
        }

        return [
            'total_transactions' => (int) $dailyTransactions->sum('trx_count'),
            'total_revenue' => (float) $dailyTransactions->sum('revenue'),
            'total_items_sold' => (int) $dailyItems->sum(),
            'daily_breakdown' => $dailyTransactions->map(fn ($row) => (object) [
                'date' => (string) $row->date,
                'trx_count' => (int) $row->trx_count,
                'revenue' => (float) $row->revenue,
            ])->values(),
        ];
    }

    private function successfulTransactions(): Builder
    {
        return Transaction::query()->whereRaw("UPPER(COALESCE(status, '')) = ?", ['SUCCESS']);
    }

    private function emptyRangePayload(): array
    {
        return [
            'total_transactions' => 0,
            'total_revenue' => 0.0,
            'total_items_sold' => 0,
            'daily_breakdown' => collect(),
        ];
    }

    private function toPayload(DailySalesSummary $summary): array
    {
        return [
            'total_transactions' => (int) $summary->total_transactions,
            'total_revenue' => (float) $summary->total_revenue,
            'total_items_sold' => (int) $summary->total_items_sold,
        ];
    }

    private function hasSummaryTable(): bool
    {
        if ($this->summaryTableAvailable !== null) {
            return $this->summaryTableAvailable;
        }

        return $this->summaryTableAvailable = Schema::hasTable('daily_sales_summaries');
    }

    private function isMissingSummaryTableException(QueryException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), 'daily_sales_summaries')
            && str_contains(strtolower($exception->getMessage()), 'does not exist');
    }
}
