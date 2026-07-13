@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Laporan Stok Harian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Laporan Stok Harian" 
        subtitle="Ringkasan akumulasi bahan baku yang dibawa, sisa di akhir sesi, total yang terpakai, serta estimasi nilai pemakaian per sesi kasir." 
        breadcrumb-parent="Pelaporan" 
        breadcrumb-child="Laporan Stok Harian">
    </x-page-header>

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
            <input type="{{ $inputType }}" value="{{ $inputValue }}" onchange="updateDateRange(this, '{{ $type }}')"
                   max="{{ $inputType === 'month' ? now()->format('Y-m') : now()->toDateString() }}"
                   class="h-full w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
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
    @php
        $unitTones = ['tone-emerald', 'tone-amber', 'tone-violet', 'tone-cyan', 'tone-rose'];
    @endphp
    <div class="daily-stock-summary-grid">
        {{-- Jumlah Sesi --}}
        <div class="daily-stock-card tone-slate">
            <div class="daily-stock-card-head">
                <div>
                    <p class="daily-stock-card-label">Jumlah Sesi</p>
                    <p class="daily-stock-card-value">{{ number_format($summary['sessions_count'], 0, ',', '.') }}</p>
                </div>
                <span class="daily-stock-card-icon">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <div class="daily-stock-card-foot">
                <span>Sesi Kasir</span>
                <span>Periode Aktif</span>
            </div>
        </div>

        {{-- Total Item --}}
        <div class="daily-stock-card tone-blue">
            <div class="daily-stock-card-head">
                <div>
                    <p class="daily-stock-card-label">Total Item</p>
                    <p class="daily-stock-card-value">{{ number_format($summary['items_count'], 0, ',', '.') }}</p>
                </div>
                <span class="daily-stock-card-icon">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </span>
            </div>
            <div class="daily-stock-card-foot">
                <span>Bahan Baku</span>
                <span>Tercatat</span>
            </div>
        </div>

        {{-- Per-Unit Cards (Dinamis) --}}
        @foreach($summary['by_unit'] as $idx => $unitData)
            @php $tone = $unitTones[$idx % count($unitTones)]; @endphp
            <div class="daily-stock-card daily-stock-unit-card {{ $tone }}">
                <div class="daily-stock-card-head">
                    <div>
                        <p class="daily-stock-card-label">Stok {{ $unitData['unit'] }}</p>
                        <p class="daily-stock-card-value daily-stock-card-value-small">
                            {{ rtrim(rtrim(number_format($unitData['used'], 2, '.', ''), '0'), '.') }}
                        </p>
                    </div>
                    <span class="daily-stock-card-icon">
                        @php
                            $unitLower = strtolower($unitData['unit']);
                        @endphp
                        @if($unitLower === 'g' || $unitLower === 'gram' || $unitLower === 'kg')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                        @elseif($unitLower === 'ml' || $unitLower === 'liter' || $unitLower === 'l')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        @elseif($unitLower === 'pcs' || $unitLower === 'pcs.')
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        @else
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        @endif
                    </span>
                </div>

                <div class="daily-stock-card-foot">
                    <span>Bawa: <strong class="text-slate-700 dark:text-slate-200 font-bold ml-1">{{ rtrim(rtrim(number_format($unitData['opening'], 2, '.', ''), '0'), '.') }}</strong></span>
                    <span>Sisa: <strong class="text-slate-700 dark:text-slate-200 font-bold ml-1">{{ rtrim(rtrim(number_format($unitData['remaining'], 2, '.', ''), '0'), '.') }}</strong></span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Baris 2: Card Finansial --}}
    <div class="daily-stock-finance-grid">
        <div class="daily-stock-finance-card finance-cost">
            <span class="daily-stock-finance-icon">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="daily-stock-finance-label">Estimasi Nilai Modal</p>
                <p class="daily-stock-finance-caption">Biaya bahan baku terpakai</p>
                <p class="daily-stock-finance-value">
                    <span>Rp</span>{{ number_format($summary['total_value'], 0, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="daily-stock-finance-card finance-revenue">
            <span class="daily-stock-finance-icon">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="daily-stock-finance-label">Estimasi Nilai Terjual</p>
                <p class="daily-stock-finance-caption">Potensi revenue bahan terpakai</p>
                <p class="daily-stock-finance-value">
                    <span>Rp</span>{{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}
                </p>
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
                    <tr class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                        <th class="px-6 py-4 whitespace-nowrap">Sesi & Kasir</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Status</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Item</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Bawa</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Sisa</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Terpakai</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Est. Modal</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Est. Terjual</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                    @forelse($sessions as $session)
                        
                        {{-- ================= ROW DESKTOP ================= --}}
                        <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-sm shrink-0">
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
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle text-[13px] font-bold text-slate-700 dark:text-slate-200 tabular-nums">
                                {{ rtrim(rtrim(number_format((float) ($session->total_used ?? 0), 2, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <span class="inline-flex items-center justify-end font-bold text-slate-700 dark:text-slate-200 tabular-nums text-[14px]">
                                    <span class="text-[10px] mr-1 text-slate-400 dark:text-slate-500">Rp</span>
                                    {{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                <span class="inline-flex items-center justify-end font-bold text-slate-700 dark:text-slate-200 tabular-nums text-[14px]">
                                    <span class="text-[10px] mr-1 text-slate-400 dark:text-slate-500">Rp</span>
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
                                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-1">Pakai</p>
                                                <p class="font-bold text-slate-700 dark:text-slate-200 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) ($session->total_used ?? 0), 2, ',', '.'), '0'), ',') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Baris 3: Nilai Estimasi --}}
                                    <div class="flex items-center justify-between pt-1.5 px-1">
                                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Est. Modal</p>
                                        <p class="font-black text-slate-700 dark:text-slate-200 text-[14px] tabular-nums"><span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mr-0.5">Rp</span>{{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}</p>
                                    </div>
                                    <div class="flex items-center justify-between pt-1 px-1">
                                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Est. Terjual</p>
                                        <p class="font-black text-slate-700 dark:text-slate-200 text-[14px] tabular-nums"><span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mr-0.5">Rp</span>{{ number_format((float) ($session->total_revenue ?? 0), 0, ',', '.') }}</p>
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

    </div>

    @include('partials.pagination_simple', [
        'paginator' => $sessions,
        'label' => 'data',
    ])

</div>

<style>
/* CSS Helper untuk menyembunyikan scrollbar di menu navigasi */
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.daily-stock-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
    align-items: start;
}

.daily-stock-card {
    --tone-rgb: 71 85 105;
    position: relative;
    overflow: hidden;
    border: 1px solid rgb(226 232 240);
    border-radius: 16px;
    background: rgb(255 255 255);
    padding: 16px 20px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
}

.daily-stock-card:hover {
    border-color: rgb(203 213 225);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    transform: translateY(-1px);
}

.dark .daily-stock-card {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
    box-shadow: none;
}

.dark .daily-stock-card:hover {
    border-color: rgb(51 65 85);
    box-shadow: none;
}

.daily-stock-card-head {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
}

.daily-stock-card-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: rgb(100 116 139);
}

.dark .daily-stock-card-label {
    color: rgb(148 163 184);
}

.daily-stock-card-value {
    margin-top: 8px;
    font-size: 28px;
    line-height: 1;
    font-weight: 800;
    color: rgb(15 23 42);
    font-variant-numeric: tabular-nums;
}

.dark .daily-stock-card-value {
    color: rgb(248 250 252);
}

.daily-stock-card-value-small {
    font-size: 24px;
}

.daily-stock-card-icon {
    position: relative;
    z-index: 1;
    display: inline-flex;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: rgb(248 250 252);
    color: rgb(var(--tone-rgb));
    box-shadow: inset 0 0 0 1px rgb(226 232 240);
}

.dark .daily-stock-card-icon {
    background: rgb(30 41 59);
    box-shadow: inset 0 0 0 1px rgb(51 65 85);
}

.daily-stock-card-foot {
    position: relative;
    z-index: 1;
    margin-top: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-top: 1px dashed rgb(226 232 240);
    padding-top: 14px;
    font-size: 11px;
    font-weight: 600;
    color: rgb(100 116 139);
}

.dark .daily-stock-card-foot {
    border-color: rgb(30 41 59);
    color: rgb(148 163 184);
}



.tone-slate { --tone-rgb: 71 85 105; }
.tone-blue { --tone-rgb: 59 130 246; }
.tone-emerald { --tone-rgb: 16 185 129; }
.tone-amber { --tone-rgb: 245 158 11; }
.tone-violet { --tone-rgb: 139 92 246; }
.tone-cyan { --tone-rgb: 6 182 212; }
.tone-rose { --tone-rgb: 244 63 94; }

.daily-stock-finance-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1rem;
}

.daily-stock-finance-card {
    --finance-rgb: 71 85 105;
    display: flex;
    align-items: center;
    gap: 16px;
    overflow: hidden;
    border: 1px solid rgb(226 232 240);
    border-radius: 16px;
    background: rgb(255 255 255);
    padding: 16px 20px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.dark .daily-stock-finance-card {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
    box-shadow: none;
}

.finance-cost { --finance-rgb: 249 115 22; }
.finance-revenue { --finance-rgb: 225 29 72; }

.daily-stock-finance-icon {
    display: inline-flex;
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgb(var(--finance-rgb) / .10);
    color: rgb(var(--finance-rgb));
}

.daily-stock-finance-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: rgb(100 116 139);
}

.dark .daily-stock-finance-label {
    color: rgb(148 163 184);
}

.daily-stock-finance-caption {
    margin-top: 2px;
    font-size: 11px;
    font-weight: 500;
    color: rgb(148 163 184);
}

.daily-stock-finance-value {
    margin-top: 8px;
    font-size: 26px;
    line-height: 1;
    font-weight: 800;
    color: rgb(15 23 42);
    font-variant-numeric: tabular-nums;
}

.dark .daily-stock-finance-value {
    color: rgb(248 250 252);
}

.daily-stock-finance-value span {
    margin-right: 4px;
    font-size: 14px;
    font-weight: 700;
    color: rgb(148 163 184);
}

@media (min-width: 768px) {
    .daily-stock-finance-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (min-width: 1280px) {
    .daily-stock-summary-grid {
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    }
}

@media (max-width: 640px) {
    .daily-stock-card {
        min-height: 132px;
        padding: 16px;
    }

    .daily-stock-card-value {
        font-size: 27px;
    }
}
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
