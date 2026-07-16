@extends('layouts.app')

@section('title', 'Dashboard Owner')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
@inject('stockSessionPresenter', 'App\View\Presenters\StockSessionPresenter')
@php
    $sessionPresentation = $stockSessionPresenter->present($dailyStockStatus['key'] ?? null, $dailyStockStatus['label'] ?? null);
@endphp

@push('styles')
@vite('resources/css/pages/owner-dashboard.css')
@endpush

<div class="owner-dashboard space-y-4">
    <x-page-header 
        title="Dashboard Owner" 
        subtitle="Pantau performa penjualan, target, stok, dan transaksi dari satu tempat." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Ringkasan Bisnis">
        
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="inline-flex h-10 w-fit items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3.5 text-[11px] font-bold text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                <svg class="h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 21h18M6 21V7l6-4 6 4v14M9 21v-6h6v6"></path></svg>
                {{ $branchScopeLabel ?? 'Semua Cabang' }}
            </div>
            <div class="inline-flex h-10 w-fit items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3.5 text-[11px] font-bold text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                {{ now()->translatedFormat('d F Y') }}
            </div>
        </div>
    </x-page-header>

    <section class="rounded-2xl border border-blue-200/60 bg-gradient-to-r from-blue-50/80 to-indigo-50/50 px-5 py-4 shadow-sm backdrop-blur-xl dark:border-blue-900/40 dark:from-blue-900/20 dark:to-indigo-900/10">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-100 dark:bg-slate-900 dark:text-blue-400 dark:ring-slate-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M6 21V7l6-4 6 4v14M9 21v-6h6v6"></path></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600/80 dark:text-blue-400/80">Konteks Cabang</p>
                    <p class="mt-0.5 truncate text-base font-black tracking-tight text-slate-900 dark:text-white">{{ $branchScopeLabel ?? 'Semua Cabang' }}</p>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $branchScopeDescription ?? 'Data dashboard mengikuti filter cabang aktif.' }}</p>
                </div>
            </div>
            <span class="inline-flex w-fit items-center rounded-lg bg-white/60 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-blue-700 shadow-sm ring-1 ring-blue-200/50 backdrop-blur-sm dark:bg-slate-900/60 dark:text-blue-300 dark:ring-blue-900/50">
                {{ number_format($activeBranchCount ?? 0) }} Cabang Aktif
            </span>
        </div>
    </section>

    <section class="owner-dashboard-kpis gap-3 sm:gap-4 mt-2">
        @foreach([
            ['label' => 'Omzet Hari Ini', 'value' => 'Rp ' . number_format($todayRevenue, 0, ',', '.'), 'note' => number_format($todayTransactionsCount) . ' transaksi', 'icon' => 'revenue'],
            ['label' => 'Selisih Hari Ini', 'value' => 'Rp ' . number_format($todayNetProfit, 0, ',', '.'), 'note' => 'omzet dikurangi pengeluaran tercatat', 'icon' => 'profit', 'success' => $todayNetProfit >= 0, 'danger' => $todayNetProfit < 0],
            ['label' => 'Target Hari Ini', 'value' => $targetProgress . '%', 'note' => 'Rp ' . number_format($targetRevenue, 0, ',', '.'), 'icon' => 'target'],
            ['label' => 'Pengeluaran Hari Ini', 'value' => 'Rp ' . number_format($todayExpenseTotal, 0, ',', '.'), 'note' => number_format($todayExpenseCount) . ' catatan', 'icon' => 'expense', 'danger' => true],
        ] as $metric)
            <article class="group flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:-translate-y-1 hover:shadow-md hover:border-slate-300/80 dark:border-slate-800/80 dark:bg-slate-900 dark:hover:border-slate-700">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border transition-colors duration-300 {{ ($metric['danger'] ?? false) ? 'border-rose-100 bg-rose-50 text-rose-600 group-hover:bg-rose-100 dark:border-rose-900/30 dark:bg-rose-500/10 dark:text-rose-400 dark:group-hover:bg-rose-500/20' : (($metric['success'] ?? false) ? 'border-emerald-100 bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 dark:border-emerald-900/30 dark:bg-emerald-500/10 dark:text-emerald-400 dark:group-hover:bg-emerald-500/20' : 'border-slate-100 bg-slate-50 text-slate-600 group-hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400 dark:group-hover:bg-slate-800') }}">
                    @if($metric['icon'] === 'revenue')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8M5 12a7 7 0 1114 0 7 7 0 01-14 0z"></path></svg>
                    @elseif($metric['icon'] === 'profit')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    @elseif($metric['icon'] === 'target')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"></path></svg>
                    @else
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3"></path></svg>
                    @endif
                </span>
                <div class="flex min-w-0 flex-col justify-center">
                    <p class="truncate text-[10px] font-black uppercase tracking-[0.15em] text-slate-400 dark:text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-1.5 truncate text-xl font-black tracking-tight leading-none {{ ($metric['danger'] ?? false) ? 'text-rose-600 dark:text-rose-400' : (($metric['success'] ?? false) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">{{ $metric['value'] }}</p>
                    <p class="mt-1.5 truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $metric['note'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <div class="owner-dashboard-main gap-4 mt-2">
        <section class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800/80 dark:bg-slate-900">
            <div class="border-b border-slate-100/80 px-5 py-4 dark:border-slate-800/80">
                <h2 class="text-base font-black tracking-tight text-slate-900 dark:text-white">Performa Penjualan</h2>
                <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">Omzet tujuh hari terakhir</p>
            </div>
            <div class="owner-dashboard-sales-list flex-1 p-5">
                @foreach($salesLast7Days as $day)
                    <div class="owner-dashboard-sales-row group">
                        <p class="truncate text-[11px] {{ $day['is_today'] ? 'font-black text-slate-900 dark:text-white' : 'font-bold text-slate-500 dark:text-slate-400' }}">
                            {{ $day['is_today'] ? 'Hari ini' : $day['label'] }}
                        </p>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800/50">
                            <div class="h-full rounded-full transition-all duration-500 group-hover:brightness-110 {{ $day['is_today'] ? 'bg-gradient-to-r from-blue-500 to-indigo-500 shadow-[0_0_10px_rgba(99,102,241,0.4)]' : 'bg-slate-300 dark:bg-slate-600' }}" style="width: {{ $day['bar_width'] }}%"></div>
                        </div>
                        <p class="text-right text-[11px] font-black tracking-tight {{ $day['is_today'] ? 'text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300' }}">Rp {{ number_format($day['omzet'], 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800/80 dark:bg-slate-900">
            <div class="flex h-full flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Target & Sesi Stok</p>
                        <div class="mt-2.5 flex items-center gap-2">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75 {{ $sessionPresentation->dotClass }}"></span>
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full {{ $sessionPresentation->dotClass }}"></span>
                            </span>
                            <h2 class="text-lg font-black tracking-tight {{ $sessionPresentation->textClass }}">{{ $sessionPresentation->label }}</h2>
                        </div>
                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $dailyStockStatus['description'] ?? 'Belum ada sesi stok harian.' }}</p>
                    </div>
                    <span class="rounded-lg border px-2.5 py-1 text-[10px] font-black uppercase tracking-widest {{ $sessionPresentation->badgeClass }}">Hari Ini</span>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/80">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.15em] text-slate-400">Progress Target</p>
                            <p class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-white">{{ $targetProgress }}%</p>
                        </div>
                        <a href="{{ route('owner.targets.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700/80">Atur</a>
                    </div>
                    <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800/60">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-1000" style="width: {{ $targetProgress }}%"></div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between gap-3 text-[11px] font-bold text-slate-500 dark:text-slate-400">
                        <span>Rp {{ number_format($todayRevenue, 0, ',', '.') }}</span>
                        <span>Rp {{ number_format($targetRevenue, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="owner-dashboard-session-metrics mt-4 divide-x divide-slate-100 rounded-xl border border-slate-200/80 bg-slate-50/50 dark:divide-slate-700/80 dark:border-slate-700/80 dark:bg-slate-800/30">
                    @foreach([
                        ['label' => 'Total', 'value' => $dailyStockStatus['total_sessions'] ?? 0],
                        ['label' => 'Aktif', 'value' => $dailyStockStatus['open_sessions'] ?? 0],
                        ['label' => 'Tutup', 'value' => $dailyStockStatus['closed_sessions'] ?? 0],
                    ] as $sessionMetric)
                        <div class="py-3.5 text-center transition hover:bg-white dark:hover:bg-slate-800/50">
                            <p class="text-xl font-black leading-none tracking-tight text-slate-900 dark:text-white">{{ number_format($sessionMetric['value']) }}</p>
                            <p class="mt-1.5 text-[10px] font-black uppercase tracking-[0.15em] text-slate-400">{{ $sessionMetric['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <nav class="owner-dashboard-shortcuts mt-4 gap-2.5 flex-1" aria-label="Aksi cepat owner">
                    @foreach([
                        ['route' => route('owner.reports.sales'), 'label' => 'Penjualan', 'path' => 'M9 17v-6m4 6V7m4 10v-4M5 21h14'],
                        ['route' => route('owner.transactions.index'), 'label' => 'Transaksi', 'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => route('owner.targets.index'), 'label' => 'Target', 'path' => 'M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z'],
                        ['route' => route('owner.reports.cashflow'), 'label' => 'Pengeluaran', 'path' => 'M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3'],
                    ] as $shortcut)
                        <a href="{{ $shortcut['route'] }}" class="group flex h-full items-center justify-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3 text-[11px] font-bold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700/80 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-500/50 dark:hover:bg-blue-900/20 dark:hover:text-blue-400">
                            <svg class="h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-blue-500 dark:group-hover:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $shortcut['path'] }}"></path></svg>
                            {{ $shortcut['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </section>
    </div>

    <div class="owner-dashboard-bottom gap-4 mt-2">
        <section class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800/80 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100/80 px-5 py-4 dark:border-slate-800/80">
                <div>
                    <h2 class="text-base font-black tracking-tight text-slate-900 dark:text-white">Stok Perlu Perhatian</h2>
                    <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ number_format($lowStockItems->count()) }} bahan di bawah minimum</p>
                </div>
                <a href="{{ route('owner.stocks.index') }}" class="inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-wider text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">Semua</a>
            </div>
            <div class="flex-1 divide-y divide-slate-100/80 overflow-y-auto dark:divide-slate-800/80">
                @forelse($lowStockItems as $item)
                    @php $isCritical = ($item['status_key'] ?? '') === 'critical'; @endphp
                    <div class="owner-dashboard-stock-row group px-5 py-3 transition hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="owner-dashboard-stock-icon h-9 w-9 shrink-0 rounded-xl {{ $isCritical ? 'bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400' : 'bg-amber-50 text-amber-500 dark:bg-amber-500/10 dark:text-amber-400' }}">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-black tracking-tight text-slate-800 dark:text-slate-200">{{ $item['name'] }}</p>
                                <p class="mt-0.5 truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">Tersisa: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $item['stock_label'] }}</span></p>
                            </div>
                        </div>
                        <div class="hidden sm:block"></div>
                        <span class="rounded-lg border px-2.5 py-1 text-[9px] font-black uppercase tracking-widest shadow-sm {{ $isCritical ? 'border-red-200/80 bg-white text-red-600 dark:border-red-900/40 dark:bg-slate-900 dark:text-red-400' : 'border-amber-200/80 bg-white text-amber-600 dark:border-amber-900/40 dark:bg-slate-900 dark:text-amber-400' }}">
                            {{ $item['status_label'] ?? 'Rendah' }}
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center px-5 py-12 text-center">
                        <svg class="h-10 w-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="mt-2 text-[11px] font-bold text-slate-500 dark:text-slate-400">Semua stok dalam keadaan aman.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section x-data="{ activeTab: 'menu' }" class="flex flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-800/80 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100/80 px-4 pt-2 dark:border-slate-800/80">
                <div class="flex gap-2">
                    <button type="button" @click="activeTab = 'menu'" :class="activeTab === 'menu' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'" class="border-b-[3px] px-2 py-3 text-[11px] font-black uppercase tracking-wider transition-colors duration-300">Menu Terlaris</button>
                    <button type="button" @click="activeTab = 'transaction'" :class="activeTab === 'transaction' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-400 hover:text-slate-600 dark:hover:text-slate-300'" class="border-b-[3px] px-2 py-3 text-[11px] font-black uppercase tracking-wider transition-colors duration-300">Transaksi Terbaru</button>
                </div>
                <a x-show="activeTab === 'transaction'" x-cloak href="{{ route('owner.transactions.index') }}" class="rounded-lg bg-slate-50 px-2.5 py-1.5 text-[9px] font-black uppercase tracking-widest text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200">Semua</a>
            </div>

            <div x-show="activeTab === 'menu'" class="flex-1 divide-y divide-slate-100/80 overflow-y-auto p-2 dark:divide-slate-800/80">
                @forelse($topMenusToday as $index => $menu)
                    <div class="group flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 transition hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-[10px] font-black text-slate-500 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">{{ $index + 1 }}</span>
                            <p class="truncate text-[11px] font-bold text-slate-800 dark:text-slate-200">{{ $menu->name }}</p>
                        </div>
                        <p class="shrink-0 rounded-lg bg-blue-50 px-2 py-1 text-[10px] font-black text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">{{ number_format($menu->sold_qty) }} pcs</p>
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-[11px] font-medium text-slate-500">Belum ada transaksi menu hari ini.</div>
                @endforelse
            </div>

            <div x-show="activeTab === 'transaction'" x-cloak class="flex-1 divide-y divide-slate-100/80 overflow-y-auto dark:divide-slate-800/80">
                @forelse($latestTransactions as $trx)
                    <a href="{{ route('owner.transactions.show', $trx->id) }}" class="owner-dashboard-transaction-row group px-5 py-3 transition hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                        <div>
                            <p class="truncate text-[11px] font-bold text-slate-800 transition group-hover:text-blue-600 dark:text-slate-200 dark:group-hover:text-blue-400">{{ $trx->transaction_code }}</p>
                            <p class="mt-0.5 truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">Oleh {{ optional($trx->user)->name ?? 'Sistem' }}</p>
                        </div>
                        <p class="truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $trx->created_at->format('d M H:i') }}</p>
                        <span class="rounded-lg bg-emerald-50 px-2 py-1 text-right text-[10px] font-black tracking-tight text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</span>
                    </a>
                @empty
                    <div class="px-5 py-8 text-center text-[11px] font-medium text-slate-500">Belum ada transaksi terbaru.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
