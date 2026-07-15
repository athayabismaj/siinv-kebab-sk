@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
@php
    $type = $type ?? 'daily';
    $paymentSummary = $paymentSummary ?? [
        'cashTotal' => 0,
        'successCount' => 0,
        'canceledCount' => 0,
        'canceledTotal' => 0,
        'methods' => collect(),
    ];
    $salesTransactions = $salesTransactions ?? collect();
@endphp

<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Laporan Penjualan" 
        subtitle="Pantau omzet tunai, transaksi penjualan, dan rekap menu berdasarkan periode yang dipilih." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Laporan Penjualan">
        
        {{-- PERIODE BADGE (kanan atas) --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 shrink-0 mt-2 lg:mt-0">
            <div class="inline-flex w-full sm:w-auto items-center justify-center sm:justify-start gap-2 rounded-full bg-blue-50 border border-blue-100/50 px-3 py-1.5 dark:bg-blue-500/10 dark:border-blue-800/30 shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                <span class="text-[11px] font-bold tracking-wide text-blue-700 dark:text-blue-400 uppercase">
                    Periode Data:
                    @if($type === 'daily')
                        <span class="font-medium text-slate-700 dark:text-slate-300 ml-1 normal-case">{{ $selectedDate->format('d M Y') }}</span>
                    @elseif($type === 'weekly')
                        <span class="font-medium text-slate-700 dark:text-slate-300 ml-1 normal-case">{{ $selectedWeekStart->format('d M Y') }}</span>
                        <span class="mx-0.5 text-slate-400">-</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300 normal-case">{{ $selectedWeekEnd->format('d M Y') }}</span>
                    @else
                        <span class="font-medium text-slate-700 dark:text-slate-300 ml-1 normal-case">{{ $selectedMonth->translatedFormat('F Y') }}</span>
                    @endif
                </span>
            </div>
        </div>
    </x-page-header>

    {{-- FILTER SECTION --}}
    <div class="relative z-10 py-2 mb-2">
        <div class="flex flex-col lg:flex-row gap-3 w-full items-stretch lg:items-center">
            
            {{-- 1. TABS (KIRI) --}}
            <div class="flex rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 shrink-0">
                <a href="{{ route('owner.reports.sales', array_filter(['type' => 'daily'])) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'daily' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</a>
                <a href="{{ route('owner.reports.sales', array_filter(['type' => 'weekly'])) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'weekly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</a>
                <a href="{{ route('owner.reports.sales', array_filter(['type' => 'monthly'])) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'monthly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</a>
            </div>

            {{-- 2. DATE NAVIGATOR (TENGAH) --}}
            <div class="min-w-0 flex items-center px-1 w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 flex-1">
                {{-- Prev --}}
                @if($type === 'daily')
                    <a href="{{ route('owner.reports.sales', array_filter(['type' => $type, 'date' => $prevFrom])) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    </a>
                @elseif($type === 'weekly')
                    <a href="{{ route('owner.reports.sales', array_filter(['type' => $type, 'week_date' => $prevFrom])) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    </a>
                @else
                    <a href="{{ route('owner.reports.sales', array_filter(['type' => $type, 'month' => $prevFrom])) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    </a>
                @endif

                {{-- Date Input --}}
                @if($type === 'daily')
                    <input type="date"
                           value="{{ $selectedDate->toDateString() }}"
                           max="{{ now()->toDateString() }}"
                           data-base-url="{{ route('owner.reports.sales', array_filter(['type' => 'daily'])) }}"
                           data-param="date"
                           data-date-navigation
                           class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
                @elseif($type === 'weekly')
                    <input type="date"
                           value="{{ $selectedWeekStart->toDateString() }}"
                           max="{{ now()->toDateString() }}"
                           data-base-url="{{ route('owner.reports.sales', array_filter(['type' => 'weekly'])) }}"
                           data-param="week_date"
                           data-date-navigation
                           class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
                @else
                    <input type="month"
                           value="{{ $selectedMonth->format('Y-m') }}"
                           max="{{ now()->format('Y-m') }}"
                           data-base-url="{{ route('owner.reports.sales', array_filter(['type' => 'monthly'])) }}"
                           data-param="month"
                           data-date-navigation
                           class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
                @endif

                {{-- Next --}}
                @if($isFuture)
                    <span class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </span>
                @else
                    @if($type === 'daily')
                        <a href="{{ route('owner.reports.sales', array_filter(['type' => $type, 'date' => $nextFrom])) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @elseif($type === 'weekly')
                        <a href="{{ route('owner.reports.sales', array_filter(['type' => $type, 'week_date' => $nextFrom])) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @else
                        <a href="{{ route('owner.reports.sales', array_filter(['type' => $type, 'month' => $nextFrom])) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @endif
                @endif
            </div>

            {{-- 3. ACTIONS (KANAN) --}}
            <div class="flex flex-row gap-3 shrink-0">
                
                {{-- Tombol Atur Ulang --}}
                @php
                    $hasActiveFilters = request()->filled('date') || request()->filled('week_date') || request()->filled('month') || (request()->filled('type') && request('type') !== 'daily');
                @endphp
                @if($hasActiveFilters)
                    <a href="{{ route('owner.reports.sales') }}" class="inline-flex h-[38px] w-full sm:w-auto items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[13px] font-semibold text-slate-600 shadow-sm transition-all hover:bg-slate-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-rose-400 shrink-0 whitespace-nowrap">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        <span class="hidden sm:inline">Atur Ulang</span>
                    </a>
                @endif

                {{-- EXPORT DROPDOWN --}}
                <div class="relative w-full sm:w-auto" x-data="{ exportOpen: false }">
                    <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 h-[38px] bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Ekspor Laporan
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
                        
                        <a href="{{ route('owner.reports.sales.export', array_filter(['type' => $type, 'date' => request('date'), 'week_date' => request('week_date'), 'month' => request('month'), 'format' => 'html'])) }}" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-blue-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            Format HTML
                        </a>
                        <a href="{{ route('owner.reports.sales.export', array_filter(['type' => $type, 'date' => request('date'), 'week_date' => request('week_date'), 'month' => request('month'), 'format' => 'pdf'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-rose-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-rose-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Format PDF
                        </a>
                        <a href="{{ route('owner.reports.sales.export', array_filter(['type' => $type, 'date' => request('date'), 'week_date' => request('week_date'), 'month' => request('month'), 'format' => 'excel'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-emerald-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Format Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @php
            $stats = [
                ['label' => 'Omzet Tunai', 'value' => number_format($totalRevenue, 0, ',', '.'), 'desc' => 'total penjualan tunai', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2', 'color' => 'blue', 'unit' => 'Rp'],
                ['label' => 'Jumlah Transaksi', 'value' => number_format($totalTransactions), 'desc' => 'transaksi tercatat', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2', 'color' => 'emerald', 'unit' => 'kali'],
                ['label' => 'Rata-rata Transaksi', 'value' => number_format($avgTransaction, 0, ',', '.'), 'desc' => 'nilai rata-rata', 'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6', 'color' => 'violet', 'unit' => 'Rp'],
                ['label' => 'Item Terjual', 'value' => number_format($totalMenuSold), 'desc' => 'total item menu', 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5', 'color' => 'amber', 'unit' => 'item'],
            ];
        @endphp

        @foreach($stats as $stat)
            <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">{{ $stat['value'] }}</p>
                    </div>
                    <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-{{ $stat['color'] }}-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"></path></svg>
                    </span>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                    <span>
                        @if($stat['unit'] === 'Rp') Rp - @endif
                        {{ $stat['desc'] }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <section class="mt-8 mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="mb-4 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
            <span class="h-1.5 w-4 rounded-full bg-emerald-500"></span>
            Ringkasan Transaksi Tunai
        </h3>

        @php
            $paymentItems = [
                ['label' => 'Total Tunai', 'value' => 'Rp ' . number_format($paymentSummary['cashTotal'] ?? 0, 0, ',', '.'), 'note' => 'pembayaran kas berhasil', 'color' => 'emerald', 'icon' => 'M3 10h18M7 15h.01M11 15h2M5 7h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2z'],
                ['label' => 'Transaksi Berhasil', 'value' => number_format($paymentSummary['successCount'] ?? 0, 0, ',', '.'), 'note' => 'transaksi valid', 'color' => 'violet', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'Transaksi Dibatalkan', 'value' => number_format($paymentSummary['canceledCount'] ?? 0, 0, ',', '.'), 'note' => 'void/dibatalkan', 'color' => 'amber', 'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                ['label' => 'Total Pembatalan', 'value' => 'Rp ' . number_format($paymentSummary['canceledTotal'] ?? 0, 0, ',', '.'), 'note' => 'nilai transaksi void', 'color' => 'rose', 'icon' => 'M9 14l2 2 4-4M7 3h8l4 4v14H7V3z'],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($paymentItems as $item)
                <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/50 pl-3 pr-4 py-3.5 transition-colors hover:border-slate-200 hover:bg-slate-50 dark:border-slate-800/60 dark:bg-slate-800/30 dark:hover:bg-slate-800/50">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-{{ $item['color'] }}-100/50 text-{{ $item['color'] }}-600 dark:bg-{{ $item['color'] }}-500/20 dark:text-{{ $item['color'] }}-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $item['label'] }}</p>
                        <p class="mt-0.5 truncate text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ $item['note'] }}</p>
                    </div>
                    <div class="pl-2 text-right">
                        <p class="text-[13px] font-black tabular-nums text-slate-900 dark:text-white">{{ $item['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mt-8">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 flex items-center gap-2">
                    <span class="w-4 h-1 bg-blue-500 rounded-full"></span>
                    Riwayat Transaksi Penjualan
                </p>
                <p class="mt-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">Daftar transaksi tunai pada periode yang dipilih.</p>
            </div>
        </div>

        <div class="md:hidden p-4 space-y-4 bg-slate-50/30 dark:bg-slate-800/10" id="transaction-card-list">
            @forelse($salesTransactions as $transaction)
                @php
                    $statusRaw = strtolower((string) ($transaction->status ?? 'success'));
                    $isCanceled = $statusRaw === 'void';
                    $statusLabel = $isCanceled ? 'Dibatalkan' : 'Berhasil';
                    $paymentMethodRaw = trim((string) ($transaction->payment_method_name ?? ''));
                    $paymentMethodLabel = str_contains(strtolower($paymentMethodRaw), 'cash') || str_contains(strtolower($paymentMethodRaw), 'tunai')
                        ? 'Tunai'
                        : ($paymentMethodRaw !== '' ? $paymentMethodRaw : '-');
                @endphp
                <article class="transaction-mobile-card rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition-all hover:border-slate-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                    <div class="flex items-start justify-between gap-3 border-b border-dashed border-slate-100 pb-3 dark:border-slate-800">
                        <div class="min-w-0">
                            <p class="truncate font-mono text-xs font-black text-slate-800 dark:text-slate-100">{{ $transaction->transaction_code ?? '-' }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                                {{ \Carbon\Carbon::parse($transaction->created_at)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($transaction->created_at)->format('H:i') }}
                            </p>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black {{ $isCanceled ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/25' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/25' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $isCanceled ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-2 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        <div class="flex justify-between">
                            <span class="uppercase tracking-wider">Kasir</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $transaction->cashier_name ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="uppercase tracking-wider">Metode</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paymentMethodLabel }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="uppercase tracking-wider">Item</span>
                            <span class="font-bold text-slate-700 dark:text-slate-200">{{ number_format((float) $transaction->item_count, 0, ',', '.') }} item</span>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between rounded-xl bg-blue-50/50 px-4 py-3 ring-1 ring-blue-100/50 dark:bg-blue-500/5 dark:ring-blue-500/10">
                        <span class="text-[10px] font-black uppercase tracking-widest text-blue-600 dark:text-blue-400">Total Transaksi</span>
                        <span class="text-sm font-black text-slate-900 tabular-nums dark:text-white">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</span>
                    </div>
                </article>
            @empty
                <div data-empty-card="true" class="transaction-mobile-card px-6 py-16 text-center text-sm font-medium text-slate-400 dark:text-slate-500">
                    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-slate-400 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:text-slate-500 dark:ring-slate-800">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V7l-4-4H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    Belum ada transaksi penjualan pada periode ini.
                </div>
            @endforelse
        </div>

        <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[980px] text-sm text-left" id="transaction-table">
                <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                    <tr>
                        <th class="px-5 py-3.5">Kode Transaksi</th>
                        <th class="px-5 py-3.5">Waktu</th>
                        <th class="px-5 py-3.5">Kasir</th>
                        <th class="px-5 py-3.5">Metode Pembayaran</th>
                        <th class="px-5 py-3.5 text-center">Jumlah Item</th>
                        <th class="px-5 py-3.5 text-right">Total Transaksi</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody id="transaction-table-body">
                    @forelse($salesTransactions as $transaction)
                        @php
                            $statusRaw = strtolower((string) ($transaction->status ?? 'success'));
                            $isCanceled = $statusRaw === 'void';
                            $statusLabel = $isCanceled ? 'Dibatalkan' : 'Berhasil';
                            $paymentMethodRaw = trim((string) ($transaction->payment_method_name ?? ''));
                            $paymentMethodLabel = str_contains(strtolower($paymentMethodRaw), 'cash') || str_contains(strtolower($paymentMethodRaw), 'tunai')
                                ? 'Tunai'
                                : ($paymentMethodRaw !== '' ? $paymentMethodRaw : '-');
                        @endphp
                        <tr class="border-b border-dashed border-slate-100 last:border-none hover:bg-slate-50/80 transition-colors dark:border-slate-800/60 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-4 font-mono text-xs font-bold text-slate-700 dark:text-slate-200">{{ $transaction->transaction_code ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-500 dark:text-slate-400">
                                <p class="font-bold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($transaction->created_at)->translatedFormat('d M Y') }}</p>
                                <p class="mt-0.5 text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ \Carbon\Carbon::parse($transaction->created_at)->format('H:i') }}</p>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ $transaction->cashier_name ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-500 dark:text-slate-400">{{ $paymentMethodLabel }}</td>
                            <td class="px-5 py-4 text-center">
                                <span class="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ number_format((float) $transaction->item_count, 0, ',', '.') }}</span>
                            </td>
                            <td class="px-5 py-4 text-right font-black text-slate-900 dark:text-white">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black {{ $isCanceled ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/25' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/25' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isCanceled ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row="true">
                            <td colspan="7" class="px-6 py-20 text-center text-sm font-medium text-slate-400 dark:text-slate-500">
                                <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-slate-400 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:text-slate-500 dark:ring-slate-800">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V7l-4-4H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                Belum ada transaksi penjualan pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col items-center gap-3 border-t border-slate-100 px-5 py-4 text-center dark:border-slate-800 sm:flex-row sm:justify-between sm:text-left">
            <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500" id="transaction-pagination-info">Menampilkan transaksi pada periode ini.</p>
            <div class="flex items-center justify-center gap-2 sm:justify-end" id="transaction-pagination-controls"></div>
        </div>
    </section>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 flex items-center gap-2 uppercase tracking-widest">
                    <span class="h-1.5 w-4 rounded-full bg-blue-500"></span>
                    @if($type === 'daily')
                        Rincian Penjualan Menu | {{ $selectedDate->format('d M Y') }}
                    @elseif($type === 'weekly')
                        Rincian Penjualan Menu | {{ $selectedWeekStart->format('d M') }} - {{ $selectedWeekEnd->format('d M Y') }}
                    @else
                        Rincian Penjualan Menu | {{ $selectedMonth->translatedFormat('F Y') }}
                    @endif
                </p>
                <p class="mt-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">Rangkuman menu yang terjual pada periode yang dipilih.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left" id="breakdown-table">
                <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                    @if($type === 'daily')
                        <tr>
                            <th class="px-6 py-3.5">Nama Menu</th>
                            <th class="px-6 py-3.5 text-center">Jumlah Terjual</th>
                            <th class="px-6 py-3.5 text-right">Total Penjualan</th>
                        </tr>
                    @else
                        <tr>
                            <th class="px-6 py-3.5">Tanggal</th>
                            <th class="px-6 py-3.5 text-center">Transaksi</th>
                            <th class="px-6 py-3.5 text-right">Omzet</th>
                        </tr>
                    @endif
                </thead>
                <tbody id="table-body">
                    @if($type === 'daily')
                        @forelse($contributions as $row)
                            <tr class="border-b border-dashed border-slate-100 last:border-none hover:bg-slate-50/80 transition-colors dark:border-slate-800/60 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ $row->menu_name }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->total_qty) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                    <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->total_sales, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-20 text-center text-sm font-medium text-slate-400 dark:text-slate-500">
                                    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-slate-400 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:text-slate-500 dark:ring-slate-800">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                    </div>
                                    Belum ada transaksi pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    @elseif($type === 'weekly')
                        @forelse($weeklyBreakdown as $row)
                            <tr class="border-b border-dashed border-slate-100 last:border-none hover:bg-slate-50/80 transition-colors dark:border-slate-800/60 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->trx_count) }} transaksi</span>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                    <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->revenue, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-20 text-center text-sm font-medium text-slate-400 dark:text-slate-500">
                                    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-slate-400 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:text-slate-500 dark:ring-slate-800">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                    </div>
                                    Tidak ada data untuk periode ini.
                                </td>
                            </tr>
                        @endforelse
                    @else
                        @forelse($dailyBreakdown as $row)
                            <tr class="border-b border-dashed border-slate-100 last:border-none hover:bg-slate-50/80 transition-colors dark:border-slate-800/60 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->trx_count) }} transaksi</span>
                                </td>
                                <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                    <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->revenue, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-20 text-center text-sm font-medium text-slate-400 dark:text-slate-500">
                                    <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-slate-400 ring-1 ring-slate-100 dark:bg-slate-800/50 dark:text-slate-500 dark:ring-slate-800">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                    </div>
                                    Tidak ada data untuk periode ini.
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>
        <div class="flex flex-col items-center gap-3 border-t border-slate-100 px-5 py-4 text-center dark:border-slate-800 sm:flex-row sm:justify-between sm:text-left">
            <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500" id="pagination-info">Menampilkan rincian penjualan menu.</p>
            <div class="flex items-center justify-center gap-2 sm:justify-end" id="pagination-controls"></div>
        </div>
    </div>
</div>
@endsection
