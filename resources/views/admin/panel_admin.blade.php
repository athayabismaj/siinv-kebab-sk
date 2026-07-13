@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')
@php
    $stockStatusKey = $dailyStockStatus['key'] ?? 'not_opened';
    $sessionTone = match ($stockStatusKey) {
        'closed' => [
            'dot' => 'bg-emerald-500',
            'text' => 'text-emerald-700 dark:text-emerald-300',
            'badge' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300',
        ],
        'open' => [
            'dot' => 'bg-amber-500',
            'text' => 'text-amber-700 dark:text-amber-300',
            'badge' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300',
        ],
        default => [
            'dot' => 'bg-slate-400',
            'text' => 'text-slate-700 dark:text-slate-300',
            'badge' => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        ],
    };
@endphp

@push('styles')
<style>
    .admin-dashboard-kpis,
    .admin-dashboard-main,
    .admin-dashboard-bottom {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
    }

    .admin-dashboard-sales-row {
        display: grid;
        grid-template-columns: 72px minmax(0, 1fr) 88px;
        align-items: center;
        gap: 12px;
    }

    .admin-dashboard-sales-list {
        display: grid;
        grid-template-rows: repeat(7, minmax(30px, 1fr));
        gap: 10px;
    }

    .admin-dashboard-session-metrics,
    .admin-dashboard-shortcuts {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .admin-dashboard-shortcuts {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-auto-rows: minmax(56px, 1fr);
    }

    .admin-dashboard-stock-row,
    .admin-dashboard-activity-row {
        display: grid;
        align-items: center;
        gap: 12px;
    }

    .admin-dashboard-stock-row {
        grid-template-columns: minmax(130px, 1fr) minmax(130px, .8fr) auto;
        min-height: 45px;
    }

    .admin-dashboard-activity-row {
        grid-template-columns: minmax(100px, .8fr) minmax(120px, 1.2fr) auto;
        gap: 8px;
    }

    .admin-dashboard-stock-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .admin-dashboard-stock-icon > svg {
        display: block;
        flex: none;
    }

    [x-cloak] {
        display: none !important;
    }

    @media (max-width: 639px) {
        .admin-dashboard-sales-row {
            grid-template-columns: 56px minmax(0, 1fr) 72px;
            gap: 8px;
        }

        .admin-dashboard-stock-row,
        .admin-dashboard-activity-row {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .admin-dashboard-stock-row > :nth-child(2),
        .admin-dashboard-activity-row > :nth-child(2) {
            display: none;
        }
    }

    @media (min-width: 640px) {
        .admin-dashboard-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .admin-dashboard-kpis {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .admin-dashboard-main {
            grid-template-columns: minmax(0, 1.68fr) minmax(310px, 1fr);
            align-items: stretch;
        }

        .admin-dashboard-bottom {
            grid-template-columns: minmax(0, 1.16fr) minmax(350px, 0.84fr);
            align-items: start;
        }

        .admin-dashboard-main > *,
        .admin-dashboard-bottom > * {
            min-width: 0;
        }
    }
</style>
@endpush

<div class="admin-dashboard space-y-3">
    <x-page-header 
        title="Dashboard Admin" 
        subtitle="Pantau penjualan, stok, dan sesi kasir dari satu tempat." 
        breadcrumb-parent="Admin" 
        breadcrumb-child="Ringkasan Operasional">
        
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex h-9 w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-[11px] font-bold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                <svg class="h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M6 21V7l6-4 6 4v14M9 21v-6h6v6"></path></svg>
                {{ $branchScopeLabel ?? 'Semua Cabang' }}
            </div>
            <div class="inline-flex h-9 w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-[11px] font-bold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                {{ now()->translatedFormat('d F Y') }}
            </div>
        </div>
    </x-page-header>

    <section class="rounded-lg border border-blue-100 bg-blue-50/70 px-4 py-3 shadow-sm dark:border-blue-900/50 dark:bg-blue-950/20">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-white text-blue-600 dark:border-blue-900/70 dark:bg-slate-900 dark:text-blue-300">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M6 21V7l6-4 6 4v14M9 21v-6h6v6"></path></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-300">Cabang Aktif</p>
                    <p class="mt-0.5 truncate text-sm font-black text-slate-900 dark:text-white">{{ $branchScopeLabel ?? 'Semua Cabang' }}</p>
                    <p class="mt-0.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $branchScopeDescription ?? 'Data operasional mengikuti cabang aktif.' }}</p>
                </div>
            </div>
            <span class="inline-flex w-fit items-center rounded-md border border-blue-200 bg-white px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider text-blue-700 dark:border-blue-900/70 dark:bg-slate-900 dark:text-blue-300">
                {{ number_format($activeBranchCount ?? 0) }} Cabang Akses
            </span>
        </div>
    </section>

    <section class="admin-dashboard-kpis gap-2.5">
        @foreach([
            ['label' => 'Menu Aktif', 'value' => number_format($totalActiveMenus), 'note' => 'siap dijual', 'icon' => 'menu'],
            ['label' => 'Bahan Baku', 'value' => number_format($totalIngredients), 'note' => 'terdaftar', 'icon' => 'stock'],
            ['label' => 'Transaksi Hari Ini', 'value' => number_format($transactionsTodayCount), 'note' => 'transaksi', 'icon' => 'transaction'],
            ['label' => 'Pengeluaran Hari Ini', 'value' => 'Rp ' . number_format($expenseToday['total'] ?? 0, 0, ',', '.'), 'note' => number_format($expenseToday['count'] ?? 0) . ' catatan', 'icon' => 'expense', 'danger' => true],
        ] as $metric)
            <article class="flex min-h-[70px] items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border {{ ($metric['danger'] ?? false) ? 'border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900/60 dark:bg-rose-500/10 dark:text-rose-300' : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                    @if($metric['icon'] === 'menu')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h10"></path></svg>
                    @elseif($metric['icon'] === 'stock')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    @elseif($metric['icon'] === 'transaction')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14"></path></svg>
                    @else
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3"></path></svg>
                    @endif
                </span>
                <div class="flex min-w-0 flex-col justify-center">
                    <p class="truncate text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $metric['label'] }}</p>
                    <p class="mt-1 truncate text-lg font-black leading-none {{ ($metric['danger'] ?? false) ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">{{ $metric['value'] }}</p>
                    <p class="mt-1 truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $metric['note'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <div class="admin-dashboard-main gap-3">
        <section class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Performa Penjualan</h2>
                <p class="mt-0.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">Omzet tujuh hari terakhir</p>
            </div>
            <div class="admin-dashboard-sales-list flex-1 p-4">
                @foreach($salesLast7Days as $day)
                    <div class="admin-dashboard-sales-row">
                        <p class="truncate text-[10px] {{ $day['is_today'] ? 'font-black text-slate-900 dark:text-white' : 'font-bold text-slate-500 dark:text-slate-400' }}">
                            {{ $day['is_today'] ? 'Hari ini' : $day['label'] }}
                        </p>
                        <div class="h-2 overflow-hidden rounded-sm bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-sm {{ $day['is_today'] ? 'bg-blue-600 dark:bg-blue-500' : 'bg-slate-300 dark:bg-slate-600' }}" style="width: {{ $day['bar_width'] }}%"></div>
                        </div>
                        <p class="text-right text-[10px] font-black text-slate-700 dark:text-slate-300">Rp {{ number_format($day['omzet'], 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-full min-h-[318px] flex-col p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Status Sesi Stok</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $sessionTone['dot'] }}"></span>
                            <h2 class="text-base font-black {{ $sessionTone['text'] }}">{{ $dailyStockStatus['label'] ?? 'Belum Dibuka' }}</h2>
                        </div>
                        <p class="mt-1 text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $dailyStockStatus['description'] ?? 'Belum ada sesi stok harian.' }}</p>
                    </div>
                    <span class="rounded-md border px-2 py-1 text-[9px] font-bold uppercase tracking-wider {{ $sessionTone['badge'] }}">Hari Ini</span>
                </div>

                <div class="admin-dashboard-session-metrics mt-3 divide-x divide-slate-200 rounded-lg border border-slate-200 dark:divide-slate-700 dark:border-slate-700">
                    @foreach([
                        ['label' => 'Total', 'value' => $dailyStockStatus['total_sessions'] ?? 0],
                        ['label' => 'Aktif', 'value' => $dailyStockStatus['open_sessions'] ?? 0],
                        ['label' => 'Tutup', 'value' => $dailyStockStatus['closed_sessions'] ?? 0],
                    ] as $sessionMetric)
                        <div class="py-3 text-center">
                            <p class="text-lg font-black leading-none text-slate-900 dark:text-white">{{ number_format($sessionMetric['value']) }}</p>
                            <p class="mt-1 text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $sessionMetric['label'] }}</p>
                        </div>
                    @endforeach
                </div>

                <nav class="admin-dashboard-shortcuts mt-3 flex-1 gap-2" aria-label="Aksi cepat admin">
                    @foreach([
                        ['route' => route('admin.stocks.index'), 'label' => 'Restok', 'path' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                        ['route' => route('admin.daily-stocks.index'), 'label' => 'Sesi Stok', 'path' => 'M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['route' => route('admin.reports.cashflow.create'), 'label' => 'Pengeluaran', 'path' => 'M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3'],
                        ['route' => route('admin.transactions.index'), 'label' => 'Transaksi', 'path' => 'M9 17v-6m4 6V7m4 10v-4M5 21h14'],
                    ] as $shortcut)
                        <a href="{{ $shortcut['route'] }}" class="flex h-full min-h-[56px] items-center gap-2 rounded-lg border border-slate-200 px-3 text-[10px] font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-800 dark:hover:bg-blue-950/20">
                            <svg class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $shortcut['path'] }}"></path></svg>
                            {{ $shortcut['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </section>
    </div>

    <div class="admin-dashboard-bottom gap-3">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Stok Perlu Perhatian</h2>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ number_format($lowStockSummary['total_low'] ?? 0) }} bahan di bawah batas</p>
                </div>
                <a href="{{ route('admin.stocks.index') }}" class="text-[9px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Kelola</a>
            </div>
            <div class="max-h-[286px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @forelse($lowStockItems as $item)
                    @php $isCritical = ($item['status_key'] ?? '') === 'critical'; @endphp
                    <div class="admin-dashboard-stock-row px-4 py-2">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <span class="admin-dashboard-stock-icon h-7 w-7 shrink-0 rounded-md bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </span>
                            <p class="truncate text-[11px] font-bold text-slate-800 dark:text-slate-200">{{ $item['name'] }}</p>
                        </div>
                        <p class="truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $item['stock_label'] }} / min. {{ $item['minimum_label'] }}</p>
                        <span class="rounded-full border px-2 py-0.5 text-[8px] font-bold uppercase tracking-wider {{ $isCritical ? 'border-red-200 text-red-600 dark:border-red-900/60 dark:text-red-300' : 'border-amber-200 text-amber-600 dark:border-amber-900/60 dark:text-amber-300' }}">
                            {{ $item['status_label'] ?? 'Rendah' }}
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Semua stok aman.</div>
                @endforelse
            </div>
        </section>

        <section x-data="{ activeTab: 'menu' }" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-3 dark:border-slate-800">
                <div class="flex">
                    <button type="button" @click="activeTab = 'menu'" :class="activeTab === 'menu' ? 'border-blue-600 text-slate-900 dark:text-white font-bold' : 'border-transparent text-slate-400 font-medium hover:text-slate-600 dark:hover:text-slate-300'" class="border-b-2 px-3 py-2.5 text-[10px] transition">Menu Terlaris</button>
                    <button type="button" @click="activeTab = 'activity'" :class="activeTab === 'activity' ? 'border-blue-600 text-slate-900 dark:text-white font-bold' : 'border-transparent text-slate-400 font-medium hover:text-slate-600 dark:hover:text-slate-300'" class="border-b-2 px-3 py-2.5 text-[10px] transition">Aktivitas Hari Ini</button>
                </div>
                <a x-show="activeTab === 'activity'" x-cloak href="{{ route('admin.stocks.logs') }}" class="text-[9px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Semua</a>
            </div>

            <div x-show="activeTab === 'menu'" class="max-h-[286px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @forelse($topMenusToday as $index => $menu)
                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded border border-slate-200 text-[9px] font-black leading-none text-slate-600 dark:border-slate-700 dark:text-slate-300">{{ $index + 1 }}</span>
                            <p class="truncate text-[10px] font-bold text-slate-800 dark:text-slate-200">{{ $menu->name }}</p>
                        </div>
                        <p class="shrink-0 text-[10px] font-black text-slate-700 dark:text-slate-300">{{ number_format($menu->sold_qty) }} pcs</p>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center text-[10px] font-medium text-slate-500">Belum ada transaksi.</div>
                @endforelse
            </div>

            <div x-show="activeTab === 'activity'" x-cloak class="max-h-[286px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @forelse($recentStockActivities as $item)
                    <div class="admin-dashboard-activity-row px-3 py-2">
                        <p class="truncate text-[10px] font-bold text-slate-800 dark:text-slate-200">{{ $item['ingredient_name'] }}</p>
                        <p class="truncate text-[9px] font-medium text-slate-500 dark:text-slate-400">{{ $item['activity'] }} &middot; {{ $item['time']->format('d M H:i') }}</p>
                        <p class="shrink-0 text-[10px] font-black {{ str_starts_with($item['quantity_label'], '+') ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">{{ $item['quantity_label'] }}</p>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center text-[10px] font-medium text-slate-500">Belum ada aktivitas.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
