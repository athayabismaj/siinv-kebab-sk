@extends('layouts.app')

@section('content')
@php
    $today = now()->toDateString();
    $dateValue = $selectedDate->toDateString();
    $prevDate = $selectedDate->copy()->subDay()->toDateString();
    $nextDate = $selectedDate->copy()->addDay()->toDateString();
    $isToday = $dateValue === $today;
@endphp

<div class="space-y-8 max-w-full overflow-x-hidden">

    {{-- ════ 1. HEADER ════ --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Analisis Menu</span>
        </nav>
        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
            Analisis Menu
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
            Pantau performa penjualan setiap menu secara harian. Temukan menu terlaris dan yang perlu diperhatikan.
        </p>
    </div>

    {{-- ════ 2. CONTROL BAR (Filter Periode Eksklusif) ════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50 dark:bg-slate-800/40 p-2 sm:p-3 rounded-2xl border border-slate-200 dark:border-slate-800">
        
        {{-- Label Konteks --}}
        <div class="hidden sm:flex items-center gap-2.5 px-3">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            <span class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                Periode Aktif: {{ $selectedDate->translatedFormat('d F Y') }}
            </span>
        </div>

        {{-- Date Navigator --}}
        <div class="inline-flex items-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm p-1 shrink-0 w-full sm:w-auto justify-between sm:justify-start">
            
            {{-- Tombol Mundur --}}
            <a href="{{ route('owner.analytics.menu', ['date' => $prevDate]) }}" title="Hari Sebelumnya"
               class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-800 dark:hover:text-blue-400 transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
            </a>

            {{-- Form Tanggal --}}
            <form method="GET" action="{{ route('owner.analytics.menu') }}" class="flex-1 flex min-w-0 px-2 sm:px-4">
                <input type="date" name="date" max="{{ $today }}" value="{{ $dateValue }}" onchange="this.form.submit()"
                       class="w-full min-w-0 text-center bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none dark:[color-scheme:dark]">
            </form>

            {{-- Tombol Maju --}}
            @if($isToday)
                <div class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-300 dark:text-slate-700 cursor-not-allowed shrink-0" title="Tidak ada data masa depan">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </div>
            @else
                <a href="{{ route('owner.analytics.menu', ['date' => $nextDate]) }}" title="Hari Berikutnya"
                   class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-800 dark:hover:text-blue-400 transition-all shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </a>
            @endif

        </div>
    </div>

    {{-- ════ 3. STATS CARDS (Glowing Effect) ════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        {{-- Card 1: Menu Terlaris --}}
        <div class="relative p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl group hover:border-emerald-500/30 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-emerald-500/5 dark:bg-emerald-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3">Menu Terlaris</p>
                    @if($topMenu)
                        <p class="text-xl font-black text-slate-900 dark:text-white leading-tight mb-1">{{ $topMenu->menu_name }}</p>
                        <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($topMenu->total_qty, 0, ',', '.') }} terjual</p>
                    @else
                        <p class="text-sm font-medium text-slate-400 italic">Belum ada data</p>
                    @endif
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l7-7 7 7M5 19l7-7 7 7"></path></svg>
                </div>
            </div>
        </div>

        {{-- Card 2: Menu Paling Sedikit --}}
        <div class="relative p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl group hover:border-amber-500/30 hover:shadow-2xl hover:shadow-amber-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-500/5 dark:bg-amber-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3">Kurang Laris</p>
                    @if($leastMenu)
                        <p class="text-xl font-black text-slate-900 dark:text-white leading-tight mb-1">{{ $leastMenu->menu_name }}</p>
                        <p class="text-sm font-bold text-amber-600 dark:text-amber-400">{{ number_format($leastMenu->total_qty, 0, ',', '.') }} terjual</p>
                    @else
                        <p class="text-sm font-medium text-slate-400 italic">Belum ada data</p>
                    @endif
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        {{-- Card 3: Total Terjual --}}
        <div class="relative p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl group hover:border-violet-500/30 hover:shadow-2xl hover:shadow-violet-500/10 transition-all duration-500 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-violet-500/5 dark:bg-violet-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-3">Total Item Terjual</p>
                    <div class="flex items-baseline gap-1">
                        <p class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ number_format($totalMenuSold, 0, ',', '.') }}</p>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-0.5">Item</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>
        </div>

    </div>

    {{-- ════ 4. MAIN CONTENT (Kontribusi Penjualan) ════ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm self-start">
        
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 flex items-center gap-2">
                <span class="w-4 h-1 bg-blue-500 rounded-full"></span>
                Rincian Kontribusi Penjualan Menu
            </p>
        </div>

        {{-- Mobile View --}}
        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($contributions as $item)
                <div class="p-5">
                    <p class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ $item->menu_name }}</p>
                    
                    <div class="flex items-end justify-between mb-1.5">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($item->total_qty, 0, ',', '.') }} Terjual</p>
                        <p class="text-xs font-black text-blue-600 dark:text-blue-400">{{ number_format($item->contribution, 1, ',', '.') }}%</p>
                    </div>
                    
                    {{-- Progress Bar --}}
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mb-3 border border-slate-200 dark:border-slate-700">
                        <div class="h-full bg-blue-500 rounded-full transition-all duration-500" style="width: {{ $item->contribution }}%"></div>
                    </div>

                    <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 p-2.5 rounded-lg mt-3">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Pendapatan</span>
                        <span class="text-xs font-black text-slate-900 dark:text-white">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Tidak ada penjualan pada tanggal ini.</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop View --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left">
                
                {{-- FIX Kontras Dark Mode Solid --}}
                <thead class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-4">Nama Menu</th>
                        <th class="px-6 py-4 text-center">Qty Terjual</th>
                        <th class="px-6 py-4 min-w-[200px]">Porsi Kontribusi</th>
                        <th class="px-6 py-4 text-right">Total Pendapatan</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($contributions as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors group">
                            
                            <td class="px-6 py-4 font-bold text-slate-800 dark:text-white">
                                {{ $item->menu_name }}
                            </td>
                            
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-md font-bold text-slate-600 dark:text-slate-300 text-xs">
                                    {{ number_format($item->total_qty, 0, ',', '.') }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 group-hover:bg-blue-400 rounded-full transition-all duration-500" style="width: {{ $item->contribution }}%"></div>
                                    </div>
                                    <span class="text-xs font-black text-slate-600 dark:text-slate-300 w-10 text-right">{{ number_format($item->contribution, 1, ',', '.') }}%</span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($item->total_sales, 0, ',', '.') }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    </div>
                                    <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Tidak ada penjualan pada tanggal ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection