<?php

namespace App\Services\Shared;

use Carbon\Carbon;
use Illuminate\Http\Request;

class PeriodFilterService
{
    private const MAX_INTERACTIVE_DAYS = 90;

    /**
     * @param array<int, string> $allowedTypes
     */
    public function resolveType(string $type, array $allowedTypes = ['daily', 'weekly', 'monthly']): string
    {
        return in_array($type, $allowedTypes, true) ? $type : 'daily';
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveDateRange(Request $request, string $type): array
    {
        $today = now()->startOfDay();

        if (! $request->filled('date_from') || ! $request->filled('date_to')) {
            if ($type === 'monthly') {
                $from = $today->copy()->startOfMonth();
                $to = $today->copy()->endOfMonth();
            } elseif ($type === 'weekly') {
                $from = $today->copy()->startOfWeek(Carbon::MONDAY);
                $to = $today->copy()->endOfWeek(Carbon::SUNDAY);
            } else {
                $from = $today->copy();
                $to = $today->copy();
            }

            if ($to->greaterThan($today)) {
                $to = $today->copy();
            }

            return [$from, $to];
        }

        $from = Carbon::parse((string) $request->input('date_from'))->startOfDay();
        $to = Carbon::parse((string) $request->input('date_to'))->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->greaterThan($today)) {
            $from = $today->copy();
        }
        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        // Batas rentang laporan interaktif agar performa tetap stabil.
        $maxStart = $to->copy()->subDays(self::MAX_INTERACTIVE_DAYS - 1);
        if ($from->lessThan($maxStart)) {
            $from = $maxStart;
        }

        return [$from, $to];
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:bool,5:string,6:string}
     */
    public function buildNavigator(string $type, Carbon $dateFrom): array
    {
        $today = now()->startOfDay();

        if ($type === 'monthly') {
            $prevFrom = $dateFrom->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subMonth()->endOfMonth()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addMonth()->startOfMonth()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addMonth()->endOfMonth()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addMonth()->startOfMonth()->isAfter($today);
            $inputValue = $dateFrom->format('Y-m');
            $inputType = 'month';
        } elseif ($type === 'weekly') {
            $prevFrom = $dateFrom->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->isAfter($today);
            $inputValue = $dateFrom->format('Y-m-d');
            $inputType = 'date';
        } else {
            $prevFrom = $dateFrom->copy()->subDay()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subDay()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addDay()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addDay()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addDay()->isAfter($today);
            $inputValue = $dateFrom->format('Y-m-d');
            $inputType = 'date';
        }

        return [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType];
    }
}
