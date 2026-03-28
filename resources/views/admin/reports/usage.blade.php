@extends('layouts.app')

@section('title', 'Laporan Pemakaian')

@section('content')
@php
    $isOwner = request()->routeIs('owner.*');
    $usageRoute = $isOwner ? 'owner.reports.usage' : 'admin.reports.usage';
    $exportRoute = $isOwner ? 'owner.reports.usage.export' : 'admin.reports.usage.export';
    $stockRoute = $isOwner ? 'owner.stocks.index' : 'admin.stocks.logs';

    $hasActiveFilters = request()->filled('search') || request()->filled('date_from') || request()->filled('date_to');
@endphp

<div class="space-y-8 max-w-full overflow-x-hidden">


    {{-- ════ 1. HEADER ════ --}}
    <div class="mb-8">
        
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <a href="{{ $isOwner ? route('owner.panel') : url('/admin') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Laporan Pemakaian</span>
        </nav>

        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
            Laporan Pemakaian
        </h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
            Pantau total bahan baku yang terpakai selama periode tertentu.<br class="hidden sm:block mt-1">
            Gunakan filter di bawah untuk mencari bahan spesifik atau mengekspor data ke format laporan.
        </p>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route($stockRoute) }}"
               class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-[13px] font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                {{ $isOwner ? 'Monitoring Stok' : 'Riwayat Stok' }}
            </a>
        </div>
    </div>


    {{-- ════ 2. CONTROL BAR (Konsisten dengan Analisis Penjualan) ════ --}}
    <form method="GET" action="{{ route($usageRoute) }}" id="filter-form" class="flex flex-col lg:flex-row gap-3 w-full mb-6 relative z-10">
        <input type="hidden" name="type" id="hidden_type" value="{{ $type }}">
        <input type="hidden" name="date_from" id="hidden_date_from" value="{{ $dateFrom->toDateString() }}">
        <input type="hidden" name="date_to" id="hidden_date_to" value="{{ $dateTo->toDateString() }}">

        {{-- 1. Quick Tabs (Gaya Segmented Control - Ukuran Presisi) --}}
        <div class="w-full lg:w-auto flex bg-slate-100 dark:bg-slate-800/50 p-1.5 rounded-xl border border-slate-200/50 dark:border-slate-700/50 shrink-0 overflow-x-auto no-scrollbar justify-start sm:justify-center">
            <button type="button" onclick="changeType('daily')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'daily' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
            <button type="button" onclick="changeType('monthly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'monthly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
            <button type="button" onclick="changeType('yearly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'yearly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Tahunan</button>
        </div>

        {{-- 2. Controls Group (Flex-1) --}}
        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            
            {{-- Date Selector (Lebih Ramping) --}}
            <div class="flex-1 flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all min-w-0">
                <svg class="w-5 h-5 text-slate-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <input type="{{ $inputType }}" value="{{ $inputValue }}" onchange="updateDateRange(this, '{{ $type }}')" class="w-full text-center bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none dark:[color-scheme:dark]">
            </div>

            {{-- Search Box --}}
            <div class="flex-1 flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all min-w-0">
                <svg class="w-4 h-4 text-slate-400 shrink-0 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" name="search" id="search-input" value="{{ $search }}" placeholder="Cari nama bahan baku..." autocomplete="off"
                       class="w-full bg-transparent border-none text-[13px] font-semibold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 p-0 outline-none">
            </div>

            {{-- Export Button (Ukuran Tinggi Persis Sama) --}}
            <a href="{{ route($exportRoute, request()->query()) }}" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:bg-slate-800 dark:hover:bg-white active:scale-95 transition-all shadow-sm shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Export Data
            </a>
        </div>
    </form>

    {{-- ════ 3. STATS CARDS ════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <div class="relative p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl group hover:border-emerald-500/30 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/5 dark:bg-emerald-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3">Item Terpakai</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white leading-tight tracking-tight">{{ number_format($summary['ingredients_count']) }} <span class="text-sm text-slate-400 font-bold uppercase tracking-widest normal-case ml-1">Bahan</span></p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
            </div>
        </div>

        <div class="relative p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl group hover:border-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/5 dark:bg-blue-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3">Frekuensi Pemakaian</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white leading-tight tracking-tight">{{ number_format($summary['logs_count']) }} <span class="text-sm text-slate-400 font-bold uppercase tracking-widest normal-case ml-1">Log</span></p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
            </div>
        </div>

        <div class="relative p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl group hover:border-violet-500/30 hover:shadow-2xl hover:shadow-violet-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-violet-500/5 dark:bg-violet-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3">Total Volume (Base)</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white leading-tight tracking-tight">{{ number_format($summary['total_base_quantity'], 2) }}</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                </div>
            </div>
        </div>

    </div>

    {{-- ════ 4. MAIN TABLE (Laporan Pemakaian) ════ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm self-start">
        
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 flex items-center gap-2">
                <span class="w-4 h-1 bg-blue-500 rounded-full"></span>
                Rincian Pemakaian Bahan
            </p>
            <p class="text-[10px] font-bold text-slate-400">
                Menampilkan <span class="text-slate-700 dark:text-slate-300">{{ $usageItems->firstItem() ?? 0 }} - {{ $usageItems->lastItem() ?? 0 }}</span> dari <span class="text-slate-700 dark:text-slate-300">{{ $usageItems->total() }}</span> data
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-4">Bahan Baku</th>
                        <th class="px-6 py-4 text-center">Total Pemakaian</th>
                        <th class="px-6 py-4 text-center">Frekuensi</th>
                        <th class="px-6 py-4 text-right">Terakhir Dipakai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($usageItems as $item)
                        @php
                            $baseUnit = strtolower(trim((string) ($item->base_unit ?? '')));
                            $displayUnit = $baseUnit;
                            $total = (float) $item->total_quantity;

                            if (in_array($baseUnit, ['g', 'gr', 'gram'], true)) {
                                if ($total >= 1000) {
                                    $total = $total / 1000;
                                    $displayUnit = 'kg';
                                } else {
                                    $displayUnit = 'g';
                                }
                            } elseif (in_array($baseUnit, ['ml', 'milliliter'], true)) {
                                if ($total >= 1000) {
                                    $total = $total / 1000;
                                    $displayUnit = 'L';
                                } else {
                                    $displayUnit = 'ml';
                                }
                            }

                            $formatted = number_format($total, 2, '.', '');
                            $formatted = rtrim(rtrim($formatted, '0'), '.');
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-800 dark:text-white">
                                {{ $item->ingredient_name }}
                            </td>
                            
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1.5 bg-red-50 dark:bg-red-900/30 border border-red-100 dark:border-red-800/50 rounded-lg font-black text-red-600 dark:text-red-400 text-[13px] tracking-tight">
                                    {{ $formatted }} <span class="text-[10px] font-bold uppercase tracking-widest ml-0.5">{{ $displayUnit }}</span>
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-md font-bold text-slate-600 dark:text-slate-300 text-xs">
                                    {{ number_format($item->usage_count) }}x
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 tabular-nums">
                                {{ \Carbon\Carbon::parse($item->last_used_at)->translatedFormat('d M Y, H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    </div>
                                    <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Tidak ada pemakaian bahan dalam rentang tanggal ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Custom Pagination Styling --}}
        @if($usageItems->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800 flex justify-between items-center">
                <div class="flex gap-2 w-full justify-end">
                    @if($usageItems->onFirstPage())
                        <span class="px-4 py-2 text-[11px] font-bold uppercase tracking-widest rounded-lg bg-slate-100 dark:bg-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed">Previous</span>
                    @else
                        <a href="{{ $usageItems->previousPageUrl() }}" class="px-4 py-2 text-[11px] font-bold uppercase tracking-widest rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors">Previous</a>
                    @endif

                    @if($usageItems->hasMorePages())
                        <a href="{{ $usageItems->nextPageUrl() }}" class="px-4 py-2 text-[11px] font-bold uppercase tracking-widest rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors shadow-sm">Next</a>
                    @else
                        <span class="px-4 py-2 text-[11px] font-bold uppercase tracking-widest rounded-lg bg-slate-100 dark:bg-slate-800/50 text-slate-400 dark:text-slate-600 cursor-not-allowed">Next</span>
                    @endif
                </div>
            </div>
        @endif
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
        timeout = setTimeout(() => {
            document.getElementById('filter-form').submit();
        }, 500);
    });
}
</script>
@endpush