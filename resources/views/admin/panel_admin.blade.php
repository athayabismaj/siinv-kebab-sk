@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="space-y-6 md:space-y-8">

    <div>
        <h1 class="text-xl md:text-2xl font-bold text-slate-800 dark:text-white">
            Dashboard Admin
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Ringkasan operasional inventory dan kasir hari ini
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm">
            <h2 class="text-sm text-slate-500 dark:text-slate-400">Total Menu Aktif</h2>
            <p class="text-xl md:text-2xl font-bold text-slate-800 dark:text-white mt-2">
                {{ number_format($totalActiveMenus) }} Menu
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm">
            <h2 class="text-sm text-slate-500 dark:text-slate-400">Total Bahan</h2>
            <p class="text-xl md:text-2xl font-bold text-slate-800 dark:text-white mt-2">
                {{ number_format($totalIngredients) }} Bahan
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm sm:col-span-2 lg:col-span-1">
            <h2 class="text-sm text-slate-500 dark:text-slate-400">Transaksi Hari Ini</h2>
            <p class="text-xl md:text-2xl font-bold text-slate-800 dark:text-white mt-2">
                {{ number_format($transactionsTodayCount) }} transaksi
            </p>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm">
            <div class="flex flex-wrap items-center gap-2 justify-between">
                <h2 class="text-base font-semibold text-slate-800 dark:text-white">Stok Hampir Habis</h2>
                <a href="{{ route('admin.stocks.index') }}" class="text-xs text-blue-600 hover:underline">Kelola Stok</a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($lowStockItems as $item)
                    @php
                        $isCritical = ($item['status_key'] ?? '') === 'critical';
                        $boxClass = $isCritical
                            ? 'border-red-200 bg-red-50 dark:border-red-900/40 dark:bg-red-900/10'
                            : 'border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-900/10';
                        $badgeClass = $isCritical
                            ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
                    @endphp
                    <div class="rounded-xl border px-4 py-3 {{ $boxClass }}">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $item['name'] }}</p>
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $badgeClass }}">
                                {{ $item['status_label'] ?? 'Rendah' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                            Stok: {{ $item['stock_label'] }}
                            <span class="mx-1">|</span>
                            Minimum: {{ $item['minimum_label'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Semua bahan masih di atas batas minimum.
                    </p>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 md:p-6 shadow-sm">
            <div class="flex flex-wrap items-center gap-2 justify-between">
                <h2 class="text-base font-semibold text-slate-800 dark:text-white">Menu Terlaris Hari Ini</h2>
                <span class="text-xs text-slate-500 dark:text-slate-400">Opsional</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($topMenusToday as $index => $menu)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 dark:border-slate-800 px-3 md:px-4 py-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ $index + 1 }}
                            </span>
                            <p class="text-sm font-medium text-slate-800 dark:text-slate-100 break-words">{{ $menu->name }}</p>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 whitespace-nowrap">{{ number_format($menu->sold_qty) }} pcs</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Belum ada transaksi hari ini.
                    </p>
                @endforelse
            </div>
        </div>

    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-2 justify-between">
            <h2 class="text-base font-semibold text-slate-800 dark:text-white">Aktivitas Stok Terakhir</h2>
            <a href="{{ route('admin.stocks.logs') }}" class="text-xs text-blue-600 hover:underline">Lihat Semua</a>
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="px-6 py-3 text-left">Waktu</th>
                        <th class="px-6 py-3 text-left">Bahan</th>
                        <th class="px-6 py-3 text-left">Aktivitas</th>
                        <th class="px-6 py-3 text-left">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentStockActivities as $item)
                        <tr class="border-t border-slate-200 dark:border-slate-800">
                            <td class="px-6 py-4 text-slate-500">{{ $item['time']->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800 dark:text-slate-100">{{ $item['ingredient_name'] }}</td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $item['activity'] }}</td>
                            <td class="px-6 py-4 font-semibold {{ str_starts_with($item['quantity_label'], '+') ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $item['quantity_label'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                Belum ada aktivitas stok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden divide-y divide-slate-200 dark:divide-slate-800">
            @forelse($recentStockActivities as $item)
                <div class="px-4 py-3 space-y-1.5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 break-words">
                            {{ $item['ingredient_name'] }}
                        </p>
                        <p class="text-xs text-slate-500 whitespace-nowrap">
                            {{ $item['time']->format('H:i') }}
                        </p>
                    </div>
                    <p class="text-xs text-slate-500">
                        {{ $item['time']->format('d M Y') }}
                    </p>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $item['activity'] }}</p>
                        <p class="text-sm font-semibold whitespace-nowrap {{ str_starts_with($item['quantity_label'], '+') ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $item['quantity_label'] }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    Belum ada aktivitas stok.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection
