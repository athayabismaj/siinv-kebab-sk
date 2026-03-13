@extends('layouts.app')

@section('content')
@php
    $monthValue = $selectedMonth->format('Y-m');
    $maxMonth = now()->format('Y-m');
@endphp

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">Laporan Penjualan</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ringkasan transaksi bulanan untuk owner.</p>
        </div>

        <div class="flex items-center gap-2">
            <form method="GET" action="{{ route('owner.reports.sales.monthly') }}">
                <input type="month"
                       name="month"
                       max="{{ $maxMonth }}"
                       value="{{ $monthValue }}"
                       onchange="this.form.submit()"
                       class="px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-700 dark:text-slate-200">
            </form>

            <a href="{{ route('owner.reports.sales.monthly.export', ['month' => $monthValue]) }}"
               class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                Export Laporan
            </a>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('owner.reports.sales.daily') }}"
           class="px-3 py-1.5 rounded-lg text-sm {{ request()->routeIs('owner.reports.sales') || request()->routeIs('owner.reports.sales.daily') ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
            Harian
        </a>
        <a href="{{ route('owner.reports.sales.monthly', ['month' => $monthValue]) }}"
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

    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Rincian Harian Bulan {{ $selectedMonth->translatedFormat('F Y') }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3 text-left">Tanggal</th>
                        <th class="px-5 py-3 text-left">Jumlah Transaksi</th>
                        <th class="px-5 py-3 text-left">Omzet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailyBreakdown as $row)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-5 py-3 text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ number_format($row->trx_count, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">Rp {{ number_format($row->revenue, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center text-slate-500">Belum ada transaksi pada bulan ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
