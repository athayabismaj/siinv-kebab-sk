@extends('layouts.app')

@section('title', 'Dashboard Owner')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
<div class="space-y-6 md:space-y-8">

    <div>
        <h1 class="text-xl md:text-2xl font-bold text-slate-800 dark:text-white">
            Dashboard Owner
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Ringkasan performa penjualan dan monitoring operasional
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm">
            <h2 class="text-sm text-slate-500 dark:text-slate-400">Omzet Hari Ini</h2>
            <p class="text-xl md:text-2xl font-bold text-emerald-600 mt-2">
                Rp {{ number_format($todayRevenue, 0, ',', '.') }}
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm">
            <h2 class="text-sm text-slate-500 dark:text-slate-400">Transaksi Hari Ini</h2>
            <p class="text-xl md:text-2xl font-bold text-slate-800 dark:text-white mt-2">
                {{ number_format($todayTransactionsCount) }} transaksi
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm sm:col-span-2 lg:col-span-1">
            <h2 class="text-sm text-slate-500 dark:text-slate-400">Menu Terlaris</h2>
            @if($bestSeller)
                <p class="text-base md:text-lg font-bold text-blue-700 mt-2 break-words">
                    {{ $bestSeller->name }}
                </p>
                <p class="text-sm text-slate-500 mt-1">
                    {{ number_format($bestSeller->sold_qty) }} pcs
                </p>
            @else
                <p class="text-sm text-slate-500 mt-2">Belum ada penjualan hari ini.</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-800 dark:text-white">Stok Hampir Habis</h2>
                <span class="text-xs text-slate-500">Monitoring</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($lowStockItems as $item)
                    @php
                        $isCritical = ($item['status_key'] ?? '') === 'critical';
                        $boxClass = $isCritical
                            ? 'border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/10'
                            : 'border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-900/10';
                        $badgeClass = $isCritical
                            ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
                    @endphp
                    <div class="rounded-xl border px-4 py-3 {{ $boxClass }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $item['name'] }}</p>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $badgeClass }}">
                                {{ $item['status_label'] ?? 'Rendah' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">{{ $item['stock_label'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">Tidak ada bahan yang mendekati minimum.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-800 dark:text-white">Grafik Penjualan (7 Hari)</h2>
                <span class="text-xs text-slate-500">Omzet</span>
            </div>

            <div class="mt-4 space-y-3">
                @foreach($salesLast7Days as $day)
                    <div class="grid grid-cols-[92px_1fr_auto] items-center gap-3">
                        <p class="text-xs {{ $day['is_today'] ? 'font-semibold text-blue-600' : 'text-slate-500' }}">
                            {{ $day['is_today'] ? 'Hari ini' : $day['label'] }}
                        </p>
                        <div class="h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-blue-600" style="width: {{ $day['bar_width'] }}%"></div>
                        </div>
                        <p class="text-xs font-medium text-slate-600 dark:text-slate-300 whitespace-nowrap">
                            Rp {{ number_format($day['omzet'], 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-800 dark:text-white">Transaksi Terbaru</h2>
            <a href="{{ route('owner.transactions.index') }}" class="text-xs text-blue-600 hover:underline">Lihat Semua</a>
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="px-6 py-3 text-left">Kode</th>
                        <th class="px-6 py-3 text-left">Waktu</th>
                        <th class="px-6 py-3 text-left">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestTransactions as $trx)
                        <tr class="border-t border-slate-200 dark:border-slate-800">
                            <td class="px-6 py-4 font-medium text-slate-800 dark:text-slate-100">{{ $trx->transaction_code }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ $trx->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden divide-y divide-slate-200 dark:divide-slate-800">
            @forelse($latestTransactions as $trx)
                <div class="px-4 py-3 space-y-1">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $trx->transaction_code }}</p>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 whitespace-nowrap">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</p>
                    </div>
                    <p class="text-xs text-slate-500">{{ $trx->created_at->format('d M Y H:i') }}</p>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">Belum ada transaksi.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection
