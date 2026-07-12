@extends('layouts.app')

@section('title', 'Dashboard Owner')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
@php
    $stockStatusKey = $dailyStockStatus['key'] ?? 'not_opened';
    $sessionTone = match ($stockStatusKey) {
        'closed' => [
            'dot' => 'bg-emerald-500',
            'text' => 'text-emerald-700 dark:text-emerald-300',
            'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300',
        ],
        'open' => [
            'dot' => 'bg-amber-500',
            'text' => 'text-amber-700 dark:text-amber-300',
            'badge' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300',
        ],
        default => [
            'dot' => 'bg-slate-400',
            'text' => 'text-slate-700 dark:text-slate-300',
            'badge' => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ],
    };
@endphp

@push('styles')
<style>
    .owner-dashboard-kpis,
    .owner-dashboard-main,
    .owner-dashboard-bottom {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
    }

    .owner-dashboard-sales-row {
        display: grid;
        grid-template-columns: 72px minmax(0, 1fr) 88px;
        align-items: center;
        gap: 12px;
    }

    .owner-dashboard-sales-list {
        display: grid;
        grid-template-rows: repeat(7, minmax(30px, 1fr));
        gap: 10px;
    }

    .owner-dashboard-session-metrics,
    .owner-dashboard-shortcuts {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .owner-dashboard-shortcuts {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .owner-dashboard-stock-row,
    .owner-dashboard-transaction-row {
        display: grid;
        align-items: center;
        gap: 12px;
    }

    .owner-dashboard-stock-row {
        grid-template-columns: minmax(130px, 1fr) minmax(110px, .7fr) auto;
        min-height: 45px;
    }

    .owner-dashboard-transaction-row {
        grid-template-columns: minmax(130px, 1fr) minmax(92px, .7fr) auto;
    }

    .owner-dashboard-stock-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .owner-dashboard-stock-icon > svg {
        display: block;
        flex: none;
    }

    [x-cloak] {
        display: none !important;
    }

    @media (max-width: 639px) {
        .owner-dashboard-sales-row {
            grid-template-columns: 56px minmax(0, 1fr) 72px;
            gap: 8px;
        }

        .owner-dashboard-stock-row,
        .owner-dashboard-transaction-row {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .owner-dashboard-stock-row > :nth-child(2),
        .owner-dashboard-transaction-row > :nth-child(2) {
            display: none;
        }
    }

    @media (min-width: 640px) {
        .owner-dashboard-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .owner-dashboard-kpis {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .owner-dashboard-main {
            grid-template-columns: minmax(0, 1.68fr) minmax(310px, 1fr);
            align-items: stretch;
        }

        .owner-dashboard-bottom {
            grid-template-columns: minmax(0, 1.16fr) minmax(350px, 0.84fr);
            align-items: start;
        }

        .owner-dashboard-main > *,
        .owner-dashboard-bottom > * {
            min-width: 0;
        }
    }
</style>
@endpush

<div class="owner-dashboard space-y-3">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-400">
                <span class="text-blue-600 dark:text-blue-400">Owner</span>
                <span>/</span>
                <span>Ringkasan Bisnis</span>
            </nav>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-white">Dashboard Owner</h1>
            <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                Pantau performa penjualan, target, stok, dan transaksi dari satu tempat.
            </p>
        </div>

        <div class="inline-flex h-9 w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-[11px] font-bold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            {{ now()->translatedFormat('d F Y') }}
        </div>
    </header>

    <section class="owner-dashboard-kpis gap-2.5">
        @foreach([
            ['label' => 'Omzet Hari Ini', 'value' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'), 'note' => number_format($todayTransactionsCount) . ' transaksi', 'icon' => 'revenue'],
            ['label' => 'Selisih Hari Ini', 'value' => 'Rp ' . number_format($todayNetProfit, 0, ',', '.'), 'note' => 'omzet dikurangi pengeluaran tercatat', 'icon' => 'profit', 'success' => $todayNetProfit >= 0, 'danger' => $todayNetProfit < 0],
            ['label' => 'Target Hari Ini', 'value' => $targetProgress . '%', 'note' => 'Rp ' . number_format($targetRevenue, 0, ',', '.'), 'icon' => 'target'],
            ['label' => 'Pengeluaran Hari Ini', 'value' => 'Rp ' . number_format($todayExpenseTotal, 0, ',', '.'), 'note' => number_format($todayExpenseCount) . ' catatan', 'icon' => 'expense', 'danger' => true],
        ] as $metric)
            <article class="flex min-h-[70px] items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border {{ ($metric['danger'] ?? false) ? 'border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900/60 dark:bg-rose-500/10 dark:text-rose-300' : (($metric['success'] ?? false) ? 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300') }}">
                    @if($metric['icon'] === 'revenue')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8M5 12a7 7 0 1114 0 7 7 0 01-14 0z"></path></svg>
                    @elseif($metric['icon'] === 'profit')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    @elseif($metric['icon'] === 'target')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"></path></svg>
                    @else
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3"></path></svg>
                    @endif
                </span>
                <div class="flex min-w-0 flex-col justify-center">
                    <p class="truncate text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $metric['label'] }}</p>
                    <p class="mt-1 truncate text-lg font-black leading-none {{ ($metric['danger'] ?? false) ? 'text-rose-600 dark:text-rose-400' : (($metric['success'] ?? false) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">{{ $metric['value'] }}</p>
                    <p class="mt-1 truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $metric['note'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <div class="owner-dashboard-main gap-3">
        <section class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Performa Penjualan</h2>
                <p class="mt-0.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">Omzet tujuh hari terakhir</p>
            </div>
            <div class="owner-dashboard-sales-list flex-1 p-4">
                @foreach($salesLast7Days as $day)
                    <div class="owner-dashboard-sales-row">
                        <p class="truncate text-[10px] {{ $day['is_today'] ? 'font-black text-slate-900 dark:text-white' : 'font-bold text-slate-500 dark:text-slate-400' }}">
                            {{ $day['is_today'] ? 'Hari ini' : $day['label'] }}
                        </p>
                        <div class="h-2 overflow-hidden rounded-sm bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-sm {{ $day['is_today'] ? 'bg-blue-600 dark:bg-blue-500' : 'bg-slate-300 dark:bg-slate-600' }}" style="width: {{ $day['bar_width'] }}%"></div>
                        </div>
                        <p class="text-right text-[10px] font-black text-slate-700 dark:text-slate-300">Rp {{ number_format($day['omzet'], 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Target & Sesi Stok</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $sessionTone['dot'] }}"></span>
                            <h2 class="text-base font-black {{ $sessionTone['text'] }}">{{ $dailyStockStatus['label'] ?? 'Belum Dibuka' }}</h2>
                        </div>
                        <p class="mt-1 text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $dailyStockStatus['description'] ?? 'Belum ada sesi stok harian.' }}</p>
                    </div>
                    <span class="rounded-md border px-2 py-1 text-[9px] font-bold uppercase tracking-wider {{ $sessionTone['badge'] }}">Hari Ini</span>
                </div>

                <div class="mt-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Progress Target</p>
                            <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">{{ $targetProgress }}%</p>
                        </div>
                        <a href="{{ route('owner.targets.index') }}" class="rounded-md border border-slate-200 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-blue-600 transition hover:bg-blue-50 dark:border-slate-700 dark:text-blue-300 dark:hover:bg-blue-950/20">Atur</a>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-sm bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-sm bg-blue-600 dark:bg-blue-500" style="width: {{ $targetProgress }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3 text-[10px] font-bold text-slate-500 dark:text-slate-400">
                        <span>Rp {{ number_format($todayRevenue, 0, ',', '.') }}</span>
                        <span>Rp {{ number_format($targetRevenue, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="owner-dashboard-session-metrics mt-3 divide-x divide-slate-200 rounded-lg border border-slate-200 dark:divide-slate-700 dark:border-slate-700">
                    @foreach([
                        ['label' => 'Total', 'value' => $dailyStockStatus['total_sessions'] ?? 0],
                        ['label' => 'Aktif', 'value' => $dailyStockStatus['open_sessions'] ?? 0],
                        ['label' => 'Tutup', 'value' => $dailyStockStatus['closed_sessions'] ?? 0],
                    ] as $sessionMetric)
                        <div class="py-3 text-center">
                            <p class="text-lg font-black leading-none text-slate-900 dark:text-white">{{ number_format($sessionMetric['value']) }}</p>
                            <p class="mt-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $sessionMetric['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <nav class="owner-dashboard-shortcuts mt-3 gap-2" aria-label="Aksi cepat owner">
                    @foreach([
                        ['route' => route('owner.reports.sales'), 'label' => 'Penjualan', 'path' => 'M9 17v-6m4 6V7m4 10v-4M5 21h14'],
                        ['route' => route('owner.transactions.index'), 'label' => 'Transaksi', 'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => route('owner.targets.index'), 'label' => 'Target', 'path' => 'M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z'],
                        ['route' => route('owner.reports.cashflow'), 'label' => 'Pengeluaran', 'path' => 'M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3'],
                    ] as $shortcut)
                        <a href="{{ $shortcut['route'] }}" class="flex h-10 items-center gap-2 rounded-lg border border-slate-200 px-3 text-[10px] font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-800 dark:hover:bg-blue-950/20">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $shortcut['path'] }}"></path></svg>
                            {{ $shortcut['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </section>
    </div>

    <div class="owner-dashboard-bottom gap-3">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Stok Perlu Perhatian</h2>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ number_format($lowStockItems->count()) }} bahan di bawah batas</p>
                </div>
                <a href="{{ route('owner.stocks.index') }}" class="text-[9px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Lihat</a>
            </div>
            <div class="max-h-[286px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @forelse($lowStockItems as $item)
                    @php $isCritical = ($item['status_key'] ?? '') === 'critical'; @endphp
                    <div class="owner-dashboard-stock-row px-4 py-2">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="owner-dashboard-stock-icon h-7 w-7 shrink-0 rounded-md bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </span>
                            <p class="truncate text-[11px] font-bold text-slate-800 dark:text-slate-200">{{ $item['name'] }}</p>
                        </div>
                        <p class="truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">Stok: {{ $item['stock_label'] }}</p>
                        <span class="rounded-full border px-2 py-0.5 text-[8px] font-bold uppercase tracking-wider {{ $isCritical ? 'border-red-200 text-red-600 dark:border-red-900/60 dark:text-red-300' : 'border-amber-200 text-amber-600 dark:border-amber-900/60 dark:text-amber-300' }}">
                            {{ $item['status_label'] ?? 'Rendah' }}
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Semua stok aman.</div>
                @endforelse
            </div>
        </section>

        <section x-data="{ activeTab: 'menu' }" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-3 dark:border-slate-800">
                <div class="flex">
                    <button type="button" @click="activeTab = 'menu'" :class="activeTab === 'menu' ? 'border-blue-600 text-slate-900 dark:text-white font-bold' : 'border-transparent text-slate-400 font-medium hover:text-slate-600 dark:hover:text-slate-300'" class="border-b-2 px-3 py-2.5 text-[10px] transition">Menu Terlaris</button>
                    <button type="button" @click="activeTab = 'transaction'" :class="activeTab === 'transaction' ? 'border-blue-600 text-slate-900 dark:text-white font-bold' : 'border-transparent text-slate-400 font-medium hover:text-slate-600 dark:hover:text-slate-300'" class="border-b-2 px-3 py-2.5 text-[10px] transition">Transaksi Terbaru</button>
                </div>
                <a x-show="activeTab === 'transaction'" x-cloak href="{{ route('owner.transactions.index') }}" class="text-[9px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Semua</a>
            </div>

            <div x-show="activeTab === 'menu'" class="max-h-[286px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @forelse($topMenusToday as $index => $menu)
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded border border-slate-200 text-[9px] font-black leading-none text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ $index + 1 }}</span>
                            <p class="truncate text-[10px] font-bold text-slate-800 dark:text-slate-200">{{ $menu->name }}</p>
                        </div>
                        <p class="shrink-0 text-[10px] font-black text-slate-700 dark:text-slate-300">{{ number_format($menu->sold_qty) }} pcs</p>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center text-[10px] font-medium text-slate-500">Belum ada transaksi.</div>
                @endforelse
            </div>

            <div x-show="activeTab === 'transaction'" x-cloak class="max-h-[286px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @forelse($latestTransactions as $trx)
                    <a href="{{ route('owner.transactions.show', $trx->id) }}" class="owner-dashboard-transaction-row px-3 py-2 transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <p class="truncate text-[10px] font-bold text-slate-800 dark:text-slate-200">{{ $trx->transaction_code }}</p>
                        <p class="truncate text-[9px] font-medium text-slate-500 dark:text-slate-400">{{ $trx->created_at->format('d M H:i') }}</p>
                        <p class="shrink-0 text-[10px] font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</p>
                    </a>
                @empty
                    <div class="px-3 py-6 text-center text-[10px] font-medium text-slate-500">Belum ada transaksi.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
