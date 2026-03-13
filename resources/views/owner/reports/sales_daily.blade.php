@extends('layouts.app')

@section('content')
@php
    $today = now()->toDateString();
    $dateValue = $selectedDate->toDateString();
    $prevDate = $selectedDate->copy()->subDay()->toDateString();
    $nextDate = $selectedDate->copy()->addDay()->toDateString();
    $isToday = $dateValue === $today;
@endphp

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">Laporan Penjualan</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ringkasan transaksi harian untuk owner.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('owner.reports.sales.daily', ['date' => $prevDate]) }}"
               class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"><-</a>

            <form method="GET" action="{{ route('owner.reports.sales.daily') }}">
                <input type="date"
                       name="date"
                       max="{{ $today }}"
                       value="{{ $dateValue }}"
                       onchange="this.form.submit()"
                       class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-700 dark:text-slate-200">
            </form>

            @if($isToday)
                <span class="px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-800 text-sm text-slate-400 dark:text-slate-500 cursor-not-allowed">-></span>
            @else
                <a href="{{ route('owner.reports.sales.daily', ['date' => $nextDate]) }}"
                   class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">-></a>
            @endif

            <a href="{{ route('owner.reports.sales.daily.export', ['date' => $dateValue]) }}"
               class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                Export Laporan
            </a>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('owner.reports.sales.daily', ['date' => $dateValue]) }}"
           class="px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('owner.reports.sales') || request()->routeIs('owner.reports.sales.daily') ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
            Harian
        </a>
        <a href="{{ route('owner.reports.sales.monthly') }}"
           class="px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('owner.reports.sales.monthly') ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
            Bulanan
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Omzet</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Jumlah Transaksi</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Rata-rata Transaksi</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">Rp {{ number_format($avgTransaction, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Menu Terlaris</p>
            @if($topMenu)
                <p class="mt-2 text-lg font-semibold text-slate-800 dark:text-white">{{ $topMenu->menu_name }}</p>
                <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ number_format($topMenu->total_qty, 0, ',', '.') }} terjual</p>
            @else
                <p class="mt-2 text-sm text-slate-500">Belum ada data penjualan.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Menu Paling Sedikit Terjual</p>
            @if($leastMenu)
                <p class="mt-2 text-lg font-semibold text-slate-800 dark:text-white">{{ $leastMenu->menu_name }}</p>
                <p class="text-sm text-amber-600 dark:text-amber-400">{{ number_format($leastMenu->total_qty, 0, ',', '.') }} terjual</p>
            @else
                <p class="mt-2 text-sm text-slate-500">Belum ada data penjualan.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Item Terjual</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">{{ number_format($totalMenuSold, 0, ',', '.') }} item</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Kontribusi Penjualan Menu (Harian)</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3 text-left">Menu</th>
                        <th class="px-5 py-3 text-left">Qty</th>
                        <th class="px-5 py-3 text-left">Kontribusi</th>
                        <th class="px-5 py-3 text-left">Penjualan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contributions as $item)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-5 py-3 font-medium text-slate-800 dark:text-white">{{ $item->menu_name }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ number_format($item->total_qty, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ number_format($item->contribution, 1, ',', '.') }}%</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">Tidak ada penjualan pada tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
