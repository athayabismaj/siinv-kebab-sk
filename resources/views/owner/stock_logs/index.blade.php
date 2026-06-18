@extends('layouts.app')

@section('title', 'Log Perubahan Stok')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.stocks.partials.logs.header', [
        'dateDisplay' => $dateDisplay,
        'homeRoute' => route('owner.panel'),
        'homeLabel' => 'Beranda',
        'sectionLabel' => 'Operasional',
        'parentRoute' => route('owner.stocks.index'),
        'parentLabel' => 'Monitoring Stok',
        'currentLabel' => 'Log Perubahan Stok',
        'title' => 'Log Perubahan Stok',
        'description' => 'Jejak perubahan stok dari restok, pemakaian transaksi kasir, transfer stok harian, dan penyesuaian manual.',
    ])

    @include('admin.stocks.partials.logs.filters', [
        'activePeriod' => $period,
        'activeDate' => $selectedDate,
        'prevParams' => $prevParams,
        'nextParams' => $nextParams,
        'isNextDisabled' => $isNextDisabled,
        'logsRouteName' => 'owner.stock-logs.index',
        'exportRouteName' => 'owner.stock-logs.export',
    ])

    @include('admin.stocks.partials.logs.summary', ['summaryCards' => $summaryCards])

    @include('admin.stocks.partials.logs.table', ['logs' => $logs, 'groupedLogs' => $groupedLogs])
</div>
@endsection
