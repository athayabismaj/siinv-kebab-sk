@extends('layouts.app')

@section('title', 'Dashboard Owner')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
@php
    $stockStatusKey = $dailyStockStatus['key'] ?? 'not_opened';
    $stockStatusClass = match ($stockStatusKey) {
        'closed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300',
        'open' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300',
        default => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300',
    };
    $stockDotClass = match ($stockStatusKey) {
        'closed' => 'bg-emerald-500',
        'open' => 'bg-amber-500',
        default => 'bg-slate-400',
    };
@endphp

<div class="space-y-5 md:space-y-6">

    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-blue-600 dark:text-blue-400">Owner Overview</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Dashboard Owner
            </h1>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400">
                <span>Ringkasan penjualan, target, dan kondisi operasional hari ini.</span>
                <span class="hidden h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700 sm:inline-block"></span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-bold text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    {{ now()->translatedFormat('d M Y') }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(360px,0.75fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="p-5 md:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Omzet Hari Ini</p>
                        <p class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-white md:text-4xl">
                            Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                {{ number_format($todayTransactionsCount) }} Transaksi
                            </span>
                            @if($bestSeller)
                                <span class="inline-flex max-w-full items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-amber-700 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300">
                                    Terlaris: <span class="ml-1 truncate normal-case tracking-normal">{{ $bestSeller->name }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                    Belum ada penjualan
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="w-full rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/50 dark:shadow-none lg:max-w-[280px]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Target</p>
                                <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $targetProgress }}%</p>
                            </div>
                            <a href="{{ route('owner.targets.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-blue-600 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-slate-800">
                                Atur
                            </a>
                        </div>
                        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div class="h-full rounded-full bg-blue-600 transition-all duration-500 dark:bg-blue-500" style="width: {{ $targetProgress }}%"></div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 text-[11px] font-bold">
                            <span class="text-slate-600 dark:text-slate-300">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</span>
                            <span class="text-slate-400">Rp {{ number_format($targetRevenue, 0, ',', '.') }}</span>
                        </div>
                        <p class="mt-2 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                            @if($targetRevenue > 0)
                                Sisa target Rp {{ number_format($targetGap, 0, ',', '.') }}
                            @else
                                Target harian belum diatur.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Laba Bersih Hari Ini</p>
                        <p class="mt-2 text-2xl font-black {{ $todayNetProfit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            Rp {{ number_format($todayNetProfit, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-2.5 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m9-5a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/50">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Omzet</p>
                        <p class="mt-1 text-xs font-bold text-slate-700 dark:text-slate-200">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/50">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pengeluaran</p>
                        <p class="mt-1 text-xs font-bold text-rose-600 dark:text-rose-400">Rp {{ number_format($todayExpenseTotal, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Status Tutup Buku</p>
                        <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ $dailyStockStatus['label'] ?? 'Belum Dibuka' }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest {{ $stockStatusClass }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $stockDotClass }}"></span>
                        Hari Ini
                    </span>
                </div>
                <p class="mt-4 text-xs font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ $dailyStockStatus['description'] ?? 'Sesi stok harian belum dibuka.' }}
                </p>
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Transaksi Hari Ini</p>
                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($todayTransactionsCount) }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Jumlah transaksi sukses hari ini.</p>
                </div>
                <div class="rounded-xl bg-blue-50 p-2.5 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Menu Terlaris</p>
                    @if($bestSeller)
                        <p class="mt-2 truncate text-2xl font-black text-slate-900 dark:text-white" title="{{ $bestSeller->name }}">{{ $bestSeller->name }}</p>
                        <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ number_format($bestSeller->sold_qty) }} pcs terjual</p>
                    @else
                        <p class="mt-2 text-sm font-bold text-slate-500 dark:text-slate-400">Belum ada penjualan.</p>
                    @endif
                </div>
                <div class="rounded-xl bg-amber-50 p-2.5 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Pengeluaran Hari Ini</p>
                    <p class="mt-2 text-2xl font-black text-rose-600 dark:text-rose-400">Rp {{ number_format($todayExpenseTotal, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ number_format($todayExpenseCount, 0, ',', '.') }} transaksi pengeluaran.</p>
                </div>
                <div class="rounded-xl bg-rose-50 p-2.5 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800/70 dark:bg-slate-800/20">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Stok Hampir Habis</h2>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Monitoring bahan baku prioritas.</p>
                </div>
                <a href="{{ route('owner.stocks.index') }}" class="text-[10px] font-bold uppercase tracking-widest text-blue-600 hover:text-blue-700 dark:text-blue-400">Lihat</a>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/70">
                @forelse($lowStockItems as $item)
                    @php
                        $isCritical = ($item['status_key'] ?? '') === 'critical';
                        $badgeClass = $isCritical
                            ? 'border-red-200 bg-red-50 text-red-600 dark:border-red-900/60 dark:bg-red-500/10 dark:text-red-300'
                            : 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300';
                    @endphp
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $item['name'] }}</p>
                            <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Stok: {{ $item['stock_label'] }}</p>
                        </div>
                        <span class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-widest {{ $badgeClass }}">
                            {{ $item['status_label'] ?? 'Rendah' }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Stok aman</p>
                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Tidak ada bahan yang masuk batas minimum.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800/70 dark:bg-slate-800/20">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Grafik Penjualan</h2>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Omzet 7 hari terakhir.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">7 Hari</span>
            </div>

            <div class="space-y-4 p-5">
                @foreach($salesLast7Days as $day)
                    <div class="grid grid-cols-[76px_minmax(0,1fr)_86px] items-center gap-3">
                        <p class="truncate text-[11px] {{ $day['is_today'] ? 'font-black text-blue-600 dark:text-blue-400' : 'font-bold text-slate-500 dark:text-slate-400' }}">
                            {{ $day['is_today'] ? 'Hari ini' : $day['label'] }}
                        </p>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-blue-600 transition-all duration-500 dark:bg-blue-500" style="width: {{ $day['bar_width'] }}%"></div>
                        </div>
                        <p class="text-right text-[11px] font-black text-slate-600 dark:text-slate-300">
                            Rp {{ number_format($day['omzet'], 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800/70 dark:bg-slate-800/20">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Transaksi Terbaru</h2>
                <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Daftar transaksi terakhir yang masuk ke sistem.</p>
            </div>
            <a href="{{ route('owner.transactions.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold uppercase tracking-widest text-blue-600 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-blue-300 dark:hover:bg-blue-950/30">
                Lihat Semua
            </a>
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-100 bg-white text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:border-slate-800 dark:bg-slate-900">
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs dark:divide-slate-800/70">
                    @forelse($latestTransactions as $trx)
                        <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-800/30">
                            <td class="px-5 py-3 font-black text-slate-800 dark:text-slate-200">{{ $trx->transaction_code }}</td>
                            <td class="px-5 py-3 font-medium text-slate-500 dark:text-slate-400">{{ $trx->created_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3 text-right font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-10 text-center text-sm font-medium text-slate-500 dark:text-slate-400">Belum ada transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800/70 md:hidden">
            @forelse($latestTransactions as $trx)
                <div class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xs font-black text-slate-800 dark:text-slate-200">{{ $trx->transaction_code }}</p>
                        <p class="shrink-0 text-xs font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</p>
                    </div>
                    <p class="mt-1 text-[10px] font-bold text-slate-400">{{ $trx->created_at->format('d M Y H:i') }}</p>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Belum ada transaksi.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
