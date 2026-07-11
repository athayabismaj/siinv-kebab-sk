@extends('layouts.app')

@section('content')
@php
    $routePrefix = 'owner.transactions';

    $hasActiveFilters = request()->filled('search')
        || request()->filled('user_id')
        || request()->filled('payment_method_id')
        || request()->filled('date_from')
        || request()->filled('date_to');
@endphp

@push('styles')
<style>
    .transaction-monitor-card,
    .transaction-monitor-table-card {
        border: 1px solid rgb(226 232 240);
        background: rgb(255 255 255);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    .dark .transaction-monitor-card,
    .dark .transaction-monitor-table-card {
        border-color: rgb(30 41 59);
        background: rgb(15 23 42);
        box-shadow: none;
    }

    .transaction-monitor-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }

    .transaction-monitor-card {
        --tone-rgb: 37 99 235;
        position: relative;
        overflow: hidden;
        min-height: 112px;
        border-radius: 16px;
        padding: 18px 82px 18px 18px;
    }

    .transaction-monitor-card::after {
        content: "";
        position: absolute;
        right: -34px;
        top: -34px;
        width: 112px;
        height: 112px;
        border-radius: 999px;
        background: rgb(var(--tone-rgb) / .08);
        pointer-events: none;
    }

    .transaction-monitor-card > .relative {
        position: static;
    }

    .transaction-monitor-card > .relative > .min-w-0 {
        position: relative;
        z-index: 3;
    }

    .transaction-monitor-icon {
        display: inline-flex;
        position: absolute;
        right: 12px;
        top: 24px;
        width: 38px;
        height: 38px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgb(var(--tone-rgb) / .10);
        color: rgb(var(--tone-rgb));
        box-shadow: inset 0 0 0 1px rgb(var(--tone-rgb) / .16);
        transform: translateY(-50%);
        z-index: 2;
    }

    .transaction-monitor-label {
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: rgb(148 163 184);
    }

    .transaction-monitor-value {
        margin-top: 9px;
        font-size: 26px;
        line-height: 1;
        font-weight: 950;
        color: rgb(15 23 42);
        overflow-wrap: anywhere;
        font-variant-numeric: tabular-nums;
    }

    .dark .transaction-monitor-value {
        color: rgb(248 250 252);
    }

    .transaction-monitor-note {
        margin-top: 8px;
        font-size: 11px;
        font-weight: 700;
        color: rgb(100 116 139);
    }

    .dark .transaction-monitor-note {
        color: rgb(148 163 184);
    }

    .transaction-monitor-table-card {
        overflow: hidden;
        border-radius: 16px;
    }

    .transaction-monitor-table-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid rgb(226 232 240);
        padding: 14px 16px;
        background: rgb(248 250 252 / .72);
    }

    .dark .transaction-monitor-table-head {
        border-color: rgb(30 41 59);
        background: rgb(2 6 23 / .28);
    }

    .transaction-monitor-empty {
        display: flex;
        min-height: 220px;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 38px 18px;
        text-align: center;
    }

    .transaction-monitor-empty-icon {
        display: inline-flex;
        height: 46px;
        width: 46px;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: rgb(37 99 235 / .08);
        color: rgb(37 99 235);
        box-shadow: inset 0 0 0 1px rgb(37 99 235 / .12);
    }

    .dark .transaction-monitor-empty-icon {
        background: rgb(96 165 250 / .12);
        color: rgb(147 197 253);
        box-shadow: inset 0 0 0 1px rgb(96 165 250 / .18);
    }

    .transaction-monitor-mobile-card {
        border: 1px solid rgb(226 232 240);
        background: rgb(248 250 252 / .72);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    .dark .transaction-monitor-mobile-card {
        border-color: rgb(30 41 59);
        background: rgb(2 6 23 / .24);
        box-shadow: none;
    }

    .tone-blue { --tone-rgb: 37 99 235; }
    .tone-emerald { --tone-rgb: 5 150 105; }
    .tone-violet { --tone-rgb: 124 58 237; }
    .tone-amber { --tone-rgb: 217 119 6; }

    .dark .tone-blue { --tone-rgb: 96 165 250; }
    .dark .tone-emerald { --tone-rgb: 52 211 153; }
    .dark .tone-violet { --tone-rgb: 167 139 250; }
    .dark .tone-amber { --tone-rgb: 251 191 36; }
</style>
@endpush

<div class="space-y-5 max-w-full overflow-x-hidden">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <nav class="mb-2 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span>/</span>
                <span>Riwayat</span>
                <span>/</span>
                <span class="text-blue-600 dark:text-blue-400">Riwayat Transaksi</span>
            </nav>

            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Riwayat Transaksi
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                Pantau transaksi berdasarkan periode, pencarian kode, dan filter kasir.
            </p>
        </div>

        <div class="inline-flex w-fit items-center gap-2 rounded-xl border border-blue-100 bg-blue-50/70 px-3 py-2 text-xs font-black uppercase tracking-wider text-blue-700 shadow-sm dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
            <span class="h-2 w-2 rounded-full bg-blue-500"></span>
            <span>Periode:</span>
            <span class="normal-case tracking-normal text-slate-700 dark:text-slate-200">{{ $dateFrom->format('d M Y') }}</span>
            @if(!$dateFrom->isSameDay($dateTo))
                <span class="text-slate-400">-</span>
                <span class="normal-case tracking-normal text-slate-700 dark:text-slate-200">{{ $dateTo->format('d M Y') }}</span>
            @endif
        </div>
    </header>

    <form method="GET" action="{{ route($routePrefix.'.index') }}" id="filter-form" class="relative z-10">
        <div class="flex flex-col gap-3">
            <input type="hidden" name="type" id="hidden_type" value="{{ $type }}">
            <input type="hidden" name="date_from" id="hidden_date_from" value="{{ $dateFrom->toDateString() }}">
            <input type="hidden" name="date_to" id="hidden_date_to" value="{{ $dateTo->toDateString() }}">

            <div class="flex flex-col lg:flex-row gap-3 w-full">
                <div class="w-full lg:w-auto flex bg-slate-100 dark:bg-slate-800/50 p-1 rounded-xl border border-slate-200/50 dark:border-slate-700/50 shrink-0 overflow-x-auto no-scrollbar justify-start sm:justify-center">
                    <button type="button" onclick="changeType('daily')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'daily' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
                    <button type="button" onclick="changeType('weekly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'weekly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</button>
                    <button type="button" onclick="changeType('monthly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'monthly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
                </div>

                <div class="flex-1 flex items-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-1 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all w-full min-w-0">
                    <a href="{{ route($routePrefix.'.index', ['type' => $type, 'date_from' => $prevFrom, 'date_to' => $prevTo, 'search' => request('search'), 'user_id' => request('user_id'), 'payment_method_id' => request('payment_method_id')]) }}" title="Sebelumnya" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-700 dark:hover:text-blue-400 transition-all shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                    </a>

                    <div class="flex-1 flex px-3">
                        <input type="{{ $inputType }}" value="{{ $inputValue }}" onchange="updateDateRange(this, '{{ $type }}')" class="w-full text-center bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none dark:[color-scheme:dark]">
                    </div>

                    @if($isFuture)
                        <div class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-300 dark:text-slate-700 cursor-not-allowed shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    @else
                        <a href="{{ route($routePrefix.'.index', ['type' => $type, 'date_from' => $nextFrom, 'date_to' => $nextTo, 'search' => request('search'), 'user_id' => request('user_id'), 'payment_method_id' => request('payment_method_id')]) }}" title="Berikutnya" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-700 dark:hover:text-blue-400 transition-all shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    @endif
                </div>

                <div class="relative shrink-0" x-data="{ exportOpen: false }">
                    <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:bg-slate-800 dark:hover:bg-white active:scale-95 transition-all shadow-sm w-full h-full">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Ekspor Data
                        <svg class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    
                    <div x-show="exportOpen" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 rounded-xl bg-white shadow-lg border border-slate-100 dark:bg-slate-800 dark:border-slate-700 py-1 z-50 overflow-hidden"
                         style="display: none;">
                        
                        <a href="{{ route($routePrefix.'.export', array_merge(request()->query(), ['format' => 'html'])) }}" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-blue-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            Format HTML
                        </a>
                        
                        <a href="{{ route($routePrefix.'.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-rose-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-rose-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Format PDF
                        </a>
                        
                        <a href="{{ route($routePrefix.'.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-emerald-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Format Excel
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-3 w-full">
                <div class="flex-1 flex items-center gap-2 bg-white dark:bg-slate-800 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all min-w-0">
                    <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 110-14 7 7 0 010 14z"></path></svg>
                    <input type="text" name="search" id="search-input" value="{{ request('search') }}" placeholder="Cari Kode Transaksi / Kasir..." autocomplete="off" class="w-full bg-transparent border-none py-1.5 focus:ring-0 text-[13px] font-medium text-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none">
                </div>

                <div class="flex flex-row gap-3 md:w-[34rem]">
                    <select name="user_id" onchange="this.form.submit()" class="flex-1 min-w-0 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm text-[13px] font-medium text-slate-700 dark:text-slate-200">
                        <option value="">Semua Kasir</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" {{ (string) request('user_id') === (string) $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                        @endforeach
                    </select>

                    <select name="payment_method_id" onchange="this.form.submit()" class="flex-1 min-w-0 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm text-[13px] font-medium text-slate-700 dark:text-slate-200">
                        <option value="">Semua Pembayaran</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ (string) request('payment_method_id') === (string) $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                        @endforeach
                    </select>

                    @if($hasActiveFilters)
                        <a href="{{ route($routePrefix.'.index') }}" title="Reset Filter" class="inline-flex items-center justify-center shrink-0 w-10 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 rounded-xl hover:text-red-500 transition-all">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="transaction-monitor-summary">
        <article class="transaction-monitor-card tone-blue">
            <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="transaction-monitor-label">Jumlah Transaksi</p>
                    <p class="transaction-monitor-value">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
                    <p class="transaction-monitor-note">transaksi tercatat</p>
                </div>
                <span class="transaction-monitor-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 12h6m-6 7h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"></path></svg>
                </span>
            </div>
        </article>

        <article class="transaction-monitor-card tone-emerald">
            <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="transaction-monitor-label">Omzet</p>
                    <p class="transaction-monitor-value">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                    <p class="transaction-monitor-note">total penjualan</p>
                </div>
                <span class="transaction-monitor-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v-1m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
        </article>

        <article class="transaction-monitor-card tone-violet">
            <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="transaction-monitor-label">Rata-rata Transaksi</p>
                    <p class="transaction-monitor-value">Rp {{ number_format($avgTransaction, 0, ',', '.') }}</p>
                    <p class="transaction-monitor-note">nilai rata-rata</p>
                </div>
                <span class="transaction-monitor-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19V5m4 14v-7m4 7V9m4 10v-4m4 4H3"></path></svg>
                </span>
            </div>
        </article>

        <article class="transaction-monitor-card tone-amber">
            <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="transaction-monitor-label">Kasir Teraktif</p>
                    <p class="transaction-monitor-value truncate">{{ $topCashierName }}</p>
                    <p class="transaction-monitor-note">paling banyak transaksi</p>
                </div>
                <span class="transaction-monitor-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </span>
            </div>
        </article>
    </div>

    @forelse($groupedTransactions as $date => $items)
        <div class="transaction-monitor-table-card">
            <div class="transaction-monitor-table-head">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z"></path></svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="truncate text-xs font-black uppercase tracking-[0.16em] text-slate-800 dark:text-slate-100">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</h2>
                        <p class="mt-0.5 text-[11px] font-semibold text-slate-500 dark:text-slate-400">Rincian transaksi kasir pada tanggal ini</p>
                    </div>
                </div>
                <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-black text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">{{ $items->count() }} transaksi</span>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="border-b border-slate-100 bg-slate-50/70 text-[10px] font-black uppercase tracking-[0.14em] text-slate-400 dark:border-slate-800 dark:bg-slate-950/25 dark:text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5">Kode</th>
                            <th class="px-5 py-3.5">Kasir</th>
                            <th class="px-5 py-3.5">Pembayaran</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-center">Item</th>
                            <th class="px-5 py-3.5 text-right">Total</th>
                            <th class="px-5 py-3.5">Waktu</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($items as $trx)
                            @php
                                $statusRaw = strtolower(trim((string) ($trx->status ?? 'success')));
                                $isSuccess = $statusRaw === 'success';
                                $isVoid = $statusRaw === 'void';
                                $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount;
                                $voidReasonLabel = match (strtolower((string) $trx->void_reason)) {
                                    'restock' => 'Kembali Stok',
                                    'waste' => 'Waste',
                                    default => null,
                                };
                                $statusLabel = $isVoid ? 'VOID' : ($isSuccess ? ($isPaid ? 'Lunas' : 'Kurang') : strtoupper(str_replace('_', ' ', (string) $trx->status)));
                                $badgeClass = $isVoid
                                    ? 'bg-slate-100 text-slate-500 ring-1 ring-slate-200 line-through dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700'
                                    : ($isPaid ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/25' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/25');
                                $statusDotClass = $isVoid ? 'bg-slate-400' : ($isPaid ? 'bg-emerald-500' : 'bg-rose-500');
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-900/35">
                                <td class="px-5 py-4">
                                    <span class="font-mono text-xs font-black text-slate-800 dark:text-white {{ !$isSuccess ? 'line-through opacity-60' : '' }}">{{ $trx->transaction_code }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $trx->user->name ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $trx->paymentMethod->name ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col items-start gap-1">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black {{ $badgeClass }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                            {{ $statusLabel }}
                                        </span>
                                        @if($isVoid)
                                            <span class="text-[10px] font-semibold {{ $voidReasonLabel ? 'text-amber-700 dark:text-amber-300' : 'text-slate-400 dark:text-slate-500' }}">
                                                {{ $voidReasonLabel ? 'Alasan: '.$voidReasonLabel : 'Alasan belum tercatat' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex min-w-8 justify-center rounded-lg bg-slate-100 px-2 py-1 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $trx->details_count }}</span>
                                </td>
                                <td class="px-5 py-4 text-right font-black text-slate-900 dark:text-white">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-xs font-bold tabular-nums text-slate-400 dark:text-slate-500">{{ $trx->created_at->format('H:i') }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-600 hover:text-white dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20 dark:hover:bg-blue-500 dark:hover:text-white">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="md:hidden p-4 space-y-3">
                @foreach($items as $trx)
                    @php
                        $statusRaw = strtolower(trim((string) ($trx->status ?? 'success')));
                        $isSuccess = $statusRaw === 'success';
                        $isVoid = $statusRaw === 'void';
                        $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount;
                        $voidReasonLabel = match (strtolower((string) $trx->void_reason)) {
                            'restock' => 'Kembali Stok',
                            'waste' => 'Waste',
                            default => null,
                        };
                        $statusLabel = $isVoid ? 'VOID' : ($isSuccess ? ($isPaid ? 'Lunas' : 'Kurang') : strtoupper(str_replace('_', ' ', (string) $trx->status)));
                        $badgeClass = $isVoid
                            ? 'bg-slate-100 text-slate-500 ring-1 ring-slate-200 line-through dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700'
                            : ($isPaid ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/25' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/25');
                        $statusDotClass = $isVoid ? 'bg-slate-400' : ($isPaid ? 'bg-emerald-500' : 'bg-rose-500');
                    @endphp
                    <div class="transaction-monitor-mobile-card rounded-xl p-4">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-mono text-xs font-black break-all text-slate-800 dark:text-white {{ !$isSuccess ? 'line-through opacity-60' : '' }}">{{ $trx->transaction_code }}</p>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black {{ $badgeClass }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                {{ $statusLabel }}
                            </span>
                        </div>
                        @if($isVoid)
                            <p class="text-xs font-semibold {{ $voidReasonLabel ? 'text-amber-700 dark:text-amber-300' : 'text-slate-400 dark:text-slate-500' }}">
                                {{ $voidReasonLabel ? 'Alasan void: '.$voidReasonLabel : 'Alasan void belum tercatat' }}
                            </p>
                        @endif
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-white/70 p-2 dark:bg-slate-900/40">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Kasir</p>
                                <p class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $trx->user->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg bg-white/70 p-2 dark:bg-slate-900/40">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Pembayaran</p>
                                <p class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $trx->paymentMethod->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg bg-white/70 p-2 dark:bg-slate-900/40">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Waktu</p>
                                <p class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $trx->created_at->format('H:i') }}</p>
                            </div>
                            <div class="rounded-lg bg-white/70 p-2 dark:bg-slate-900/40">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Item</p>
                                <p class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $trx->details_count }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <p class="text-sm font-black text-slate-900 dark:text-white">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</p>
                            <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-black text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700">Detail</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="transaction-monitor-table-card transaction-monitor-empty">
            <span class="transaction-monitor-empty-icon">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14h6M9 10h6M7 3h10a2 2 0 012 2v16l-4-2-3 2-3-2-4 2V5a2 2 0 012-2z"></path></svg>
            </span>
            <div>
                <p class="text-sm font-black text-slate-700 dark:text-slate-100">Belum ada transaksi.</p>
                <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Tidak ada data pada rentang tanggal atau filter yang dipilih.</p>
            </div>
        </div>
    @endforelse

    @include('partials.pagination_simple', [
        'paginator' => $transactions,
        'label' => 'transaksi',
    ])
</div>
@endsection

@push('scripts')
<script>
function formatStr(d) {
    return d.getFullYear() + '-' + (d.getMonth() < 9 ? '0' : '') + (d.getMonth() + 1) + '-' + (d.getDate() < 10 ? '0' : '') + d.getDate();
}

function resolveWeekRange(dateObj) {
    let day = dateObj.getDay();
    let diff = day === 0 ? -6 : 1 - day;
    let start = new Date(dateObj);
    start.setDate(dateObj.getDate() + diff);
    let end = new Date(start);
    end.setDate(start.getDate() + 6);
    return { from: formatStr(start), to: formatStr(end) };
}

function changeType(newType) {
    document.getElementById('hidden_type').value = newType;
    let d = new Date();
    let from = '', to = '';

    if (newType === 'daily') {
        from = to = formatStr(d);
    } else if (newType === 'weekly') {
        const range = resolveWeekRange(d);
        from = range.from;
        to = range.to;
    } else if (newType === 'monthly') {
        let start = new Date(d.getFullYear(), d.getMonth(), 1);
        let end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        from = formatStr(start);
        to = formatStr(end);
    }

    document.getElementById('hidden_date_from').value = from;
    document.getElementById('hidden_date_to').value = to;
    document.getElementById('filter-form').submit();
}

function updateDateRange(input, type) {
    let val = input.value;
    if (!val) return;

    let from = '', to = '';
    if (type === 'daily') {
        from = to = val;
    } else if (type === 'weekly') {
        const range = resolveWeekRange(new Date(val));
        from = range.from;
        to = range.to;
    } else if (type === 'monthly') {
        let parts = val.split('-');
        let start = new Date(parts[0], parts[1] - 1, 1);
        let end = new Date(parts[0], parts[1], 0);
        from = formatStr(start);
        to = formatStr(end);
    }

    document.getElementById('hidden_date_from').value = from;
    document.getElementById('hidden_date_to').value = to;
    document.getElementById('filter-form').submit();
}

let timeout = null;
const searchInput = document.getElementById('search-input');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            document.getElementById('filter-form').submit();
        }, 500);
    });
}
</script>
@endpush
