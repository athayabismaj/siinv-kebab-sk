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
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Analisis Menu</span>
        </nav>
        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white tracking-tight mb-3">Analisis Menu</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
            Pantau performa penjualan menu berdasarkan periode harian, mingguan, atau bulanan.
        </p>
    </div>

    <form method="GET" action="{{ route('owner.analytics.menu') }}" class="flex flex-col lg:flex-row gap-3 w-full mb-6 relative z-10">
        <div class="w-full lg:w-auto flex bg-slate-100 dark:bg-slate-800/50 p-1.5 rounded-xl border border-slate-200/50 dark:border-slate-700/50 shrink-0 overflow-x-auto no-scrollbar justify-start sm:justify-center">
            @foreach(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'] as $key => $label)
                <a href="{{ route('owner.analytics.menu', ['type' => $key]) }}"
                   class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center
                   {{ $type === $key ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="flex-1 flex items-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm p-1 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all min-w-0">
                <a href="{{ route('owner.analytics.menu', $prevParams) }}" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-700 dark:hover:text-blue-400 transition-all shrink-0" title="Sebelumnya">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </a>

                <div class="flex-1 flex px-3">
                    <input type="hidden" name="type" value="{{ $type }}">
                    @if($type === 'daily')
                        <input type="date" name="date" value="{{ $inputValue }}" max="{{ $today->toDateString() }}" onchange="this.form.submit()" class="w-full text-center bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none dark:[color-scheme:dark]">
                    @elseif($type === 'weekly')
                        <input type="date" name="week_date" value="{{ $inputValue }}" max="{{ $today->toDateString() }}" onchange="this.form.submit()" class="w-full text-center bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none dark:[color-scheme:dark]">
                    @else
                        <input type="month" name="month" value="{{ $inputValue }}" max="{{ $today->format('Y-m') }}" onchange="this.form.submit()" class="w-full text-center bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none dark:[color-scheme:dark]">
                    @endif
                </div>

                @if($isFuture)
                    <div class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-300 dark:text-slate-700 cursor-not-allowed shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                @else
                    <a href="{{ route('owner.analytics.menu', $nextParams) }}" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-700 dark:hover:text-blue-400 transition-all shrink-0" title="Berikutnya">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                @endif
            </div>

            <div class="inline-flex items-center gap-2.5 px-4 py-2 bg-blue-50/50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 rounded-xl shadow-sm text-[12px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide shrink-0">
                <span>Periode</span>
                <span class="text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $periodLabel }}</span>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
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
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ number_format($item->total_qty, 0, ',', '.') }} Terjual</p>
                        <p class="text-xs font-black text-blue-600 dark:text-blue-400">{{ number_format($item->contribution, 1, ',', '.') }}%</p>
                    </div>
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
                    <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Tidak ada penjualan pada periode ini.</p>
                </div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm text-left">
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
