@extends('layouts.app')

@section('title', 'Riwayat Stok')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.stocks.partials.logs.header', ['dateDisplay' => $dateDisplay])

    @include('admin.stocks.partials.logs.filters', [
        'activePeriod' => $period,
        'activeDate' => $selectedDate,
        'prevParams' => $prevParams,
        'nextParams' => $nextParams,
        'isNextDisabled' => $isNextDisabled,
    ])

    @include('admin.stocks.partials.logs.summary', ['summaryCards' => $summaryCards])

    @include('admin.stocks.partials.logs.table', ['logs' => $logs, 'groupedLogs' => $groupedLogs])
</div>
@endsection
