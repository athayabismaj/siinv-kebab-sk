@extends('layouts.app')

@section('title', 'Riwayat Stok')

@section('content')
@php
    $activePeriod = $period ?? 'daily';
    $activeDate = $selectedDate ?? now()->startOfDay();
    $periodStart = $rangeStart ?? $activeDate->copy()->startOfDay();
    $periodEnd = $rangeEnd ?? $activeDate->copy()->endOfDay();

    $baseParams = array_filter([
        'period' => $activePeriod,
        'type' => request('type'),
    ], fn ($value) => $value !== null && $value !== '');

    $prevDate = $activeDate->copy();
    $nextDate = $activeDate->copy();

    if ($activePeriod === 'weekly') {
        $prevDate = $prevDate->subWeek();
        $nextDate = $nextDate->addWeek();
    } elseif ($activePeriod === 'monthly') {
        $prevDate = $prevDate->subMonth();
        $nextDate = $nextDate->addMonth();
    } else {
        $prevDate = $prevDate->subDay();
        $nextDate = $nextDate->addDay();
    }

    $prevParams = array_merge($baseParams, ['date' => $prevDate->toDateString()]);
    $nextParams = array_merge($baseParams, ['date' => $nextDate->toDateString()]);
    $isNextDisabled = $nextDate->startOfDay()->gt(now()->startOfDay());

    $dateDisplay = $activeDate->translatedFormat('d M Y');
    if ($activePeriod === 'monthly') {
        $dateDisplay = $periodStart->translatedFormat('F Y');
    } elseif ($activePeriod === 'weekly') {
        $dateDisplay = $periodStart->translatedFormat('d M Y') . ' - ' . $periodEnd->translatedFormat('d M Y');
    }
@endphp

<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.stocks.partials.logs.header', ['dateDisplay' => $dateDisplay])

    @include('admin.stocks.partials.logs.filters', [
        'activePeriod' => $activePeriod,
        'activeDate' => $activeDate,
        'prevParams' => $prevParams,
        'nextParams' => $nextParams,
        'isNextDisabled' => $isNextDisabled,
    ])

    @include('admin.stocks.partials.logs.summary', ['summary' => $summary])

    @include('admin.stocks.partials.logs.table', ['logs' => $logs])
</div>
@endsection
