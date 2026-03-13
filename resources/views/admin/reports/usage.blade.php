@extends('layouts.app')

@section('title', 'Laporan Pemakaian')

@section('content')
@php
    $todayDate = now()->startOfDay();
    $isToday = $selectedDate->isSameDay($todayDate);
    $isOwner = request()->routeIs('owner.*');
    $usageRoute = $isOwner ? 'owner.reports.usage' : 'admin.reports.usage';
    $stockRoute = $isOwner ? 'owner.stocks.index' : 'admin.stocks.logs';
@endphp

<div class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-slate-800 dark:text-white">
                Laporan Pemakaian Harian
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Bahan yang digunakan pada {{ $selectedDate->translatedFormat('d F Y') }}
            </p>
        </div>

        <a href="{{ route($stockRoute) }}"
           class="text-sm px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition">
            {{ $isOwner ? 'Monitoring Stok' : 'Riwayat Stok' }}
        </a>
    </div>

    <div class="space-y-3">
        <form method="GET" class="flex gap-2">
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Cari bahan..."
                   class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

            <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">

            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                Terapkan
            </button>
        </form>

        <form method="GET" class="flex items-center gap-2 w-full">
            <input type="hidden" name="search" value="{{ $search }}">

            <a href="{{ route($usageRoute, ['date' => $selectedDate->copy()->subDay()->toDateString(), 'search' => $search]) }}"
               class="flex items-center justify-center w-10 h-10 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                &lt;
            </a>

            <input type="date"
                   name="date"
                   value="{{ $selectedDate->toDateString() }}"
                   max="{{ $todayDate->toDateString() }}"
                   onchange="this.form.submit()"
                   class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">

            @if($isToday)
                <span class="flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600 cursor-not-allowed">
                    &gt;
                </span>
            @else
                <a href="{{ route($usageRoute, ['date' => $selectedDate->copy()->addDay()->toDateString(), 'search' => $search]) }}"
                   class="flex items-center justify-center w-10 h-10 rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    &gt;
                </a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <p class="text-xs text-slate-500 uppercase">Bahan Terpakai</p>
            <p class="mt-1 text-xl font-semibold">{{ number_format($summary['ingredients_count']) }}</p>
        </div>

        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <p class="text-xs text-slate-500 uppercase">Log Pemakaian</p>
            <p class="mt-1 text-xl font-semibold">{{ number_format($summary['logs_count']) }}</p>
        </div>

        <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
            <p class="text-xs text-slate-500 uppercase">Total Pemakaian</p>
            <p class="mt-1 text-xl font-semibold">{{ number_format($summary['total_base_quantity'], 2) }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <table class="w-full text-sm">
            <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300">
                <tr>
                    <th class="p-3 text-left">Bahan</th>
                    <th class="p-3 text-center">Total Pemakaian</th>
                    <th class="p-3 text-center">Frekuensi</th>
                    <th class="p-3 text-center">Terakhir Dipakai</th>
                </tr>
            </thead>
            <tbody>
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
                                $displayUnit = 'l';
                            } else {
                                $displayUnit = 'ml';
                            }
                        }

                        $formatted = number_format($total, 2, '.', '');
                        $formatted = rtrim(rtrim($formatted, '0'), '.');
                    @endphp

                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="p-3 font-medium">{{ $item->ingredient_name }}</td>
                        <td class="p-3 text-center font-semibold text-red-500">{{ $formatted }} {{ $displayUnit }}</td>
                        <td class="p-3 text-center">{{ number_format($item->usage_count) }} kali</td>
                        <td class="p-3 text-center text-slate-500">{{ \Carbon\Carbon::parse($item->last_used_at)->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-6 text-center text-slate-500">Tidak ada pemakaian bahan pada tanggal ini</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="text-sm text-slate-500">
            Halaman <b>{{ $usageItems->currentPage() }}</b>
            dari <b>{{ $usageItems->lastPage() }}</b>
            | Total <b>{{ $usageItems->total() }}</b>
        </div>

        <div class="flex gap-2">
            @if($usageItems->onFirstPage())
                <span class="px-3 py-1 text-sm rounded-lg bg-slate-200 text-slate-400">Previous</span>
            @else
                <a href="{{ $usageItems->previousPageUrl() }}" class="px-3 py-1 text-sm rounded-lg border hover:bg-slate-50">Previous</a>
            @endif

            @if($usageItems->hasMorePages())
                <a href="{{ $usageItems->nextPageUrl() }}" class="px-3 py-1 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700">Next</a>
            @else
                <span class="px-3 py-1 text-sm rounded-lg bg-slate-200 text-slate-400">Next</span>
            @endif
        </div>
    </div>

</div>

@endsection
