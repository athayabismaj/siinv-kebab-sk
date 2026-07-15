@extends('layouts.app')

@section('title', 'Laporan Pemakaian Bahan')

@section('content')
@php
    $isOwner = request()->routeIs('owner.*');
    $usageRoute = $isOwner ? 'owner.reports.usage' : 'admin.reports.usage';
    $exportRoute = $isOwner ? 'owner.reports.usage.export' : 'admin.reports.usage.export';
    $stockRoute = $isOwner ? 'owner.stocks.index' : 'admin.stocks.logs';
@endphp

<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.reports.usage.partials.header', [
        'isOwner' => $isOwner,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'stockRoute' => $stockRoute,
    ])

    @if(!empty($runtimeError))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
            {{ $runtimeError }}
        </div>
    @endif

    @include('admin.reports.usage.partials.filters', [
        'usageRoute' => $usageRoute,
        'exportRoute' => $exportRoute,
        'type' => $type,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'prevFrom' => $prevFrom,
        'prevTo' => $prevTo,
        'nextFrom' => $nextFrom,
        'nextTo' => $nextTo,
        'isFuture' => $isFuture,
        'inputType' => $inputType,
        'inputValue' => $inputValue,
    ])

    @include('admin.reports.usage.partials.summary', ['summary' => $summary])

    @include('admin.reports.usage.partials.table', ['usageItems' => $usageItems])
</div>
@endsection
