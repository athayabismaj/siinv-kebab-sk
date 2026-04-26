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

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Card 1 --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="p-3 bg-blue-50 dark:bg-blue-500/10 text-blue-500 rounded-xl shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            </div>
            <div>
                <h2 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Total Menu Aktif</h2>
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($totalActiveMenus) }}</p>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="p-3 bg-amber-50 dark:bg-amber-500/10 text-amber-500 rounded-xl shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <div>
                <h2 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Total Bahan</h2>
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($totalIngredients) }}</p>
            </div>
        </div>

        {{-- Card 3 --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm flex items-center gap-4 sm:col-span-2 lg:col-span-1 transition-all hover:shadow-md">
            <div class="p-3 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 rounded-xl shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h2 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Transaksi Hari Ini</h2>
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($transactionsTodayCount) }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- LOW STOCK --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm flex flex-col h-full">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/60 flex flex-wrap items-center gap-2 justify-between bg-slate-50/50 dark:bg-slate-800/20">
                <h2 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Stok Hampir Habis
                </h2>
                <a href="{{ route('admin.stocks.index') }}" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 uppercase tracking-widest transition-colors">Kelola Stok</a>
            </div>

            <div class="p-0 flex-1">
                @forelse($lowStockItems as $item)
                    @php
                        $isCritical = ($item['status_key'] ?? '') === 'critical';
                        $iconClass = $isCritical ? 'text-red-500' : 'text-amber-500';
                        $badgeClass = $isCritical
                            ? 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-900/30 dark:border-red-800/50 dark:text-red-400'
                            : 'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-900/30 dark:border-amber-800/50 dark:text-amber-400';
                    @endphp
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-slate-100 dark:border-slate-800/60 last:border-0 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="shrink-0 {{ $iconClass }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $item['name'] }}</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Stok: <strong class="text-slate-700 dark:text-slate-300">{{ $item['stock_label'] }}</strong> / {{ $item['minimum_label'] }}</p>
                            </div>
                        </div>
                        <span class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $badgeClass }} whitespace-nowrap">
                            {{ $item['status_label'] ?? 'Rendah' }}
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full min-h-[150px] p-6 text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Semua bahan masih di atas batas minimum.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- TOP MENUS & SALES CHART CONTAINER --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 col-span-1 lg:col-span-2 mt-4">
            
            {{-- TOP MENUS --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm flex flex-col h-full">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/60 flex flex-wrap items-center gap-2 justify-between bg-slate-50/50 dark:bg-slate-800/20">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        Menu Terlaris Hari Ini
                    </h2>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Top 5</span>
                </div>

                <div class="p-0 flex-1">
                    @forelse($topMenusToday as $index => $menu)
                        <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-slate-100 dark:border-slate-800/60 last:border-0 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg {{ $index === 0 ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : ($index === 1 ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : ($index === 2 ? 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400' : 'bg-blue-50 text-blue-500 dark:bg-blue-900/20 dark:text-blue-400')) }} text-[11px] font-bold">
                                    {{ $index + 1 }}
                                </span>
                                <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $menu->name }}</p>
                            </div>
                            <p class="text-[11px] font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ number_format($menu->sold_qty) }} <span class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">pcs</span></p>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center h-full min-h-[150px] p-6 text-center">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Belum ada transaksi hari ini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- SALES CHART --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm flex flex-col h-full">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/60 flex flex-wrap items-center gap-2 justify-between bg-slate-50/50 dark:bg-slate-800/20">
                    <h2 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                        Grafik Penjualan (7 Hari)
                    </h2>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Omzet</span>
                </div>

                <div class="p-5 flex-1 flex flex-col justify-center space-y-4">
                    @foreach($salesLast7Days as $day)
                        <div class="grid grid-cols-[80px_1fr_auto] items-center gap-3">
                            <p class="text-[11px] {{ $day['is_today'] ? 'font-bold text-blue-600 dark:text-blue-400' : 'font-semibold text-slate-500 dark:text-slate-400' }}">
                                {{ $day['is_today'] ? 'Hari ini' : $day['label'] }}
                            </p>
                            <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full bg-blue-500 dark:bg-blue-600 transition-all duration-500" style="width: {{ $day['bar_width'] }}%"></div>
                            </div>
                            <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300 whitespace-nowrap text-right min-w-[70px]">
                                Rp {{ number_format($day['omzet'], 0, ',', '.') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
            
        </div>

    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/60 flex flex-wrap items-center gap-2 justify-between bg-slate-50/50 dark:bg-slate-800/20">
            <h2 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Aktivitas Stok Terakhir
            </h2>
            <a href="{{ route('admin.stocks.logs') }}" class="text-[10px] font-bold text-blue-600 hover:text-blue-700 uppercase tracking-widest transition-colors">Lihat Semua</a>
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800/60 text-[10px] uppercase tracking-widest text-slate-500 dark:text-slate-400 font-bold">
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Bahan</th>
                        <th class="px-5 py-3">Aktivitas</th>
                        <th class="px-5 py-3">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($recentStockActivities as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                            <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ $item['time']->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3 font-bold text-slate-800 dark:text-slate-200">{{ $item['ingredient_name'] }}</td>
                            <td class="px-5 py-3 text-slate-600 dark:text-slate-300">
                                @if($item['activity'] === 'Restok')
                                    <span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Restok</span>
                                @elseif($item['activity'] === 'Pemakaian')
                                    <span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Pemakaian</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ $item['activity'] }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 font-bold {{ str_starts_with($item['quantity_label'], '+') ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $item['quantity_label'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400 font-medium">
                                Belum ada aktivitas stok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800/60">
            @forelse($recentStockActivities as $item)
                <div class="px-5 py-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                    <div class="flex items-start justify-between gap-3 mb-1">
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 break-words">
                            {{ $item['ingredient_name'] }}
                        </p>
                        <p class="text-[10px] font-semibold text-slate-400 whitespace-nowrap">
                            {{ $item['time']->format('d M Y H:i') }}
                        </p>
                    </div>
                    <div class="flex items-center justify-between gap-3 mt-2">
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                            @if($item['activity'] === 'Restok')
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Restok
                            @elseif($item['activity'] === 'Pemakaian')
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Pemakaian
                            @else
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ $item['activity'] }}
                            @endif
                        </p>
                        <p class="text-xs font-bold whitespace-nowrap {{ str_starts_with($item['quantity_label'], '+') ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $item['quantity_label'] }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-xs font-medium text-slate-500 dark:text-slate-400">
                    Belum ada aktivitas stok.
                </div>
            @endforelse
        </div>
    </div>

</div>

@endsection
