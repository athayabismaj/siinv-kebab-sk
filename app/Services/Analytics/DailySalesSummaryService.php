<?php

namespace App\Services\Analytics;

use App\Models\DailySalesSummary;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DailySalesSummaryService
{
    private ?bool $summaryTableAvailable = null;

    public function getOrBuildForDate(Carbon $date): array
    {
        if (! $this->hasSummaryTable()) {
            return $this->buildFromTransactionsForDate($date);
        }

        $dateKey = $date->toDateString();

        try {
            $summary = DailySalesSummary::query()->whereDate('sale_date', $dateKey)->first();
        } catch (QueryException $exception) {
            if ($this->isMissingSummaryTableException($exception)) {
                $this->summaryTableAvailable = false;

                return $this->buildFromTransactionsForDate($date);
            }

            throw $exception;
        }

        if ($summary) {
            return $this->toPayload($summary);
        }

        return $this->rebuildForDate($date);
    }

    public function getRange(Carbon $startDate, Carbon $endDate, bool $live = false): array
    {
        if ($live || ! $this->hasSummaryTable()) {
            return $this->buildRangeFromTransactions($startDate, $endDate);
        }

        try {
            $rows = DailySalesSummary::query()
                ->whereBetween('sale_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('sale_date')
                ->get();
        } catch (QueryException $exception) {
            if ($this->isMissingSummaryTableException($exception)) {
                $this->summaryTableAvailable = false;

                return $this->buildRangeFromTransactions($startDate, $endDate);
            }

            throw $exception;
        }

        $today = now()->startOfDay();
        $rangeIncludesToday = $today->betweenIncluded(
            $startDate->copy()->startOfDay(),
            $endDate->copy()->endOfDay()
        );

        // Always merge with live aggregate for current day so reports stay in sync
        // right after checkout (no need to wait for scheduler).
        if ($rangeIncludesToday) {
            $todayPayload = $this->buildFromTransactionsForDate($today);
            $todayKey = $today->toDateString();

            $rows = $rows
                ->reject(fn (DailySalesSummary $row) => $row->sale_date->toDateString() === $todayKey)
                ->push(new DailySalesSummary([
                    'sale_date' => $todayKey,
                    'total_transactions' => $todayPayload['total_transactions'],
                    'total_revenue' => $todayPayload['total_revenue'],
                    'total_items_sold' => $todayPayload['total_items_sold'],
                ]))
                ->sortBy(fn (DailySalesSummary $row) => $row->sale_date->toDateString())
                ->values();
        }

        if ($rows->count() === 0) {
            return [
                'total_transactions' => 0,
                'total_revenue' => 0.0,
                'total_items_sold' => 0,
                'daily_breakdown' => collect(),
            ];
        }

        return [
            'total_transactions' => (int) $rows->sum('total_transactions'),
            'total_revenue' => (float) $rows->sum('total_revenue'),
            'total_items_sold' => (int) $rows->sum('total_items_sold'),
            'daily_breakdown' => $rows->map(function (DailySalesSummary $row) {
                return (object) [
                    'date' => $row->sale_date->toDateString(),
                    'trx_count' => (int) $row->total_transactions,
                    'revenue' => (float) $row->total_revenue,
                ];
            }),
        ];
    }

    public function rebuildForDate(Carbon $date): array
    {
        if (! $this->hasSummaryTable()) {
            return $this->buildFromTransactionsForDate($date);
        }

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $aggregate = Transaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        $itemsSold = (int) DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->sum('transaction_details.quantity');

        $payload = [
            'sale_date' => $date->toDateString(),
            'total_transactions' => (int) ($aggregate->total_transactions ?? 0),
            'total_revenue' => (float) ($aggregate->total_revenue ?? 0),
            'total_items_sold' => $itemsSold,
        ];

        $timestamp = now();
        $summaryValues = [
            'total_transactions' => $payload['total_transactions'],
            'total_revenue' => $payload['total_revenue'],
            'total_items_sold' => $payload['total_items_sold'],
            'updated_at' => $timestamp,
        ];

        $updated = DB::table('daily_sales_summaries')
            ->whereDate('sale_date', $payload['sale_date'])
            ->update($summaryValues);

        if ($updated === 0) {
            $inserted = DB::table('daily_sales_summaries')->insertOrIgnore([
                'sale_date' => $payload['sale_date'],
                'total_transactions' => $payload['total_transactions'],
                'total_revenue' => $payload['total_revenue'],
                'total_items_sold' => $payload['total_items_sold'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            if ($inserted === 0) {
                DB::table('daily_sales_summaries')
                    ->whereDate('sale_date', $payload['sale_date'])
                    ->update($summaryValues);
            }
        }

        return [
            'total_transactions' => $payload['total_transactions'],
            'total_revenue' => $payload['total_revenue'],
            'total_items_sold' => $payload['total_items_sold'],
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

    private function buildFromTransactionsForDate(Carbon $date): array
    {
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $aggregate = Transaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total_transactions, COALESCE(SUM(total_amount), 0) as total_revenue')
            ->first();

        $itemsSold = (int) DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->sum('transaction_details.quantity');

        return [
            'total_transactions' => (int) ($aggregate->total_transactions ?? 0),
            'total_revenue' => (float) ($aggregate->total_revenue ?? 0),
            'total_items_sold' => $itemsSold,
        ];
    }

    private function buildRangeFromTransactions(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();

        $dailyTransactions = Transaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as trx_count, COALESCE(SUM(total_amount), 0) as revenue')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dailyItems = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->selectRaw('DATE(transactions.created_at) as date, COALESCE(SUM(transaction_details.quantity), 0) as items_sold')
            ->groupByRaw('DATE(transactions.created_at)')
            ->pluck('items_sold', 'date');

        $dailyBreakdown = $dailyTransactions
            ->map(function ($row) {
                return (object) [
                    'date' => (string) $row->date,
                    'trx_count' => (int) $row->trx_count,
                    'revenue' => (float) $row->revenue,
                ];
            })
            ->values();

        return [
            'total_transactions' => (int) $dailyTransactions->sum('trx_count'),
            'total_revenue' => (float) $dailyTransactions->sum('revenue'),
            'total_items_sold' => (int) $dailyItems->sum(),
            'daily_breakdown' => $dailyBreakdown,
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
