@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')
@php
    $stockStatusKey = $dailyStockStatus['key'] ?? 'not_opened';
    $stockStatusClass = match ($stockStatusKey) {
        'closed' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300',
        'open' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300',
        default => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300',
    };
    $stockDotClass = match ($stockStatusKey) {
        'closed' => 'bg-emerald-500',
        'open' => 'bg-amber-500',
        default => 'bg-slate-400',
    };
@endphp

<div class="space-y-5 md:space-y-6">

    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-blue-600 dark:text-blue-400">Admin Operations</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Dashboard Admin
            </h1>
            <div class="mt-1 flex flex-wrap items-center gap-2 text-sm font-medium text-slate-500 dark:text-slate-400">
                <span>Ringkasan stok, sesi kasir, dan aktivitas operasional hari ini.</span>
                <span class="hidden h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700 sm:inline-block"></span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-bold text-slate-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    {{ now()->translatedFormat('d M Y') }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="p-5 md:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">Status Sesi Stok Harian</p>
                        <p class="mt-2 text-2xl font-black tracking-tight text-slate-900 dark:text-white md:text-3xl">
                            {{ $dailyStockStatus['label'] ?? 'Belum Dibuka' }}
                        </p>
                        <p class="mt-2 max-w-xl text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                            {{ $dailyStockStatus['description'] ?? 'Belum ada sesi stok harian yang dibuka.' }}
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest {{ $stockStatusClass }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $stockDotClass }}"></span>
                                Operasional Hari Ini
                            </span>
                        </div>
                    </div>

                    <div class="grid w-full grid-cols-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/40 lg:max-w-[360px]">
                        <div class="px-4 py-3 text-center">
                            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($dailyStockStatus['total_sessions'] ?? 0) }}</p>
                            <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Total</p>
                        </div>
                        <div class="border-x border-slate-200 bg-amber-50/70 px-4 py-3 text-center dark:border-slate-700 dark:bg-amber-500/10">
                            <p class="text-2xl font-black text-amber-700 dark:text-amber-300">{{ number_format($dailyStockStatus['open_sessions'] ?? 0) }}</p>
                            <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-600/80 dark:text-amber-300/80">Aktif</p>
                        </div>
                        <div class="bg-emerald-50/70 px-4 py-3 text-center dark:bg-emerald-500/10">
                            <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ number_format($dailyStockStatus['closed_sessions'] ?? 0) }}</p>
                            <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-600/80 dark:text-emerald-300/80">Tutup</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Aksi Cepat</p>
                    <h2 class="mt-1 text-lg font-black text-slate-900 dark:text-white">Operasional Admin</h2>
                </div>
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">Shortcut</span>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <a href="{{ route('admin.stocks.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:bg-blue-950/20">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-black text-slate-800 group-hover:text-blue-700 dark:text-slate-100 dark:group-hover:text-blue-300">Restok Bahan</span>
                        <span class="mt-0.5 block truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">Kelola stok bahan baku</span>
                    </span>
                </a>
                <a href="{{ route('admin.daily-stocks.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:bg-blue-950/20">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-black text-slate-800 group-hover:text-blue-700 dark:text-slate-100 dark:group-hover:text-blue-300">Sesi Stok Harian</span>
                        <span class="mt-0.5 block truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">Buka, transfer, tutup sesi</span>
                    </span>
                </a>
                <a href="{{ route('admin.reports.cashflow.create') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:bg-blue-950/20">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l-1 11H6L5 9zm7 4v3"></path></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-black text-slate-800 group-hover:text-blue-700 dark:text-slate-100 dark:group-hover:text-blue-300">Input Pengeluaran</span>
                        <span class="mt-0.5 block truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">Catat biaya operasional</span>
                    </span>
                </a>
                <a href="{{ route('admin.transactions.index') }}" class="group flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:bg-blue-950/20">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14"></path></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-black text-slate-800 group-hover:text-blue-700 dark:text-slate-100 dark:group-hover:text-blue-300">Monitoring Transaksi</span>
                        <span class="mt-0.5 block truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">Pantau transaksi kasir</span>
                    </span>
                </a>
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Menu Aktif</p>
            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($totalActiveMenus) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Menu yang tersedia dijual.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Total Bahan</p>
            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($totalIngredients) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Bahan baku terdaftar.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Transaksi Hari Ini</p>
            <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($transactionsTodayCount) }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Transaksi masuk hari ini.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">Pengeluaran Hari Ini</p>
            <p class="mt-2 text-2xl font-black text-rose-600 dark:text-rose-400">Rp {{ number_format($expenseToday['total'] ?? 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ number_format($expenseToday['count'] ?? 0) }} catatan pengeluaran.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800/70 dark:bg-slate-800/20">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Stok Hampir Habis</h2>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        {{ number_format($lowStockSummary['critical_count'] ?? 0) }} kritis, {{ number_format($lowStockSummary['warning_count'] ?? 0) }} rendah.
                    </p>
                </div>
                <a href="{{ route('admin.stocks.index') }}" class="text-[10px] font-bold uppercase tracking-widest text-blue-600 hover:text-blue-700 dark:text-blue-400">Kelola</a>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/70">
                @forelse($lowStockItems as $item)
                    @php
                        $isCritical = ($item['status_key'] ?? '') === 'critical';
                        $badgeClass = $isCritical
                            ? 'border-red-200 bg-red-50 text-red-600 dark:border-red-900/60 dark:bg-red-500/10 dark:text-red-300'
                            : 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300';
                    @endphp
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $item['name'] }}</p>
                            <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Stok: {{ $item['stock_label'] }} / {{ $item['minimum_label'] }}</p>
                        </div>
                        <span class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-[9px] font-bold uppercase tracking-widest {{ $badgeClass }}">
                            {{ $item['status_label'] ?? 'Rendah' }}
                        </span>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Stok aman</p>
                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Semua bahan masih di atas batas minimum.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800/70 dark:bg-slate-800/20">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Grafik Penjualan</h2>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Omzet 7 hari terakhir.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">7 Hari</span>
            </div>

            <div class="space-y-4 p-5">
                @foreach($salesLast7Days as $day)
                    <div class="grid grid-cols-[76px_minmax(0,1fr)_86px] items-center gap-3">
                        <p class="truncate text-[11px] {{ $day['is_today'] ? 'font-black text-blue-600 dark:text-blue-400' : 'font-bold text-slate-500 dark:text-slate-400' }}">
                            {{ $day['is_today'] ? 'Hari ini' : $day['label'] }}
                        </p>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-blue-600 transition-all duration-500 dark:bg-blue-500" style="width: {{ $day['bar_width'] }}%"></div>
                        </div>
                        <p class="text-right text-[11px] font-black text-slate-600 dark:text-slate-300">
                            Rp {{ number_format($day['omzet'], 0, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800/70 dark:bg-slate-800/20">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Menu Terlaris Hari Ini</h2>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Top 5 penjualan menu.</p>
                </div>
                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-amber-600 dark:bg-amber-500/10 dark:text-amber-300">Top 5</span>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/70">
                @forelse($topMenusToday as $index => $menu)
                    <div class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $index === 0 ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }} text-[11px] font-black">
                                {{ $index + 1 }}
                            </span>
                            <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $menu->name }}</p>
                        </div>
                        <p class="shrink-0 text-[11px] font-black text-slate-600 dark:text-slate-300">{{ number_format($menu->sold_qty) }} pcs</p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Belum ada transaksi hari ini.</div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 dark:border-slate-800/70 dark:bg-slate-800/20">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Aktivitas Stok Terakhir</h2>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Mutasi stok terbaru.</p>
                </div>
                <a href="{{ route('admin.stocks.logs') }}" class="text-[10px] font-bold uppercase tracking-widest text-blue-600 hover:text-blue-700 dark:text-blue-400">Lihat Semua</a>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800/70">
                @forelse($recentStockActivities as $item)
                    <div class="px-5 py-3.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-xs font-black text-slate-800 dark:text-slate-200">{{ $item['ingredient_name'] }}</p>
                                <p class="mt-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $item['time']->format('d M Y H:i') }}</p>
                            </div>
                            <p class="shrink-0 text-xs font-black {{ str_starts_with($item['quantity_label'], '+') ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $item['quantity_label'] }}
                            </p>
                        </div>
                        <p class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500 dark:text-slate-400">
                            <span class="h-1.5 w-1.5 rounded-full {{ $item['activity'] === 'Restok' ? 'bg-emerald-500' : ($item['activity'] === 'Pemakaian' ? 'bg-red-500' : 'bg-amber-500') }}"></span>
                            {{ $item['activity'] }}
                        </p>
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Belum ada aktivitas stok.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
