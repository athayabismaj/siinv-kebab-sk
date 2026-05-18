@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Laporan Stok Harian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex-1 w-full overflow-hidden">
            
            {{-- BREADCRUMB (Anti Pecah di Mobile) --}}
            <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto hide-scrollbar pb-1">
                <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Beranda
                </a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                    Pelaporan
                </span>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                    Laporan Pemakaian
                </span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Laporan Stok Harian
            </h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Ringkasan akumulasi bahan baku yang dibawa, sisa di akhir sesi, total yang terpakai, serta estimasi nilai pemakaian per sesi kasir.
            </p>
        </div>
    </div>

    {{-- ALERTS --}}
    @if(!empty($runtimeError))
        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300 shadow-sm mb-6">
            <svg class="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>{{ $runtimeError }}</div>
        </div>
    @endif

    {{-- ================= FILTER & DATE NAVIGATOR ================= --}}
    <form id="filter-form" method="GET" action="{{ route('admin.reports.daily-stock') }}" class="flex flex-col lg:flex-row gap-3 w-full items-center justify-between py-2 relative z-10">
        
        <input type="hidden" id="hidden_type" name="type" value="{{ $type }}">
        <input type="hidden" id="hidden_date_from" name="date_from" value="{{ $dateFrom->toDateString() }}">
        <input type="hidden" id="hidden_date_to" name="date_to" value="{{ $dateTo->toDateString() }}">

        <div class="flex w-full lg:w-auto rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 shrink-0 overflow-x-auto hide-scrollbar">
            <button type="button" onclick="changeType('daily')" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-bold transition-all {{ $type === 'daily' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
            <button type="button" onclick="changeType('weekly')" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-bold transition-all {{ $type === 'weekly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</button>
            <button type="button" onclick="changeType('monthly')" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-bold transition-all {{ $type === 'monthly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
        </div>

        <div class="flex-1 flex items-center px-1 w-full h-10 rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
            <a href="{{ route('admin.reports.daily-stock', array_merge(request()->except(['page','date_from','date_to']), ['type' => $type, 'date_from' => $prevFrom, 'date_to' => $prevTo])) }}" class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <input type="{{ $inputType }}" value="{{ $inputValue }}" onchange="updateDateRange(this, '{{ $type }}')" class="h-full w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
            @if($isFuture)
                <span class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                </span>
            @else
                <a href="{{ route('admin.reports.daily-stock', array_merge(request()->except(['page','date_from','date_to']), ['type' => $type, 'date_from' => $nextFrom, 'date_to' => $nextTo])) }}" class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                </a>
            @endif
        </div>

        <div class="flex items-center w-full lg:w-auto shrink-0 justify-end" x-data="{ exportOpen: false }">
            <div class="relative w-full lg:w-auto">
                <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false" class="w-full lg:w-auto inline-flex items-center justify-center gap-2 px-5 h-10 bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
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
                    
                    <a href="{{ route('admin.reports.daily-stock.export', array_merge(request()->query(), ['format' => 'html'])) }}" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-blue-400 font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        Format HTML
                    </a>
                    
                    <a href="{{ route('admin.reports.daily-stock.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-rose-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-rose-400 font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        Format PDF
                    </a>
                    
                    <a href="{{ route('admin.reports.daily-stock.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-emerald-400 font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Format Excel
                    </a>
                </div>
            </div>
        </div>
    </form>

    {{-- ================= SUMMARY CARDS ================= --}}
    {{-- Tailwind safelist: md:grid-cols-3 md:grid-cols-4 md:grid-cols-5 md:grid-cols-6 --}}
    @php
        $unitColors = ['emerald', 'amber', 'violet', 'cyan', 'rose'];
    @endphp
    {{-- Baris 1: Sesi + Item + Per-Unit Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-{{ min(count($summary['by_unit']) + 2, 5) }} gap-4">
        {{-- Jumlah Sesi --}}
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Jumlah Sesi</p>
                <div class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 group-hover:bg-slate-200 dark:group-hover:bg-slate-700 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <span class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($summary['sessions_count'], 0, ',', '.') }}</span>
            <p class="text-[10px] text-slate-400 mt-1">sesi kasir</p>
            <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-200 dark:bg-slate-700"></div>
        </div>

        {{-- Total Item --}}
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Total Item</p>
                <div class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-500/20 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
            <span class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($summary['items_count'], 0, ',', '.') }}</span>
            <p class="text-[10px] text-slate-400 mt-1">bahan baku</p>
            <div class="absolute bottom-0 left-0 h-0.5 w-full bg-blue-500/30"></div>
        </div>

        {{-- Per-Unit Cards (Dinamis) --}}
        @foreach($summary['by_unit'] as $idx => $unitData)
            @php $color = $unitColors[$idx % count($unitColors)]; @endphp
            <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
                <div class="flex items-start justify-between mb-2">
                    <p class="text-[10px] font-bold text-{{ $color }}-500 uppercase tracking-widest">
                        Stok {{ $unitData['unit'] }}
                    </p>
                    <div class="p-1.5 rounded-lg bg-{{ $color }}-50 dark:bg-{{ $color }}-500/10 text-{{ $color }}-400 group-hover:bg-{{ $color }}-100 dark:group-hover:bg-{{ $color }}-500/20 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                </div>

                {{-- Mini metric rows --}}
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Dibawa</span>
                        <span class="text-[13px] font-extrabold text-slate-800 dark:text-white tabular-nums">
                            {{ rtrim(rtrim(number_format($unitData['opening'], 2, '.', ''), '0'), '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Sisa</span>
                        <span class="text-[13px] font-extrabold text-amber-600 dark:text-amber-400 tabular-nums">
                            {{ rtrim(rtrim(number_format($unitData['remaining'], 2, '.', ''), '0'), '.') }}
                        </span>
                    </div>
                    <div class="h-px bg-slate-100 dark:bg-slate-800"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-bold text-orange-500 uppercase tracking-wider">Terpakai</span>
                        <span class="text-[14px] font-black text-orange-600 dark:text-orange-400 tabular-nums">
                            {{ rtrim(rtrim(number_format($unitData['used'], 2, '.', ''), '0'), '.') }}
                        </span>
                    </div>
                </div>

                <div class="absolute bottom-0 left-0 h-0.5 w-full bg-{{ $color }}-500/30"></div>
            </div>
        @endforeach
    </div>

    {{-- Baris 2: Card Finansial --}}
    <div class="relative overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
        <div class="absolute inset-0 bg-gradient-to-r from-orange-500/5 via-transparent to-rose-500/5 pointer-events-none"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100 dark:divide-slate-800">
            {{-- Est. Nilai Modal --}}
            <div class="p-5 sm:p-6 flex items-center gap-5">
                <div class="shrink-0 w-12 h-12 rounded-xl bg-orange-100 dark:bg-orange-500/15 flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-0.5">Estimasi Nilai Modal</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mb-2">Biaya bahan baku terpakai (cost price)</p>
                    <p class="text-2xl font-black text-orange-600 dark:text-orange-400 tabular-nums leading-none">
                        <span class="text-sm font-bold mr-1">Rp</span>{{ number_format($summary['total_value'], 0, ',', '.') }}
                    </p>
                </div>
            </div>
            {{-- Est. Nilai Terjual --}}
            <div class="p-5 sm:p-6 flex items-center gap-5">
                <div class="shrink-0 w-12 h-12 rounded-xl bg-rose-100 dark:bg-rose-500/15 flex items-center justify-center">
                    <svg class="w-6 h-6 text-rose-500 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-0.5">Estimasi Nilai Terjual</p>
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mb-2">Potensi revenue dari bahan terpakai (selling price)</p>
                    <p class="text-2xl font-black text-rose-600 dark:text-rose-400 tabular-nums leading-none">
                        <span class="text-sm font-bold mr-1">Rp</span>{{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>


    {{-- ================= DATA TABLE (SaaS Modern Style) ================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex items-center justify-between">
            <h3 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Rincian Data Sesi</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="hidden md:table-header-group">
                    <tr class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                        <th class="px-6 py-4 whitespace-nowrap">Sesi & Kasir</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Status</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Item</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Bawa</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Sisa</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap text-blue-600 dark:text-blue-400">Terpakai</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap text-orange-500">Est. Modal</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap text-rose-500">Est. Terjual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($sessions as $session)
                        
                        {{-- ================= ROW DESKTOP ================= --}}
                        <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm shrink-0">
                                        {{ strtoupper(substr($session->cashier->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-[14px]">{{ $session->cashier->name ?? 'User Tidak Diketahui' }}</p>
                                        <p class="text-[11px] font-medium text-slate-400 mt-0.5">{{ $session->session_date->translatedFormat('d M Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap align-middle">
                                @if($session->status === 'closed')
                                    <span class="inline-flex items-center px-2.5 py-1 text-[10px] font-bold rounded-md bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 uppercase tracking-widest">
                                        Selesai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 uppercase tracking-widest">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap align-middle">
                                <span class="text-[13px] font-semibold text-slate-600 dark:text-slate-300 tabular-nums bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 px-2 py-0.5 rounded">
                                    {{ number_format((int) ($session->items_count ?? 0), 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle text-[13px] font-medium text-slate-500 dark:text-slate-400 tabular-nums">
                                {{ rtrim(rtrim(number_format((float) ($session->total_opening ?? 0), 2, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle text-[13px] font-medium text-slate-500 dark:text-slate-400 tabular-nums">
                                {{ rtrim(rtrim(number_format((float) ($session->total_remaining ?? 0), 2, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle text-[13px] font-bold text-blue-600 dark:text-blue-400 tabular-nums">
                                {{ rtrim(rtrim(number_format((float) ($session->total_used ?? 0), 2, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <span class="inline-flex items-center justify-end font-bold text-orange-600 dark:text-orange-400 tabular-nums text-[14px]">
                                    <span class="text-[10px] mr-1 text-orange-400 dark:text-orange-500">Rp</span>
                                    {{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <span class="inline-flex items-center justify-end font-bold text-rose-600 dark:text-rose-400 tabular-nums text-[14px]">
                                    <span class="text-[10px] mr-1 text-rose-400 dark:text-rose-500">Rp</span>
                                    {{ number_format((float) ($session->total_revenue ?? 0), 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>

                        {{-- ================= CARD MOBILE ================= --}}
                        <tr class="md:hidden border-b border-slate-100 dark:border-slate-800/50 last:border-0">
                            <td class="p-0" colspan="7">
                                <div class="p-4 sm:p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                    
                                    {{-- Baris 1: Avatar, Kasir & Status --}}
                                    <div class="flex justify-between items-start gap-3 mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm shrink-0">
                                                {{ strtoupper(substr($session->cashier->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white text-[14px] leading-tight">{{ $session->cashier->name ?? 'User Tidak Diketahui' }}</p>
                                                <p class="text-[11px] font-medium text-slate-400 mt-0.5">{{ $session->session_date->translatedFormat('d M Y') }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            @if($session->status === 'closed')
                                                <span class="inline-flex items-center px-2 py-1 text-[9px] font-bold rounded bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 uppercase tracking-widest">
                                                    Selesai
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2 py-1 text-[9px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 uppercase tracking-widest">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Aktif
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Baris 2: Grid Data (Divider X Style) --}}
                                    <div class="bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-slate-700/50 py-2.5 px-1 mb-3">
                                        <div class="grid grid-cols-4 gap-0 text-center divide-x divide-slate-200 dark:divide-slate-700/60">
                                            <div class="px-2">
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Item</p>
                                                <p class="font-semibold text-slate-700 dark:text-slate-300 text-xs tabular-nums">{{ number_format((int) ($session->items_count ?? 0), 0, ',', '.') }}</p>
                                            </div>
                                            <div class="px-2">
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Bawa</p>
                                                <p class="font-medium text-slate-700 dark:text-slate-400 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) ($session->total_opening ?? 0), 2, ',', '.'), '0'), ',') }}</p>
                                            </div>
                                            <div class="px-2">
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Sisa</p>
                                                <p class="font-medium text-slate-700 dark:text-slate-400 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) ($session->total_remaining ?? 0), 2, ',', '.'), '0'), ',') }}</p>
                                            </div>
                                            <div class="px-2">
                                                <p class="text-[9px] font-bold text-blue-500 uppercase tracking-widest mb-1">Pakai</p>
                                                <p class="font-bold text-blue-600 dark:text-blue-400 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) ($session->total_used ?? 0), 2, ',', '.'), '0'), ',') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Baris 3: Nilai Estimasi --}}
                                    <div class="flex items-center justify-between pt-1.5 px-1">
                                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Est. Modal</p>
                                        <p class="font-black text-orange-600 dark:text-orange-400 text-[14px] tabular-nums"><span class="text-[10px] font-bold text-orange-400 mr-0.5">Rp</span>{{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}</p>
                                    </div>
                                    <div class="flex items-center justify-between pt-1 px-1">
                                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Est. Terjual</p>
                                        <p class="font-black text-rose-600 dark:text-rose-400 text-[14px] tabular-nums"><span class="text-[10px] font-bold text-rose-400 mr-0.5">Rp</span>{{ number_format((float) ($session->total_revenue ?? 0), 0, ',', '.') }}</p>
                                    </div>

                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Belum ada data sesi stok harian pada periode ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ================= PAGINATION ================= --}}
        @if($sessions->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-[13px] text-slate-500 dark:text-slate-400 text-center sm:text-left font-medium">
                    Halaman <span class="font-bold text-slate-700 dark:text-slate-300">{{ $sessions->currentPage() }}</span> 
                    dari <span class="font-bold text-slate-700 dark:text-slate-300">{{ $sessions->lastPage() }}</span>
                </div>
                
                <div class="flex items-center gap-6 text-[13px] font-semibold">
                    @if ($sessions->onFirstPage())
                        <span class="text-slate-400 cursor-not-allowed dark:text-slate-600">&lt; Prev</span>
                    @else
                        <a href="{{ $sessions->previousPageUrl() }}" class="text-blue-600 hover:text-blue-700 transition dark:text-blue-400 dark:hover:text-blue-300">&lt; Prev</a>
                    @endif

                    @if ($sessions->hasMorePages())
                        <a href="{{ $sessions->nextPageUrl() }}" class="text-blue-600 hover:text-blue-700 transition dark:text-blue-400 dark:hover:text-blue-300">Next &gt;</a>
                    @else
                        <span class="text-slate-400 cursor-not-allowed dark:text-slate-600">Next &gt;</span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

</div>

<style>
/* CSS Helper untuk menyembunyikan scrollbar di menu navigasi */
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection

@push('scripts')
<script>
    (function () {
        function formatDate(dateObj) {
            const year = dateObj.getFullYear();
            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
            const day = String(dateObj.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function resolveWeekRange(dateObj) {
            const day = dateObj.getDay();
            const diff = day === 0 ? -6 : 1 - day;

            const start = new Date(dateObj);
            start.setDate(dateObj.getDate() + diff);

            const end = new Date(start);
            end.setDate(start.getDate() + 6);

            return { from: formatDate(start), to: formatDate(end) };
        }

        window.changeType = function changeType(newType) {
            const typeInput = document.getElementById('hidden_type');
            const fromInput = document.getElementById('hidden_date_from');
            const toInput = document.getElementById('hidden_date_to');
            const form = document.getElementById('filter-form');

            if (!typeInput || !fromInput || !toInput || !form) return;

            typeInput.value = newType;

            const now = new Date();
            let from = '';
            let to = '';

            if (newType === 'daily') {
                from = formatDate(now);
                to = from;
            } else if (newType === 'weekly') {
                const range = resolveWeekRange(now);
                from = range.from;
                to = range.to;
            } else {
                const start = new Date(now.getFullYear(), now.getMonth(), 1);
                const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                from = formatDate(start);
                to = formatDate(end);
            }

            fromInput.value = from;
            toInput.value = to;
            form.submit();
        };

        window.updateDateRange = function updateDateRange(input, type) {
            if (!input || !input.value) return;

            const fromInput = document.getElementById('hidden_date_from');
            const toInput = document.getElementById('hidden_date_to');
            const form = document.getElementById('filter-form');

            if (!fromInput || !toInput || !form) return;

            let from = '';
            let to = '';

            if (type === 'daily') {
                from = input.value;
                to = input.value;
            } else if (type === 'weekly') {
                const range = resolveWeekRange(new Date(input.value));
                from = range.from;
                to = range.to;
            } else {
                const parts = input.value.split('-');
                const year = Number(parts[0]);
                const month = Number(parts[1]) - 1;
                const start = new Date(year, month, 1);
                const end = new Date(year, month + 1, 0);
                from = formatDate(start);
                to = formatDate(end);
            }

            fromInput.value = from;
            toInput.value = to;
            form.submit();
        };
    })();
</script>
@endpush