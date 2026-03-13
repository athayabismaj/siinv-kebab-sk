@extends('layouts.app')

@section('content')
@php
    $today = now()->toDateString();
    $dateValue = $selectedDate->toDateString();
    $prevDate = $selectedDate->copy()->subDay()->toDateString();
    $nextDate = $selectedDate->copy()->addDay()->toDateString();
    $isToday = $dateValue === $today;
@endphp

<div class="space-y-6 max-w-full overflow-x-hidden">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-slate-800 dark:text-white">Analisis Menu</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Fokus pada performa menu: terlaris, paling sedikit, dan kontribusi.</p>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <a href="{{ route('owner.analytics.menu', ['date' => $prevDate]) }}"
               class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">&lt;</a>

            <form method="GET" action="{{ route('owner.analytics.menu') }}" class="flex-1 md:flex-none">
                <input type="date" name="date" max="{{ $today }}" value="{{ $dateValue }}" onchange="this.form.submit()"
                       class="w-full md:w-auto px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-700 dark:text-slate-200">
            </form>

            @if($isToday)
                <span class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-800 text-sm text-slate-400 dark:text-slate-500 cursor-not-allowed">&gt;</span>
            @else
                <a href="{{ route('owner.analytics.menu', ['date' => $nextDate]) }}"
                   class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">&gt;</a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Menu Terlaris</p>
            @if($topMenu)
                <p class="mt-2 text-lg font-semibold text-slate-800 dark:text-white break-words">{{ $topMenu->menu_name }}</p>
                <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ number_format($topMenu->total_qty, 0, ',', '.') }} terjual</p>
            @else
                <p class="mt-2 text-sm text-slate-500">Belum ada data penjualan.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Menu Paling Sedikit Terjual</p>
            @if($leastMenu)
                <p class="mt-2 text-lg font-semibold text-slate-800 dark:text-white break-words">{{ $leastMenu->menu_name }}</p>
                <p class="text-sm text-amber-600 dark:text-amber-400">{{ number_format($leastMenu->total_qty, 0, ',', '.') }} terjual</p>
            @else
                <p class="mt-2 text-sm text-slate-500">Belum ada data penjualan.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Item Terjual</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">{{ number_format($totalMenuSold, 0, ',', '.') }} item</p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Kontribusi Penjualan Menu</h2>
        </div>

        <div class="md:hidden p-3 space-y-3">
            @forelse($contributions as $item)
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                    <p class="font-semibold text-slate-800 dark:text-white break-words">{{ $item->menu_name }}</p>
                    <p class="flex justify-between text-sm"><span class="text-slate-500">Qty</span><span class="font-medium">{{ number_format($item->total_qty, 0, ',', '.') }}</span></p>
                    <p class="flex justify-between text-sm"><span class="text-slate-500">Kontribusi</span><span class="font-medium">{{ number_format($item->contribution, 1, ',', '.') }}%</span></p>
                    <p class="flex justify-between text-sm"><span class="text-slate-500">Penjualan</span><span class="font-semibold">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</span></p>
                </div>
            @empty
                <div class="px-3 py-8 text-center text-sm text-slate-500">Tidak ada penjualan pada tanggal ini.</div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3 text-left">Menu</th>
                        <th class="px-5 py-3 text-left">Qty</th>
                        <th class="px-5 py-3 text-left">Kontribusi</th>
                        <th class="px-5 py-3 text-left">Penjualan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contributions as $item)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-5 py-3 font-medium text-slate-800 dark:text-white">{{ $item->menu_name }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ number_format($item->total_qty, 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ number_format($item->contribution, 1, ',', '.') }}%</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">Rp {{ number_format($item->total_sales, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-slate-500">Tidak ada penjualan pada tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
