@extends('layouts.app')

@section('content')
@php
    $routePrefix = 'owner.transactions';

    $hasActiveFilters = request()->filled('search')
        || request()->filled('user_id')
        || request()->filled('payment_method_id')
        || request()->filled('date_from')
        || request()->filled('date_to');

    $today = now()->toDateString();
    $week  = now()->subDays(6)->toDateString();
    $month = now()->startOfMonth()->toDateString();
    $year  = now()->startOfYear()->toDateString();
@endphp

<div class="space-y-8 max-w-full overflow-x-hidden">

{{-- ═══ 1. HEADER ═══ --}}
    <div class="mb-8">
        
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Riwayat</span>
        </nav>
        
        {{-- Judul --}}
        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
            Riwayat Transaksi
        </h1>

        {{-- Deskripsi Halaman (Santai, 2 Baris) --}}
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
            Pantau semua aktivitas penjualan yang masuk ke outlet biar nggak ada yang terlewat.<br class="hidden sm:block mt-1">
            Tinggal sesuaikan filter tanggal atau kasir, cek detailnya, lalu export buat rekapan pembukuan.
        </p>

        {{-- Indikator Periode (Modern Pill Badge) --}}
        <div class="inline-flex items-center gap-2.5 px-3 py-1.5 bg-blue-50/50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 rounded-lg shadow-sm">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            <span class="text-[11px] sm:text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide">
                Periode Data:
                <span class="ml-1 text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $dateFrom->format('d M Y') }}</span>
                @if(!$dateFrom->isSameDay($dateTo))
                    <span class="mx-0.5 text-slate-400 normal-case">—</span>
                    <span class="text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $dateTo->format('d M Y') }}</span>
                @endif
            </span>
        </div>
        
    </div>

    {{-- ═══ 2. FILTER BAR (Flexbox Presisi & Fix Dark Mode) ═══ --}}
    <form method="GET" action="{{ route($routePrefix.'.index') }}" id="filter-form" class="relative group z-10 mb-6">
        <div class="flex flex-col gap-3">
            <input type="hidden" name="type" id="hidden_type" value="{{ $type }}">
            <input type="hidden" name="date_from" id="hidden_date_from" value="{{ $dateFrom->toDateString() }}">
            <input type="hidden" name="date_to" id="hidden_date_to" value="{{ $dateTo->toDateString() }}">

            {{-- 1. TOP ROW: Quick Tabs, Date Navigator, Export --}}
            <div class="flex flex-col lg:flex-row gap-3 w-full">
                
                {{-- Quick Tabs --}}
                <div class="w-full lg:w-auto flex bg-slate-100 dark:bg-slate-800/50 p-1.5 rounded-xl border border-slate-200/50 dark:border-slate-700/50 shrink-0 overflow-x-auto no-scrollbar justify-start sm:justify-center">
                    <button type="button" onclick="changeType('daily')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'daily' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
                    <button type="button" onclick="changeType('monthly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'monthly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
                    <button type="button" onclick="changeType('yearly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'yearly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Tahunan</button>
                </div>

                {{-- Date Navigator --}}
                <div class="flex-1 flex items-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm p-1 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all w-full min-w-0">
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

                {{-- Export Button --}}
                <a href="{{ route($routePrefix.'.export', request()->query()) }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:bg-slate-800 dark:hover:bg-white active:scale-95 transition-all shadow-sm shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Export Data
                </a>
            </div>

            {{-- 2. BOTTOM ROW: Search, Cashier, Payment --}}
            <div class="flex flex-col md:flex-row gap-3 w-full">
                
                {{-- Search Box --}}
                <div class="flex-1 flex items-center bg-white dark:bg-slate-800 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all min-w-0">
                    <svg class="w-5 h-5 text-slate-400 shrink-0 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" name="search" id="search-input" value="{{ request('search') }}" 
                           placeholder="Cari Kode Transaksi / Kasir..." autocomplete="off"
                           class="w-full bg-transparent border-none py-1.5 focus:ring-0 text-[13px] font-medium text-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none">
                </div>

                <div class="flex flex-row gap-3 flex-1">
                    {{-- Cashier --}}
                    <select name="user_id" onchange="this.form.submit()" 
                            class="flex-1 min-w-0 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm text-[13px] font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer outline-none transition-all appearance-none truncate">
                        <option value="">Semua Kasir</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" {{ (string) request('user_id') === (string) $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                        @endforeach
                    </select>

                    {{-- Payment Method --}}
                    <select name="payment_method_id" onchange="this.form.submit()" 
                            class="flex-1 min-w-0 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm text-[13px] font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer outline-none transition-all appearance-none truncate">
                        <option value="">Semua Pembayaran</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ (string) request('payment_method_id') === (string) $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                        @endforeach
                    </select>

                    {{-- Reset Button --}}
                    @if($hasActiveFilters)
                        <a href="{{ route($routePrefix.'.index') }}" title="Reset Filter"
                           class="inline-flex items-center justify-center shrink-0 w-[38px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 rounded-xl hover:text-red-500 hover:bg-red-50 hover:border-red-200 dark:hover:bg-red-500/10 dark:hover:text-red-400 transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </div>
            </div>

        </div>
    </form>
    
    {{-- ═══ 3. STATS CARDS ═══ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $stats = [
                ['label' => 'Total Transaksi', 'value' => number_format($totalTransactions, 0, ',', '.'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color' => 'blue', 'unit' => 'trx'],
                ['label' => 'Total Omzet', 'value' => number_format($totalRevenue, 0, ',', '.'), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald', 'unit' => 'Rp'],
                ['label' => 'Rata-rata / Trx', 'value' => number_format($avgTransaction, 0, ',', '.'), 'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'violet', 'unit' => 'Rp'],
                ['label' => 'Kasir Teraktif', 'value' => $topCashierName, 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'color' => 'orange', 'unit' => 'user']
            ];
        @endphp

        {{-- Total Transaksi --}}
        <div class="relative p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[2rem] group hover:border-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/5 dark:bg-blue-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">Total Transaksi</p>
                <div class="flex items-baseline gap-1">
                    <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">trx</span>
                </div>
            </div>
        </div>

        {{-- Total Omzet --}}
        <div class="relative p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[2rem] group hover:border-emerald-500/30 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/5 dark:bg-emerald-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">Total Omzet</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-bold text-slate-400">Rp</span>
                    <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ number_format($totalRevenue, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Rata-rata / Trx --}}
        <div class="relative p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[2rem] group hover:border-violet-500/30 hover:shadow-2xl hover:shadow-violet-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-violet-500/5 dark:bg-violet-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">Rata-rata / Trx</p>
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-bold text-slate-400">Rp</span>
                    <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ number_format($avgTransaction, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Kasir Teraktif --}}
        <div class="relative p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[2rem] group hover:border-orange-500/30 hover:shadow-2xl hover:shadow-orange-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-orange-500/5 dark:bg-orange-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-2xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">Kasir Teraktif</p>
                <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight truncate max-w-[120px]">{{ $topCashierName }}</p>
            </div>
        </div>
    </div>

    {{-- ═══ 4. TRANSACTION GROUPS ═══ --}}
    @forelse($groupedTransactions as $date => $items)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">

            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                <h2 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest">
                    {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
                </h2>
                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-[10px] font-bold text-slate-500 dark:text-slate-300">{{ $items->count() }} transaksi</span>
            </div>

            {{-- Mobile --}}
            <div class="md:hidden p-4 space-y-3">
                @foreach($items as $trx)
                    @php $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount; @endphp
                    <div class="rounded-xl border border-slate-100 dark:border-slate-800 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-bold text-slate-800 dark:text-white text-sm break-all">{{ $trx->transaction_code }}</p>
                            @if($isPaid)
                                <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Lunas</span>
                            @else
                                <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Kurang</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                            <span>Kasir</span><span class="font-semibold text-slate-700 dark:text-slate-200 text-right">{{ $trx->user->name ?? '-' }}</span>
                            <span>Pembayaran</span><span class="font-semibold text-slate-700 dark:text-slate-200 text-right">{{ $trx->paymentMethod->name ?? '-' }}</span>
                            <span>Item</span><span class="font-semibold text-slate-700 dark:text-slate-200 text-right">{{ $trx->details_count }}</span>
                            <span>Total</span><span class="font-semibold text-slate-700 dark:text-slate-200 text-right">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</span>
                            <span>Dibayar</span><span class="font-semibold text-slate-700 dark:text-slate-200 text-right">Rp {{ number_format($trx->paid_amount, 0, ',', '.') }}</span>
                            <span>Kembalian</span><span class="font-semibold text-slate-700 dark:text-slate-200 text-right">Rp {{ number_format($trx->change_amount, 0, ',', '.') }}</span>
                            <span>Waktu</span><span class="font-semibold text-slate-700 dark:text-slate-200 text-right">{{ $trx->created_at->format('H:i') }}</span>
                        </div>
                        <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                            Detail <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                @endforeach
            </div>

            {{-- Desktop --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                        <tr>
                            <th class="px-6 py-3">Kode</th>
                            <th class="px-6 py-3">Kasir</th>
                            <th class="px-6 py-3">Pembayaran</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-center">Item</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-right">Dibayar</th>
                            <th class="px-6 py-3 text-right">Kembalian</th>
                            <th class="px-6 py-3">Waktu</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                        @foreach($items as $trx)
                            @php $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount; @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-800 dark:text-white">{{ $trx->transaction_code }}</td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $trx->user->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $trx->paymentMethod->name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @if($isPaid)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Lunas</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Kurang</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 text-xs">{{ $trx->details_count }}</span>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                    <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($trx->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-slate-500 dark:text-slate-400">Rp {{ number_format($trx->paid_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-slate-500 dark:text-slate-400">Rp {{ number_format($trx->change_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs tabular-nums">{{ $trx->created_at->format('H:i') }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                                        Detail <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-900 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
            </div>
            <p class="text-slate-400 text-sm font-medium">Tidak ada transaksi dalam rentang tanggal yang dipilih.</p>
        </div>
    @endforelse

    {{-- ═══ 5. PAGINATION ═══ --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-400 dark:text-slate-500 text-center sm:text-left">
            Halaman <span class="font-bold text-slate-700 dark:text-slate-200">{{ $transactions->currentPage() }}</span>
            dari <span class="font-bold text-slate-700 dark:text-slate-200">{{ $transactions->lastPage() }}</span>
            &nbsp;·&nbsp; Total <span class="font-bold text-slate-700 dark:text-slate-200">{{ $transactions->total() }}</span> transaksi
        </p>
        <div class="flex items-center justify-center gap-1.5">
            @if ($transactions->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-300 dark:text-slate-700 cursor-not-allowed">‹ Prev</span>
            @else
                <a href="{{ $transactions->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">‹ Prev</a>
            @endif
            @if ($transactions->hasMorePages())
                <a href="{{ $transactions->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm shadow-blue-500/20">Next ›</a>
            @else
                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-300 dark:text-slate-700 cursor-not-allowed">Next ›</span>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function formatStr(d) { 
    return d.getFullYear() + '-' + (d.getMonth() < 9 ? '0' : '') + (d.getMonth()+1) + '-' + (d.getDate() < 10 ? '0' : '') + d.getDate(); 
}

function changeType(newType) {
    document.getElementById('hidden_type').value = newType;
    let d = new Date();
    let from = '', to = '';
    
    if(newType === 'daily') {
        from = to = formatStr(d);
    } else if(newType === 'monthly') {
        let start = new Date(d.getFullYear(), d.getMonth(), 1);
        let end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        from = formatStr(start);
        to = formatStr(end);
    } else if(newType === 'yearly') {
        let start = new Date(d.getFullYear(), 0, 1);
        let end = new Date(d.getFullYear(), 11, 31);
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
    } else if (type === 'monthly') {
        let parts = val.split('-');
        let start = new Date(parts[0], parts[1] - 1, 1);
        let end = new Date(parts[0], parts[1], 0);
        from = formatStr(start);
        to = formatStr(end);
    } else if (type === 'yearly') {
        let year = parseInt(val);
        let start = new Date(year, 0, 1);
        let end = new Date(year, 11, 31);
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
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => { document.getElementById('filter-form').submit(); }, 500);
    });
}
</script>
@endpush
