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

        <div class="flex items-center w-full lg:w-auto shrink-0 justify-end">
            <a href="{{ route('admin.reports.daily-stock.export', request()->query()) }}" class="flex-1 lg:flex-none inline-flex items-center justify-center gap-2 px-5 h-10 bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Export CSV
            </a>
        </div>
    </form>

    {{-- ================= SUMMARY CARDS ================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Jumlah Sesi</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($summary['sessions_count'], 0, ',', '.') }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-slate-500/20"></div>
        </div>

        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Item Aktif</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ number_format($summary['items_count'], 0, ',', '.') }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500/20"></div>
        </div>

        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Stok Dibawa</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ rtrim(rtrim(number_format($summary['total_opening'], 2, ',', '.'), '0'), ',') }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-emerald-500/20"></div>
        </div>

        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Sisa Stok</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ rtrim(rtrim(number_format($summary['total_remaining'], 2, ',', '.'), '0'), ',') }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-amber-500/20"></div>
        </div>

        <div class="relative overflow-hidden p-5 bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/50 rounded-2xl shadow-sm hover:shadow-md transition sm:col-span-2 lg:col-span-3 xl:col-span-1">
            <p class="text-[10px] font-bold text-rose-500 dark:text-rose-400 uppercase tracking-widest mb-1.5">Estimasi Nilai Terpakai</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-sm font-bold text-rose-500 dark:text-rose-400">Rp</span>
                <span class="text-2xl font-black text-rose-600 dark:text-rose-400 tabular-nums leading-none">{{ number_format($summary['total_value'], 0, ',', '.') }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-rose-500/50"></div>
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
                        <th class="px-6 py-4 text-right whitespace-nowrap text-rose-500">Estimasi Nilai</th>
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
                                <span class="inline-flex items-center justify-end font-bold text-rose-600 dark:text-rose-400 tabular-nums text-[14px]">
                                    <span class="text-[10px] mr-1 text-rose-400 dark:text-rose-500">Rp</span>
                                    {{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}
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
                                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Est. Nilai Terpakai</p>
                                        <p class="font-black text-rose-600 dark:text-rose-400 text-[15px] tabular-nums"><span class="text-[10px] font-bold text-rose-400 mr-0.5">Rp</span>{{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}</p>
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