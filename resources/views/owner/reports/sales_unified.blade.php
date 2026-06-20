@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
@php
    $type = $type ?? 'daily';
@endphp

<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- HEADER --}}
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between mb-2">
        <div class="flex-1">
            <nav class="mb-3 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 sm:text-[11px]">
                <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-blue-600 dark:text-blue-400">Laporan Penjualan</span>
            </nav>

            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white mb-2">
                Laporan Penjualan
            </h1>

            <p class="text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400 max-w-3xl">
                Pantau performa omzet, jumlah transaksi, dan pergerakan menu terlaris secara mendetail.
            </p>
        </div>

        {{-- PERIODE BADGE (kanan atas) --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 shrink-0 mt-2 lg:mt-0">
            <div class="inline-flex w-full sm:w-auto items-center justify-center sm:justify-start gap-2 rounded-full bg-blue-50 border border-blue-100/50 px-3 py-1.5 dark:bg-blue-500/10 dark:border-blue-800/30 shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                <span class="text-[11px] font-bold tracking-wide text-blue-700 dark:text-blue-400 uppercase">
                    Periode:
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
    </div>

    {{-- FILTER BAR (sama persis dengan pemakaian bahan) --}}
    <div class="flex flex-col lg:flex-row gap-3 w-full relative z-10 items-center justify-between py-2">
        {{-- TAB TYPE --}}
        <div class="flex w-full lg:w-auto rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 shrink-0">
            <a href="{{ route('owner.reports.sales', ['type' => 'daily']) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'daily' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</a>
            <a href="{{ route('owner.reports.sales', ['type' => 'weekly']) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'weekly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</a>
            <a href="{{ route('owner.reports.sales', ['type' => 'monthly']) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'monthly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</a>
        </div>

        {{-- DATE NAVIGATOR --}}
        <div class="flex-1 flex items-center px-1 w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
            {{-- Prev --}}
            @if($type === 'daily')
                <a href="{{ route('owner.reports.sales', ['type' => $type, 'date' => $prevFrom]) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                </a>
            @elseif($type === 'weekly')
                <a href="{{ route('owner.reports.sales', ['type' => $type, 'week_date' => $prevFrom]) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                </a>
            @else
                <a href="{{ route('owner.reports.sales', ['type' => $type, 'month' => $prevFrom]) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                </a>
            @endif

            {{-- Date Input --}}
            @if($type === 'daily')
                <input type="date"
                       value="{{ $selectedDate->toDateString() }}"
                       data-base-url="{{ route('owner.reports.sales', ['type' => 'daily']) }}"
                       data-param="date"
                       onchange="onSalesDateChange(this)"
                       class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
            @elseif($type === 'weekly')
                <input type="date"
                       value="{{ $selectedWeekStart->toDateString() }}"
                       data-base-url="{{ route('owner.reports.sales', ['type' => 'weekly']) }}"
                       data-param="week_date"
                       onchange="onSalesDateChange(this)"
                       class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
            @else
                <input type="month"
                       value="{{ $selectedMonth->format('Y-m') }}"
                       data-base-url="{{ route('owner.reports.sales', ['type' => 'monthly']) }}"
                       data-param="month"
                       onchange="onSalesDateChange(this)"
                       class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
            @endif

            {{-- Next --}}
            @if($isFuture)
                <span class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                </span>
            @else
                @if($type === 'daily')
                    <a href="{{ route('owner.reports.sales', ['type' => $type, 'date' => $nextFrom]) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @elseif($type === 'weekly')
                    <a href="{{ route('owner.reports.sales', ['type' => $type, 'week_date' => $nextFrom]) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @else
                    <a href="{{ route('owner.reports.sales', ['type' => $type, 'month' => $nextFrom]) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endif
            @endif
        </div>

        {{-- EXPORT DROPDOWN --}}
        <div class="flex items-center w-full lg:w-auto shrink-0 justify-end" x-data="{ exportOpen: false }">
            <div class="relative w-full lg:w-auto">
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
                    
                    <a href="{{ route('owner.reports.sales.export', ['type' => $type, 'date' => request('date'), 'week_date' => request('week_date'), 'month' => request('month'), 'format' => 'html']) }}" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-blue-400 font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        Format HTML
                    </a>
                    <a href="{{ route('owner.reports.sales.export', ['type' => $type, 'date' => request('date'), 'week_date' => request('week_date'), 'month' => request('month'), 'format' => 'pdf']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-rose-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-rose-400 font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Format PDF
                    </a>
                    <a href="{{ route('owner.reports.sales.export', ['type' => $type, 'date' => request('date'), 'week_date' => request('week_date'), 'month' => request('month'), 'format' => 'excel']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-emerald-400 font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Format Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $stats = [
                ['label' => 'Omzet', 'value' => number_format($totalRevenue, 0, ',', '.'), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2', 'color' => 'blue', 'unit' => 'Rp'],
                ['label' => 'Jumlah Transaksi', 'value' => number_format($totalTransactions), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2', 'color' => 'emerald', 'unit' => 'kali'],
                ['label' => 'Rata-rata Transaksi', 'value' => number_format($avgTransaction, 0, ',', '.'), 'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6', 'color' => 'violet', 'unit' => 'Rp'],
                ['label' => 'Item Terjual', 'value' => number_format($totalMenuSold), 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5', 'color' => 'orange', 'unit' => 'item'],
            ];
        @endphp

        @foreach($stats as $stat)
            <div class="relative p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl group hover:border-{{ $stat['color'] }}-500/30 hover:shadow-2xl hover:shadow-{{ $stat['color'] }}-500/10 transition-all duration-500 overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-{{ $stat['color'] }}-500/5 dark:bg-{{ $stat['color'] }}-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="relative flex flex-col items-center text-center">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-900/20 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"></path></svg>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">{{ $stat['label'] }}</p>
                    <div class="flex items-baseline gap-1">
                        @if($stat['unit'] === 'Rp') <span class="text-xs font-bold text-slate-400">Rp</span> @endif
                        <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $stat['value'] }}</p>
                        @if($stat['unit'] !== 'Rp') <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-0.5">{{ $stat['unit'] }}</span> @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-5">
            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-5 flex items-center gap-2">
                    <span class="w-4 h-1 bg-blue-500 rounded-full"></span>
                    Menu Terlaris
                </p>
                @if($topMenu)
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 shrink-0 rounded-2xl bg-blue-50 dark:bg-slate-800 flex items-center justify-center ring-1 ring-blue-100 dark:ring-slate-700">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-base font-black text-slate-900 dark:text-white leading-tight truncate">{{ $topMenu->menu_name }}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold ring-1 ring-slate-200 dark:ring-slate-700">{{ number_format($topMenu->total_qty) }}x terjual</span>
                                <span class="text-xs font-black text-blue-600 dark:text-blue-400">Rp {{ number_format($topMenu->total_sales, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="py-8 text-center text-slate-400 italic text-sm">Belum ada data</div>
                @endif
            </div>

            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-5 flex items-center gap-2">
                    <span class="w-4 h-1 bg-emerald-500 rounded-full"></span>
                    Andil Penjualan
                </p>
                <div class="space-y-4">
                    @forelse($contributions->take(5) as $idx => $item)
                        @php
                            $colors = ['bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-orange-500', 'bg-rose-500'];
                            $color = $colors[$idx % count($colors)];
                        @endphp
                        <div>
                            <div class="flex justify-between items-baseline mb-1.5">
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 truncate max-w-[60%]">{{ $item->menu_name }}</span>
                                <span class="text-xs font-black text-slate-500 dark:text-slate-400 shrink-0">{{ $item->contribution }}%</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full {{ $color }} rounded-full transition-all duration-500" style="width:{{ $item->contribution }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-slate-400 italic text-xs">Belum ada data kontribusi</div>
                    @endforelse
                    @if($contributions->count() > 5)
                        <p class="text-center text-[10px] text-slate-400 italic pt-1">+{{ $contributions->count() - 5 }} menu lainnya</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest">
                            @if($type === 'daily')
                                Breakdown Menu | {{ $selectedDate->format('d M Y') }}
                            @elseif($type === 'weekly')
                                Rincian Mingguan | {{ $selectedWeekStart->format('d M') }} - {{ $selectedWeekEnd->format('d M Y') }}
                            @else
                                Rincian Harian | {{ $selectedMonth->translatedFormat('F Y') }}
                            @endif
                        </p>
                        <p class="text-[10px] text-slate-400 mt-0.5" id="pagination-info"></p>
                    </div>
                    <div class="flex items-center gap-2" id="pagination-controls"></div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" id="breakdown-table">
                        <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                            @if($type === 'daily')
                                <tr>
                                    <th class="px-6 py-3">Nama Item</th>
                                    <th class="px-6 py-3 text-center">Terjual</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            @else
                                <tr>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3 text-center">Transaksi</th>
                                    <th class="px-6 py-3 text-right">Omzet</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50" id="table-body">
                            @if($type === 'daily')
                                @forelse($contributions as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ $row->menu_name }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->total_qty) }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                            <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->total_sales, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-6 py-20 text-center text-slate-400 italic text-sm">Belum ada transaksi pada periode ini.</td></tr>
                                @endforelse
                            @elseif($type === 'weekly')
                                @forelse($weeklyBreakdown as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->trx_count) }} trx</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                            <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->revenue, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-6 py-20 text-center text-slate-400 italic text-sm">Tidak ada data untuk periode ini.</td></tr>
                                @endforelse
                            @else
                                @forelse($dailyBreakdown as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->trx_count) }} trx</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                            <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->revenue, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-6 py-20 text-center text-slate-400 italic text-sm">Tidak ada data untuk periode ini.</td></tr>
                                @endforelse
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function onSalesDateChange(inputEl) {
    if (!inputEl || !inputEl.value) return;

    const baseUrl = inputEl.dataset.baseUrl;
    const param = inputEl.dataset.param;

    if (!baseUrl || !param) return;

    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set(param, inputEl.value);
    window.location.href = url.toString();
}

(function() {
    const PER_PAGE = 20;
    const body = document.getElementById('table-body');
    if (!body) return;

    const allRows = Array.from(body.querySelectorAll('tr'));
    if (allRows.length === 0) return;

    let currentPage = 1;
    const totalPages = Math.ceil(allRows.length / PER_PAGE);

    function render(page) {
        currentPage = page;
        const start = (page - 1) * PER_PAGE;
        const end = start + PER_PAGE;

        allRows.forEach((row, i) => {
            row.style.display = i >= start && i < end ? '' : 'none';
        });

        const info = document.getElementById('pagination-info');
        if (info) info.textContent = `Menampilkan ${start + 1}-${Math.min(end, allRows.length)} dari ${allRows.length} data`;

        renderControls();
    }

    function btn(label, page, disabled = false, active = false) {
        const el = document.createElement('button');
        el.textContent = label;
        el.disabled = disabled;
        el.className = [
            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200',
            active
                ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                : disabled
                    ? 'text-slate-300 dark:text-slate-700 cursor-not-allowed'
                    : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
        ].join(' ');
        if (!disabled) el.addEventListener('click', () => render(page));
        return el;
    }

    function renderControls() {
        const ctrl = document.getElementById('pagination-controls');
        if (!ctrl) return;
        ctrl.innerHTML = '';

        ctrl.appendChild(btn('<', currentPage - 1, currentPage === 1));

        const range = [];
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1) {
                range.push(p);
            } else if (range[range.length - 1] !== '...') {
                range.push('...');
            }
        }

        range.forEach(p => {
            if (p === '...') {
                const s = document.createElement('span');
                s.className = 'px-1 text-slate-300 dark:text-slate-700 text-xs';
                s.textContent = '...';
                ctrl.appendChild(s);
            } else {
                ctrl.appendChild(btn(p, p, false, p === currentPage));
            }
        });

        ctrl.appendChild(btn('>', currentPage + 1, currentPage === totalPages));
    }

    render(1);
})();
</script>
@endpush

