<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportPeriod
{
    public static function resolveType(?string $type): string
    {
        return in_array($type, ['daily', 'weekly', 'monthly'], true) ? $type : 'daily';
    }

    public static function resolveDateRange(Request $request, string $type, bool $alignToPeriod = false): array
    {
        $today = now()->startOfDay();

        if (! $request->filled('date_from') || ! $request->filled('date_to')) {
            return self::defaultRange($type, $today);
        }

        try {
            $from = Carbon::parse((string) $request->input('date_from'))->startOfDay();
            $to = Carbon::parse((string) $request->input('date_to'))->startOfDay();
        } catch (\Throwable) {
            return self::defaultRange($type, $today);
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($alignToPeriod) {
            if ($type === 'weekly') {
                $from = $from->copy()->startOfWeek(Carbon::MONDAY);
                $to = $from->copy()->endOfWeek(Carbon::SUNDAY);
            } elseif ($type === 'monthly') {
                $from = $from->copy()->startOfMonth();
                $to = $from->copy()->endOfMonth();
            }
        }

        if ($from->greaterThan($today)) {
            $from = $today->copy();
        }

        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    public static function buildNavigator(string $type, Carbon $dateFrom): array
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

            return [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType];
        }

        if ($type === 'weekly') {
            $prevFrom = $dateFrom->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->isAfter($today);
            $inputValue = $dateFrom->format('Y-m-d');
            $inputType = 'date';

            return [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType];
        }

        $prevFrom = $dateFrom->copy()->subDay()->format('Y-m-d');
        $prevTo = $dateFrom->copy()->subDay()->format('Y-m-d');
        $nextFrom = $dateFrom->copy()->addDay()->format('Y-m-d');
        $nextTo = $dateFrom->copy()->addDay()->format('Y-m-d');
        $isFuture = $dateFrom->copy()->addDay()->isAfter($today);
        $inputValue = $dateFrom->format('Y-m-d');
        $inputType = 'date';

        return [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType];
    }

    private static function defaultRange(string $type, Carbon $today): array
    {
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
}
