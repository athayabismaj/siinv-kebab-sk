@extends('layouts.app')

@section('title', 'Laporan Pengeluaran')

@section('content')
@php
    $routePrefix = $routePrefix ?? 'admin.reports';
    $canInput = $canInput ?? false;
    $hasActiveFilters = request()->filled('date_from') || request()->filled('date_to') || request('type', 'daily') !== 'daily';
@endphp

<div class="w-full space-y-6 overflow-x-hidden pb-10">
    
    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-4">
        <nav class="mb-3 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 sm:text-[11px]">
            <span class="text-slate-500 dark:text-slate-400">Keuangan</span>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-blue-600 dark:text-blue-400">Laporan Pengeluaran</span>
        </nav>

        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 lg:gap-8">
            <div class="flex-1">
                <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white mb-2">
                    Laporan Pengeluaran
                </h1>
                <p class="text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400 max-w-3xl">
                    Pantau pengeluaran operasional harian, mingguan, dan bulanan. Omzet dihitung dari transaksi penjualan menu. HPP bahan dapat dilihat di <strong>Laporan Stok Harian</strong>.
                </p>
            </div>

            {{-- BADGE PERIODE DATA --}}
            <div class="shrink-0 flex items-start">
                <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 border border-blue-100/50 dark:bg-blue-500/10 dark:border-blue-800/30 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </span>
                    <span class="text-[11px] font-bold tracking-wide text-blue-700 dark:text-blue-400 uppercase">
                        Periode Data:
                        <span class="font-medium text-slate-700 dark:text-slate-300 ml-1">{{ $dateFrom->format('d M Y') }}</span>
                        @if(!$dateFrom->isSameDay($dateTo))
                            <span class="mx-0.5 text-slate-400">-</span>
                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $dateTo->format('d M Y') }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= FILTER SECTION (Minimalist Full Width) ================= --}}
    <form method="GET" action="{{ route($routePrefix.'.cashflow') }}" id="filter-form" class="relative z-10 py-2 mb-2">
        <input type="hidden" name="type" id="hidden_type" value="{{ $type }}">
        <input type="hidden" name="date_from" id="hidden_date_from" value="{{ $dateFrom->toDateString() }}">
        <input type="hidden" name="date_to" id="hidden_date_to" value="{{ $dateTo->toDateString() }}">

        <div class="flex flex-col lg:flex-row gap-3 w-full items-center justify-between">
            
            <div class="flex w-full lg:w-auto rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 shrink-0">
                <button type="button" onclick="changeType('daily')" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $type === 'daily' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
                <button type="button" onclick="changeType('weekly')" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $type === 'weekly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</button>
                <button type="button" onclick="changeType('monthly')" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $type === 'monthly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
            </div>

            <div class="flex-1 flex items-center px-1 w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
                <a href="{{ route($routePrefix.'.cashflow', array_merge(request()->except(['page','date_from','date_to']), ['type' => $type, 'date_from' => $prevFrom, 'date_to' => $prevTo])) }}" 
                   class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                </a>

                <input type="{{ $inputType }}" value="{{ $inputValue }}" onchange="updateDateRange(this, '{{ $type }}')" 
                       class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">

                @if($isFuture)
                    <span class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </span>
                @else
                    <a href="{{ route($routePrefix.'.cashflow', array_merge(request()->except(['page','date_from','date_to']), ['type' => $type, 'date_from' => $nextFrom, 'date_to' => $nextTo])) }}" 
                       class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 w-full lg:w-auto shrink-0 justify-end">
                
                @if($hasActiveFilters)
                    <a href="{{ route($routePrefix.'.cashflow') }}" class="mr-2 inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-400 hover:text-red-500 transition-colors">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        Reset
                    </a>
                @endif
                
                <div class="relative w-full lg:w-auto" x-data="{ exportOpen: false }">
                    <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false" class="w-full lg:w-auto inline-flex items-center justify-center gap-2 px-5 h-[38px] bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Eksport Laporan
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
                        
                        <a href="{{ route($routePrefix.'.cashflow.export', array_merge(request()->query(), ['format' => 'html'])) }}" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-blue-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            Format HTML
                        </a>
                        
                        <a href="{{ route($routePrefix.'.cashflow.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-rose-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-rose-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Format PDF
                        </a>
                        
                        <a href="{{ route($routePrefix.'.cashflow.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-emerald-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Format Excel
                        </a>
                    </div>
                </div>


            </div>
        </div>
    </form>

    {{-- ================= SUMMARY CARDS ================= --}}
    @php $selisih = $salesRevenue - $expenseTotal; @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Omzet Kotor --}}
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Omzet Kotor</p>
                <div class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-500/20 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($salesRevenue, 0, ',', '.') }}</p>
            <p class="text-[10px] text-slate-400 mt-1">Rp · dari penjualan menu</p>
            <div class="absolute bottom-0 left-0 h-0.5 w-full bg-blue-500/30"></div>
        </div>

        {{-- Total Pengeluaran --}}
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[10px] font-bold text-rose-500 uppercase tracking-widest">Total Pengeluaran</p>
                <div class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-400 group-hover:bg-rose-100 dark:group-hover:bg-rose-500/20 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($expenseTotal, 0, ',', '.') }}</p>
            <p class="text-[10px] text-slate-400 mt-1">Rp · pengeluaran operasional</p>
            <div class="absolute bottom-0 left-0 h-0.5 w-full bg-rose-500/30"></div>
        </div>

        {{-- Jumlah Entri --}}
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Jumlah Entri</p>
                <div class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 group-hover:bg-slate-200 dark:group-hover:bg-slate-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($expenseCount, 0, ',', '.') }}</p>
            <p class="text-[10px] text-slate-400 mt-1">transaksi pengeluaran</p>
            <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-200 dark:bg-slate-700"></div>
        </div>

        {{-- Selisih --}}
        <div class="relative overflow-hidden p-5 col-span-2 lg:col-span-1 bg-white dark:bg-slate-900 border {{ $selisih >= 0 ? 'border-emerald-200 dark:border-emerald-800/50' : 'border-rose-200 dark:border-rose-800/50' }} rounded-2xl shadow-sm hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[10px] font-bold {{ $selisih >= 0 ? 'text-emerald-500' : 'text-rose-500' }} uppercase tracking-widest">Selisih</p>
                <div class="p-1.5 rounded-lg {{ $selisih >= 0 ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-400 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-500/20' : 'bg-rose-50 dark:bg-rose-500/10 text-rose-400 group-hover:bg-rose-100 dark:group-hover:bg-rose-500/20' }} transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black {{ $selisih >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} tabular-nums">{{ number_format($selisih, 0, ',', '.') }}</p>
            <p class="text-[10px] text-slate-400 mt-1">Rp · omzet &minus; pengeluaran</p>
            <div class="absolute bottom-0 left-0 h-0.5 w-full {{ $selisih >= 0 ? 'bg-emerald-500/40' : 'bg-rose-500/40' }}"></div>
        </div>

    </div>

    {{-- ================= DATA GROUP ================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">

        {{-- Table Header with Input Button --}}
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex items-center justify-between gap-3">
            <h3 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Rincian Pengeluaran</h3>
            @if($canInput)
                <a href="{{ route('admin.reports.cashflow.create') }}" class="inline-flex items-center gap-1.5 px-4 h-8 bg-blue-600 text-white text-[12px] font-semibold rounded-lg hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Input Pengeluaran
                </a>
            @endif
        </div>

        <div class="space-y-0 divide-y divide-slate-100 dark:divide-slate-800/60">
        @forelse($groupedEntries as $date => $items)
            <div>
                <div class="px-5 py-2.5 flex items-center justify-between gap-3 bg-slate-50/70 dark:bg-slate-800/40">
                    <h2 class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                        {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
                    </h2>
                    <span class="px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500">
                        {{ $items->count() }} entri
                    </span>
                </div>

                <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($items as $entry)
                        <div class="p-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                            <div class="flex justify-between items-start gap-3 mb-1.5">
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white text-sm">{{ $entry->source ?: '-' }}</p>
                                    <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-500 mt-0.5">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        {{ $entry->created_at?->format('H:i') }}
                                        <span class="text-slate-300 dark:text-slate-600">•</span>
                                        {{ $entry->creator->name ?? 'System' }}
                                    </div>
                                </div>
                                <p class="font-bold text-rose-600 text-sm whitespace-nowrap">- Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}</p>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">{{ $entry->note ?: 'Tidak ada catatan' }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20">
                            <tr>
                                <th class="px-6 py-3.5">Kategori & Catatan</th>
                                <th class="px-6 py-3.5 w-48">Diinput Oleh</th>
                                <th class="px-6 py-3.5 text-right w-48">Nominal Pengeluaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                            @foreach($items as $entry)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900 dark:text-white">{{ $entry->source ?: '-' }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $entry->note ?: 'Tidak ada catatan' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-slate-600 dark:text-slate-300 font-medium">{{ $entry->creator->name ?? 'System' }}</div>
                                        <div class="text-xs text-slate-400">{{ $entry->created_at?->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-rose-600 dark:text-rose-500">
                                        - Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center p-16 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-4 border border-slate-100 dark:border-slate-700">
                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-[13px] font-medium">Belum ada data pengeluaran pada periode ini.</p>
            </div>
        @endforelse
        </div>
    </div>

    {{-- ================= PAGINATION ================= --}}
    @if($entries->hasPages())
    <div class="mt-8">
        {{ $entries->links() }}
    </div>
    @endif
</div>

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
    } else {
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
    } else {
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
</script>
@endpush
@endsection
