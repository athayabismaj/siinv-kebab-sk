@extends('layouts.app')

@section('content')
@php
    $routePrefix = 'admin.transactions';
    $transactionPaymentLabel = function ($name) {
        $value = trim((string) $name);
        return in_array(strtolower($value), ['cash', 'tunai'], true) ? 'Tunai' : ($value !== '' ? $value : '-');
    };
    $transactionVoidReasonLabel = function ($reason) {
        return match (strtolower(trim((string) $reason))) {
            'restock', 'kembali_stok', 'kembali stok' => 'Kembali ke Stok',
            'waste' => 'Bahan Terbuang',
            'input_error' => 'Kesalahan Input',
            'customer_cancel' => 'Pembatalan Pesanan',
            'other', 'lainnya' => 'Lainnya',
            default => null,
        };
    };
    $transactionStatusLabel = function ($status, bool $isVoid, bool $isSuccess) {
        if ($isVoid) {
            return 'Dibatalkan';
        }

        if ($isSuccess) {
            return 'Berhasil';
        }

        return ucwords(str_replace('_', ' ', strtolower((string) $status)));
    };

    $hasActiveFilters = request()->filled('search')
        || request()->filled('user_id')
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
                <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span>/</span>
                <span>Kasir</span>
                <span>/</span>
                <span class="text-blue-600 dark:text-blue-400">Riwayat Transaksi</span>
            </nav>

            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Riwayat Transaksi
            </h1>

            <p class="mt-2 max-w-2xl text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                Pantau transaksi kasir berdasarkan periode, pencarian kode, dan filter kasir.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 shrink-0 mt-2 lg:mt-0">
            <div class="inline-flex w-full sm:w-auto items-center justify-center sm:justify-start gap-2 rounded-full bg-blue-50 border border-blue-100/50 px-3 py-1.5 dark:bg-blue-500/10 dark:border-blue-800/30 shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                <span class="text-[11px] font-bold tracking-wide text-blue-700 dark:text-blue-400 uppercase">
                    Periode:
                    <span class="font-medium text-slate-700 dark:text-slate-300 ml-1 normal-case">{{ $dateFrom->format('d M Y') }}</span>
                    @if(!$dateFrom->isSameDay($dateTo))
                        <span class="mx-0.5 text-slate-400">-</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300 normal-case">{{ $dateTo->format('d M Y') }}</span>
                    @endif
                </span>
            </div>
        </div>
    </header>

    <form method="GET" action="{{ route($routePrefix.'.index') }}" id="filter-form" class="relative z-10">
        <div class="flex flex-col gap-3">
            <input type="hidden" name="type" id="hidden_type" value="{{ $type }}">
            <input type="hidden" name="date_from" id="hidden_date_from" value="{{ $dateFrom->toDateString() }}">
            <input type="hidden" name="date_to" id="hidden_date_to" value="{{ $dateTo->toDateString() }}">

            <div class="flex flex-col gap-3 w-full">
                
                {{-- BARIS 1: Periode & Navigasi Tanggal --}}
                <div class="flex flex-col md:flex-row gap-3 w-full">
                    {{-- 1. TABS (KIRI) --}}
                    <div class="flex rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 shrink-0">
                        <button type="button" onclick="changeType('daily')" class="flex-1 md:flex-none min-w-[80px] lg:px-4 flex items-center justify-center px-3 py-1.5 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'daily' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
                        <button type="button" onclick="changeType('weekly')" class="flex-1 md:flex-none min-w-[80px] lg:px-4 flex items-center justify-center px-3 py-1.5 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'weekly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</button>
                        <button type="button" onclick="changeType('monthly')" class="flex-1 md:flex-none min-w-[80px] lg:px-4 flex items-center justify-center px-3 py-1.5 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'monthly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
                    </div>

                    {{-- 2. DATE NAVIGATOR (KANAN BARIS 1) --}}
                    <div class="flex-1 min-w-0 flex items-center px-3 rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
                        <a href="{{ route($routePrefix.'.index', ['type' => $type, 'date_from' => $prevFrom, 'date_to' => $prevTo, 'user_id' => request('user_id')]) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                        </a>
                        
                        <input type="{{ $inputType }}" value="{{ $inputValue }}" onchange="updateDateRange(this, '{{ $type }}')" 
                               max="{{ $inputType === 'month' ? now()->format('Y-m') : now()->toDateString() }}"
                               class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">

                        @if($isFuture)
                            <div class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-300 dark:text-slate-700 cursor-not-allowed">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                            </div>
                        @else
                            <a href="{{ route($routePrefix.'.index', ['type' => $type, 'date_from' => $nextFrom, 'date_to' => $nextTo, 'user_id' => request('user_id')]) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- BARIS 2: Filter Ekstra & Aksi --}}
                <div class="flex flex-col md:flex-row gap-3 w-full">
                    {{-- 3. KASIR SELECTOR (KIRI BARIS 2) --}}
                    <div class="flex-1 shrink-0 min-w-0">
                        <select name="user_id" onchange="this.form.submit()" class="h-[38px] w-full rounded-xl border border-slate-200 bg-white px-4 text-center text-[13px] font-semibold text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                            <option value="">Semua Kasir</option>
                            @foreach($cashiers as $cashier)
                                <option value="{{ $cashier->id }}" {{ (string) request('user_id') === (string) $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 4. ACTIONS (KANAN BARIS 2) --}}
                    @if($hasActiveFilters)
                        <div class="flex flex-row gap-3 shrink-0 md:justify-end">
                            <a href="{{ route($routePrefix.'.index') }}" class="inline-flex flex-1 md:flex-none shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 h-[38px] text-[13px] font-semibold text-slate-600 shadow-sm transition-all hover:bg-slate-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-rose-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                <span>Atur Ulang</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="grid gap-4 items-start" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
        
        {{-- Jumlah Transaksi --}}
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Jumlah Transaksi</p>
                    <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-blue-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 12h6m-6 7h6M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"></path></svg>
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                <span>transaksi tercatat</span>
            </div>
        </div>

        {{-- Omzet --}}
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Omzet</p>
                    <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-emerald-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v-1m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                <span>total penjualan</span>
            </div>
        </div>

        {{-- Rata-rata Transaksi --}}
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Rata-rata Transaksi</p>
                    <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">Rp {{ number_format($avgTransaction, 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-violet-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19V5m4 14v-7m4 7V9m4 10v-4m4 4H3"></path></svg>
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                <span>nilai rata-rata</span>
            </div>
        </div>

        {{-- Kasir Teraktif --}}
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div class="min-w-0 pr-2">
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Kasir Teraktif</p>
                    <p class="mt-2 text-[28px] leading-none font-black text-slate-900 truncate dark:text-white">{{ $topCashierName }}</p>
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-amber-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                <span>paling banyak transaksi</span>
            </div>
        </div>

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
                                $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount; 
                                $isSuccess = strtolower($trx->status ?? 'success') === 'success';
                                $isVoid = strtolower($trx->status ?? '') === 'void';
                                $voidReasonLabel = $transactionVoidReasonLabel($trx->void_reason);
                                $statusLabel = $transactionStatusLabel($trx->status, $isVoid, $isSuccess);
                                $badgeClass = $isVoid
                                    ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/25'
                                    : ($isSuccess ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/25' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/25');
                                $statusDotClass = $isVoid ? 'bg-amber-500' : ($isSuccess ? 'bg-emerald-500' : 'bg-rose-500');
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-900/35">
                                <td class="px-5 py-4">
                                    <span class="font-mono text-xs font-black text-slate-800 dark:text-white {{ !$isSuccess ? 'line-through opacity-60' : '' }}">{{ $trx->transaction_code }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $trx->user->name ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $transactionPaymentLabel($trx->paymentMethod->name ?? null) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black {{ $badgeClass }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                            <span>{{ $statusLabel }}</span>
                                        </span>
                                        @if($isVoid)
                                            <details class="relative inline-block">
                                                <summary class="flex h-5 w-5 cursor-pointer list-none items-center justify-center rounded-full bg-white text-[11px] font-black text-slate-900 ring-1 ring-slate-300 transition hover:bg-slate-50 dark:bg-slate-950 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-900" style="list-style: none;" title="Lihat alasan pembatalan">!</summary>
                                                <div class="absolute left-0 top-full z-30 mt-2 w-48 rounded-xl border border-amber-100 bg-white p-3 text-left shadow-xl shadow-slate-900/10 dark:border-amber-500/20 dark:bg-slate-900 dark:shadow-black/30">
                                                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-300">Alasan Pembatalan</p>
                                                    <p class="mt-1 text-xs font-bold text-slate-700 dark:text-slate-100">{{ $voidReasonLabel ?: 'Alasan belum tercatat' }}</p>
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex min-w-8 justify-center rounded-lg bg-slate-100 px-2 py-1 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $trx->details_count }}</span>
                                </td>
                                <td class="px-5 py-4 text-right font-black text-slate-900 dark:text-white">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-xs font-bold tabular-nums text-slate-400 dark:text-slate-500">{{ $trx->created_at->format('H:i') }}</td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-600 hover:text-white dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-500/20 dark:hover:bg-blue-500 dark:hover:text-white">Lihat Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="md:hidden p-4 space-y-3">
                @foreach($items as $trx)
                    @php 
                        $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount; 
                        $isSuccess = strtolower($trx->status ?? 'success') === 'success';
                        $isVoid = strtolower($trx->status ?? '') === 'void';
                        $voidReasonLabel = $transactionVoidReasonLabel($trx->void_reason);
                        $statusLabel = $transactionStatusLabel($trx->status, $isVoid, $isSuccess);
                        $badgeClass = $isVoid
                            ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/25'
                            : ($isSuccess ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/25' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/25');
                        $statusDotClass = $isVoid ? 'bg-amber-500' : ($isSuccess ? 'bg-emerald-500' : 'bg-rose-500');
                    @endphp
                    <div class="transaction-monitor-mobile-card rounded-xl p-4">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-mono text-xs font-black break-all text-slate-800 dark:text-white {{ !$isSuccess ? 'line-through opacity-60' : '' }}">{{ $trx->transaction_code }}</p>
                            <span class="inline-flex shrink-0 items-center gap-1.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black {{ $badgeClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    <span>{{ $statusLabel }}</span>
                                </span>
                                @if($isVoid)
                                    <details class="relative inline-block">
                                        <summary class="flex h-5 w-5 cursor-pointer list-none items-center justify-center rounded-full bg-white text-[11px] font-black text-slate-900 ring-1 ring-slate-300 transition hover:bg-slate-50 dark:bg-slate-950 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-900" style="list-style: none;" title="Lihat alasan pembatalan">!</summary>
                                        <div class="absolute right-0 top-full z-30 mt-2 w-48 rounded-xl border border-amber-100 bg-white p-3 text-left shadow-xl shadow-slate-900/10 dark:border-amber-500/20 dark:bg-slate-900 dark:shadow-black/30">
                                            <p class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-300">Alasan Pembatalan</p>
                                            <p class="mt-1 text-xs font-bold text-slate-700 dark:text-slate-100">{{ $voidReasonLabel ?: 'Alasan belum tercatat' }}</p>
                                        </div>
                                    </details>
                                @endif
                            </span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-white/70 p-2 dark:bg-slate-900/40">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Kasir</p>
                                <p class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $trx->user->name ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg bg-white/70 p-2 dark:bg-slate-900/40">
                                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Pembayaran</p>
                                <p class="mt-1 font-bold text-slate-700 dark:text-slate-200">{{ $transactionPaymentLabel($trx->paymentMethod->name ?? null) }}</p>
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
                            <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-black text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700">Lihat Detail</a>
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
