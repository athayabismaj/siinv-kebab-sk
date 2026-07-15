@extends('layouts.app')

@section('content')
@php
    $type = $type ?? 'daily';
    $today = now()->startOfDay();

    if ($type === 'weekly') {
        $currentStart = $selectedWeekStart->copy();
        $currentEnd = $selectedWeekEnd->copy();
        $inputType = 'date';
        $inputValue = $currentStart->toDateString();
        $prevParams = ['type' => 'weekly', 'week_date' => $currentStart->copy()->subWeek()->toDateString()];
        $nextParams = ['type' => 'weekly', 'week_date' => $currentStart->copy()->addWeek()->toDateString()];
        $isFuture = $currentStart->copy()->addWeek()->startOfWeek(\Carbon\Carbon::MONDAY)->isAfter($today);
        $periodLabel = $currentStart->translatedFormat('d M Y') . ' - ' . $currentEnd->translatedFormat('d M Y');
    } elseif ($type === 'monthly') {
        $currentMonth = $selectedMonth->copy();
        $inputType = 'month';
        $inputValue = $currentMonth->format('Y-m');
        $prevParams = ['type' => 'monthly', 'month' => $currentMonth->copy()->subMonth()->format('Y-m')];
        $nextParams = ['type' => 'monthly', 'month' => $currentMonth->copy()->addMonth()->format('Y-m')];
        $isFuture = $currentMonth->copy()->addMonth()->startOfMonth()->isAfter($today);
        $periodLabel = $currentMonth->translatedFormat('F Y');
    } else {
        $currentDate = $selectedDate->copy();
        $inputType = 'date';
        $inputValue = $currentDate->toDateString();
        $prevParams = ['type' => 'daily', 'date' => $currentDate->copy()->subDay()->toDateString()];
        $nextParams = ['type' => 'daily', 'date' => $currentDate->copy()->addDay()->toDateString()];
        $isFuture = $currentDate->copy()->addDay()->isAfter($today);
        $periodLabel = $currentDate->translatedFormat('d F Y');
    }


@endphp

<div class="space-y-8 max-w-full overflow-x-hidden">
    <x-page-header 
        title="Analisis Menu" 
        subtitle="Pantau performa penjualan menu berdasarkan periode harian, mingguan, atau bulanan." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Analisis Menu">
        
        {{-- PERIODE BADGE (kanan atas) --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 shrink-0 mt-2 lg:mt-0">
            <div class="inline-flex w-full sm:w-auto items-center justify-center sm:justify-start gap-2 rounded-full bg-blue-50 border border-blue-100/50 px-3 py-1.5 dark:bg-blue-500/10 dark:border-blue-800/30 shadow-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                </span>
                <span class="text-[11px] font-bold tracking-wide text-blue-700 dark:text-blue-400 uppercase">
                    Periode Data:
                    <span class="font-medium text-slate-700 dark:text-slate-300 ml-1 normal-case">{{ $periodLabel }}</span>
                </span>
            </div>
        </div>
    </x-page-header>

    {{-- FILTER SECTION --}}
    <div class="relative z-10 py-2 mb-6">
        <div class="flex flex-col lg:flex-row gap-3 w-full items-stretch lg:items-center">
            
            {{-- 1. TAB TYPE (KIRI) --}}
            <div class="flex rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 shrink-0">
                <a href="{{ route('owner.analytics.menu', array_filter(['type' => 'daily'])) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'daily' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</a>
                <a href="{{ route('owner.analytics.menu', array_filter(['type' => 'weekly'])) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'weekly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</a>
                <a href="{{ route('owner.analytics.menu', array_filter(['type' => 'monthly'])) }}" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center {{ $type === 'monthly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</a>
            </div>

            {{-- 2. DATE NAVIGATOR (TENGAH) --}}
            <div class="min-w-0 flex items-center px-1 w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 flex-1">
                {{-- Prev --}}
                <a href="{{ route('owner.analytics.menu', $prevParams) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                </a>

                {{-- Date Input --}}
                @if($type === 'daily')
                    <input type="date" value="{{ $inputValue }}" max="{{ $today->toDateString() }}" data-date-navigation data-base-url="{{ route('owner.analytics.menu', array_filter(['type' => 'daily'])) }}" data-param="date"
                           class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
                @elseif($type === 'weekly')
                    <input type="date" value="{{ $inputValue }}" max="{{ $today->toDateString() }}" data-date-navigation data-base-url="{{ route('owner.analytics.menu', array_filter(['type' => 'weekly'])) }}" data-param="week_date"
                           class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
                @else
                    <input type="month" value="{{ $inputValue }}" max="{{ $today->format('Y-m') }}" data-date-navigation data-base-url="{{ route('owner.analytics.menu', array_filter(['type' => 'monthly'])) }}" data-param="month"
                           class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
                @endif

                {{-- Next --}}
                @if($isFuture)
                    <span class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </span>
                @else
                    <a href="{{ route('owner.analytics.menu', $nextParams) }}" class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endif
            </div>

            {{-- 3. ACTIONS (KANAN) --}}
            <div class="flex flex-row gap-3 shrink-0">
                {{-- Tombol Atur Ulang --}}
                @php
                    $hasActiveFilters = request()->filled('date') || request()->filled('week_date') || request()->filled('month') || (request()->filled('type') && request('type') !== 'daily');
                @endphp
                @if($hasActiveFilters)
                    <a href="{{ route('owner.analytics.menu') }}" class="inline-flex h-[38px] w-full sm:w-auto items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[13px] font-semibold text-slate-600 shadow-sm transition-all hover:bg-slate-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-rose-400 shrink-0 whitespace-nowrap">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        <span class="hidden sm:inline">Atur Ulang</span>
                    </a>
                @endif
            </div>

        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        {{-- Menu Terlaris --}}
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Menu Terlaris</p>
                    @if($topMenu)
                        <p class="mt-2 text-[22px] leading-tight font-black text-slate-900 dark:text-white">{{ $topMenu->menu_name }}</p>
                    @else
                        <p class="mt-2 text-[22px] leading-tight font-black text-slate-400 italic">Belum ada data</p>
                    @endif
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-emerald-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 11l7-7 7 7M5 19l7-7 7 7"></path></svg>
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                @if($topMenu)
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ number_format($topMenu->total_qty, 0, ',', '.') }} terjual</span>
                @else
                    <span>-</span>
                @endif
            </div>
        </div>

        {{-- Menu Kurang Laris --}}
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Menu Kurang Laris</p>
                    @if($leastMenu)
                        <p class="mt-2 text-[22px] leading-tight font-black text-slate-900 dark:text-white">{{ $leastMenu->menu_name }}</p>
                    @else
                        <p class="mt-2 text-[22px] leading-tight font-black text-slate-400 italic">Belum ada data</p>
                    @endif
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-amber-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-8l-7 7-7-7"></path></svg>
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                @if($leastMenu)
                    <span class="text-amber-600 dark:text-amber-400 font-bold">{{ number_format($leastMenu->total_qty, 0, ',', '.') }} terjual</span>
                @else
                    <span>-</span>
                @endif
            </div>
        </div>

        {{-- Total Item Terjual --}}
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Total Item Terjual</p>
                    <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">{{ number_format($totalMenuSold, 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-violet-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                <span>seluruh item dari semua kategori</span>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm self-start">
        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 flex items-center gap-2">
                <span class="w-4 h-1 bg-blue-500 rounded-full"></span>
                Rincian Kontribusi Penjualan Menu
            </p>
        </div>

        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($contributions as $item)
                <div class="p-5">
                    <p class="text-sm font-bold text-slate-900 dark:text-white mb-3">{{ $item->menu_name }}</p>
                    <div class="flex items-end justify-between mb-1.5">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($item->total_qty, 0, ',', '.') }} terjual</p>
                        <p class="text-xs font-black text-blue-600 dark:text-blue-400">{{ number_format($item->contribution, 1, ',', '.') }}%</p>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mb-3 border border-slate-200 dark:border-slate-700">
                        <div class="h-full bg-blue-500 rounded-full transition-all duration-500" style="width: {{ $item->contribution }}%"></div>
                    </div>
                    <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50 p-2.5 rounded-lg mt-3">
                        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Total Pendapatan</span>
                        <span class="text-xs font-black text-slate-900 dark:text-white">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Tidak ada penjualan pada periode ini.</p>
                </div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-4">Nama Menu</th>
                        <th class="px-6 py-4 text-center">Jumlah Terjual</th>
                        <th class="px-6 py-4 min-w-[200px]">Porsi Kontribusi</th>
                        <th class="px-6 py-4 text-right">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($contributions as $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors group">
                            <td class="px-6 py-4 font-bold text-slate-800 dark:text-white">{{ $item->menu_name }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-md font-bold text-slate-600 dark:text-slate-300 text-xs">{{ number_format($item->total_qty, 0, ',', '.') }}</span>
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
                                <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Tidak ada penjualan pada periode ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

