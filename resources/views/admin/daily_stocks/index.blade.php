@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Stok Harian Kasir')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex-1 w-full overflow-hidden">
            
            {{-- BREADCRUMB (Anti Pecah di Mobile) --}}
            <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto pb-1">
                <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Beranda
                </a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                    Kasir & Stok
                </span>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                    Stok Harian Kasir
                </span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Stok Harian Kasir
            </h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Kelola sesi harian operasional kasir. Buka sesi baru, transfer stok dari gudang utama, dan tutup sesi dengan memasukkan sisa akhir bahan baku.
            </p>
        </div>
    </div>

        {{-- ================= KONTROL FILTER ================= --}}
    @php
        $todayDate = now()->startOfDay();
        $isAtToday = $selectedDate->copy()->startOfDay()->greaterThanOrEqualTo($todayDate);
        $prevDate = $selectedDate->copy()->subDay()->toDateString();
        $nextDate = $selectedDate->copy()->addDay()->toDateString();
        $baseQuery = request()->except(['date', 'cashier_id', 'page']);
    @endphp
    <div class="bg-transparent border-none">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-[7fr_3fr] lg:items-center">
            <div>
                <div class="flex items-center px-1 w-full h-[46px] rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
                    <a
                        href="{{ route('admin.daily-stocks.index', array_merge($baseQuery, ['date' => $prevDate, 'cashier_id' => $selectedCashierId])) }}"
                        class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                    </a>

                    <form method="GET" action="{{ route('admin.daily-stocks.index') }}" class="flex-1 min-w-0">
                        @foreach($baseQuery as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="cashier_id" value="{{ $selectedCashierId }}">
                        <input
                            type="date"
                            name="date"
                            value="{{ $selectedDate->toDateString() }}"
                            onchange="this.form.submit()"
                            class="h-[38px] w-full bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]"
                        >
                    </form>

                    @if($isAtToday)
                        <span class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                        </span>
                    @else
                        <a
                            href="{{ route('admin.daily-stocks.index', array_merge($baseQuery, ['date' => $nextDate, 'cashier_id' => $selectedCashierId])) }}"
                            class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    @endif
                </div>
            </div>

            <div>
                <form method="GET" action="{{ route('admin.daily-stocks.index') }}">
                    @foreach($baseQuery as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                    <div class="relative">
                        <select
                            name="cashier_id"
                            onchange="this.form.submit()"
                            class="h-[46px] w-full appearance-none rounded-xl border border-slate-200 bg-white pl-4 pr-10 text-[13px] font-bold text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                        >
                            <option value="">Semua Kasir</option>
                            @forelse($cashiers as $cashier)
                                <option value="{{ $cashier->id }}" {{ (int) $selectedCashierId === (int) $cashier->id ? 'selected' : '' }}>
                                    {{ $cashier->name }}
                                </option>
                            @empty
                                <option value="">Belum ada kasir</option>
                            @endforelse
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.daily-stocks.index') }}" class="flex flex-col sm:flex-row gap-3 w-full relative z-10 py-1">
        <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
        <input type="hidden" name="cashier_id" value="{{ $selectedCashierId }}">

        <div class="flex-1 relative flex items-center w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
            <input type="search" name="search" value="{{ $search }}" placeholder="Cari bahan pada sesi ini..."
                class="w-full h-10 bg-transparent pl-10 pr-4 text-[13px] font-medium text-slate-700 outline-none dark:text-slate-200 placeholder:text-slate-400">
        </div>

        <select name="category_id" class="w-full sm:w-56 h-10 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ (int) $selectedCategoryId === (int) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>

        <div class="flex items-center gap-2">
            @if($search || $selectedCategoryId > 0)
                <a href="{{ route('admin.daily-stocks.index', ['date' => $selectedDate->toDateString(), 'cashier_id' => $selectedCashierId]) }}" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-400 hover:text-red-500 transition-colors px-2">Reset</a>
            @endif
            <button type="submit" class="w-full sm:w-auto px-6 h-10 rounded-xl bg-slate-900 text-white text-[13px] font-bold hover:bg-slate-800 transition shadow-sm dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                Filter
            </button>
        </div>
    </form>

    {{-- ================= KONDISI EMPTY / ERROR ================= --}}
    @if($selectedCashierId <= 0)
        <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-medium text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300 shadow-sm">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Data User Kasir belum tersedia atau belum dipilih. Silakan pilih kasir pada kolom di atas.
        </div>
    @elseif(!$session)
        <div class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-16 text-center shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-4 border border-slate-100 dark:border-slate-700">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-[14px] font-medium max-w-md mb-6">
                Belum ada sesi operasional untuk tanggal dan kasir ini.
            </p>
            
            {{-- Tombol Buka Sesi di letakkan di tengah jika belum ada sesi --}}
            <form method="POST" action="{{ route('admin.daily-stocks.open') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                <input type="hidden" name="cashier_id" value="{{ $selectedCashierId }}">
                <button type="submit" class="h-11 px-8 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 text-white text-[13px] font-bold hover:bg-blue-700 transition-all shadow-md shadow-blue-500/20">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Buka Sesi Harian Baru
                </button>
            </form>
        </div>
    @else
        @php
            $sessionStatus = strtolower(trim((string) $session->status));
            $isSessionOpen = $sessionStatus === 'open';
        @endphp

        {{-- ================= SUMMARY CARDS (Jika Sesi Ada) ================= --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
            {{-- Card 1: Total Bahan --}}
            <div class="p-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-slate-800 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest leading-tight">Bahan<br>Tersedia</p>
                </div>
                <div>
                    <span class="text-2xl font-extrabold text-slate-800 dark:text-white tabular-nums">{{ $summary['items_count'] }}</span>
                </div>
            </div>

            {{-- Card 2: Total Dibawa --}}
            <div class="p-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-slate-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                    </div>
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest leading-tight">Total<br>Dibawa</p>
                </div>
                <div>
                    <span class="text-2xl font-extrabold text-slate-800 dark:text-white tabular-nums">{{ number_format($summary['total_opening'], 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- Card 3: Total Sisa --}}
            <div class="p-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-amber-50 dark:bg-slate-800 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    </div>
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest leading-tight">Total<br>Sisa</p>
                </div>
                <div>
                    <span class="text-2xl font-extrabold text-slate-800 dark:text-white tabular-nums">{{ number_format($summary['total_remaining'], 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- Card 4: Total Terpakai --}}
            <div class="p-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-orange-50 dark:bg-slate-800 flex items-center justify-center text-orange-600 dark:text-orange-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"></path></svg>
                    </div>
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest leading-tight">Total<br>Terpakai</p>
                </div>
                <div>
                    <span class="text-2xl font-extrabold text-orange-600 dark:text-orange-400 tabular-nums">{{ number_format($summary['total_used'], 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- Card 5: Est Nilai Terpakai --}}
            <div class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800/50 rounded-2xl shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow col-span-2 lg:col-span-1">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-800/50 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v8m0-8V6m0 12v2M5 12a7 7 0 1114 0 7 7 0 01-14 0z"></path></svg>
                    </div>
                    <p class="text-[10px] font-bold text-rose-500 dark:text-rose-400 uppercase tracking-widest leading-tight">Nilai<br>Terpakai</p>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-xs font-bold text-rose-400 dark:text-rose-500">Rp</span>
                    <span class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($summary['total_value'] ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- ================= KONTEN SESI & TABEL BAHAN ================= --}}

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            
            {{-- Header Sesi & Tombol Aksi (Sesuai Referensi Gambar) --}}
            <div class="p-5 md:px-6 md:py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                {{-- Info Sesi Kiri --}}
                <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-full {{ $isSessionOpen ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }} flex items-center justify-center font-bold text-lg shrink-0">
                        {{ strtoupper(substr($session->cashier->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-[15px] font-bold text-slate-900 dark:text-white leading-tight">
                            Sesi #{{ $session->id }} <span class="text-slate-300 dark:text-slate-600 mx-1">|</span> {{ $session->cashier->name ?? '-' }}
                        </h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                {{ $session->session_date->translatedFormat('d F Y') }}
                            </span>
                            <span class="text-slate-300 dark:text-slate-600 text-[10px]">&bull;</span>
                            @if($isSessionOpen)
                                <span class="inline-flex items-center gap-1.5 text-[9px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> BUKA
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                                    DITUTUP
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tombol Aksi Kanan — Responsif & Modern --}}
                <div class="grid grid-cols-2 md:flex md:flex-row md:items-center gap-3 w-full md:w-auto mt-5 md:mt-0">

                    @if($isSessionOpen)
                        {{-- Tambah Bahan --}}
                        <a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]) }}"
                           class="col-span-1 md:w-auto h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 md:px-5 text-sm font-bold text-white hover:bg-blue-700 transition-all shadow-sm active:scale-95">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span class="whitespace-nowrap">Tambah Bahan</span>
                        </a>

                        {{-- Tutup Sesi --}}
                        <a href="{{ route('admin.daily-stocks.close.form', ['session_id' => $session->id]) }}"
                           class="col-span-1 md:w-auto h-11 inline-flex items-center justify-center gap-2 rounded-xl border border-amber-400 bg-amber-50 px-4 md:px-5 text-sm font-bold text-amber-700 hover:bg-amber-100 transition-all dark:border-amber-600 dark:bg-slate-800 dark:text-amber-400 dark:hover:bg-slate-700 active:scale-95">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="whitespace-nowrap">Tutup Sesi</span>
                        </a>
                    @else
                        {{-- Reopen Sesi --}}
                        <form method="POST" action="{{ route('admin.daily-stocks.reopen') }}" class="col-span-2 md:w-auto">
                            @csrf
                            <input type="hidden" name="session_id" value="{{ $session->id }}">
                            <button type="submit" class="w-full md:w-auto h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 text-sm font-bold text-white hover:bg-amber-600 transition-all shadow-sm active:scale-95">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Buka Kembali</span>
                            </button>
                        </form>
                    @endif

                    {{-- Reconcile Data --}}
                    <form method="POST" action="{{ route('admin.daily-stocks.reconcile') }}" class="col-span-2 md:w-auto md:ml-2">
                        @csrf
                        <input type="hidden" name="session_id" value="{{ $session->id }}">
                        <button type="submit" class="w-full md:w-auto h-11 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 md:px-5 text-sm font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white active:scale-95 shadow-sm">
                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="md:hidden lg:inline whitespace-nowrap">Reconcile Data</span>
                        </button>
                    </form>

                </div>

            </div>

            {{-- Tabel Daftar Bahan (Responsive) --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="hidden md:table-header-group">
                        <tr class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/10">
                            <th class="px-6 py-4 whitespace-nowrap">Bahan Baku</th>
                            <th class="px-6 py-4 text-center whitespace-nowrap">Dibawa</th>
                            <th class="px-6 py-4 text-center whitespace-nowrap">Sisa (Akhir)</th>
                            <th class="px-6 py-4 text-right whitespace-nowrap text-blue-600 dark:text-blue-400">Total Terpakai</th>
                            <th class="px-6 py-4 text-right whitespace-nowrap text-rose-500">Est. Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($session->items as $item)
                            
                            {{-- ROW DESKTOP --}}
                            @php
                                $usedQty  = (float) $item->used_qty;
                                $selPrice = (float) ($item->ingredient->selling_price ?? 0);
                                $dispUnit = $item->ingredient->display_unit ?? '';
                                $packSize = max(1, (int) ($item->ingredient->pack_size ?? 1));
                                $itemValue = match($dispUnit) {
                                    'kg', 'l' => ($usedQty / 1000) * $selPrice,
                                    'pcs'     => ($usedQty / $packSize) * $selPrice,
                                    default   => $usedQty * $selPrice,
                                };
                            @endphp
                            <tr class="hidden md:table-row hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-bold text-[14px] text-slate-900 dark:text-white">{{ $item->ingredient->name }}</p>
                                    @if($selPrice > 0)
                                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-0.5">
                                            Rp {{ number_format($selPrice, 0, ',', '.') }}/{{ $dispUnit === 'pcs' ? 'pack' : $dispUnit }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap align-middle">
                                    <span class="text-[13px] font-bold text-slate-700 dark:text-slate-300 tabular-nums">
                                        {{ rtrim(rtrim(number_format((float) $item->opening_display, 2, '.', ''), '0'), '.') }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-wider">{{ $item->display_unit }}</span>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap align-middle">
                                    <span class="text-[13px] font-bold text-slate-700 dark:text-slate-300 tabular-nums">
                                        {{ rtrim(rtrim(number_format((float) $item->remaining_display, 2, '.', ''), '0'), '.') }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-400 ml-1 uppercase tracking-wider">{{ $item->display_unit }}</span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                    <span class="text-[14px] font-black text-blue-600 dark:text-blue-400 tabular-nums">
                                        {{ rtrim(rtrim(number_format((float) $item->used_display, 2, '.', ''), '0'), '.') }}
                                    </span>
                                    <span class="text-[10px] font-bold text-blue-400 ml-1 uppercase tracking-wider">{{ $item->display_unit }}</span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                    @if($itemValue > 0)
                                        <span class="text-[13px] font-black text-rose-600 dark:text-rose-400 tabular-nums">
                                            <span class="text-[10px] font-bold text-rose-400 mr-0.5">Rp</span>{{ number_format($itemValue, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-[11px] text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- CARD MOBILE --}}
                            <tr class="md:hidden border-b border-slate-100 dark:border-slate-800/50 last:border-0">
                                <td class="p-0">
                                    <div class="p-4 sm:p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <div class="mb-3">
                                            <p class="font-bold text-slate-900 dark:text-white text-[15px] leading-tight">{{ $item->ingredient->name }}</p>
                                        </div>
                                        
                                        {{-- Metric Grid Mobile --}}
                                        <div class="grid grid-cols-4 gap-0 bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 border border-slate-100 dark:border-slate-700/50 text-center divide-x divide-slate-200 dark:divide-slate-700">
                                            <div>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Dibawa</p>
                                                <p class="font-bold text-slate-700 dark:text-slate-300 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) $item->opening_display, 2, '.', ''), '0'), '.') }} <span class="text-[9px] font-normal uppercase">{{ $item->display_unit }}</span></p>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Sisa</p>
                                                <p class="font-bold text-slate-700 dark:text-slate-300 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) $item->remaining_display, 2, '.', ''), '0'), '.') }} <span class="text-[9px] font-normal uppercase">{{ $item->display_unit }}</span></p>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-blue-500 uppercase tracking-widest mb-1">Pakai</p>
                                                <p class="font-black text-blue-600 dark:text-blue-400 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) $item->used_display, 2, '.', ''), '0'), '.') }} <span class="text-[9px] font-normal uppercase">{{ $item->display_unit }}</span></p>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-rose-500 uppercase tracking-widest mb-1">Nilai</p>
                                                <p class="font-black text-rose-600 dark:text-rose-400 text-xs tabular-nums">
                                                    @if($itemValue > 0)
                                                        <span class="text-[8px] font-bold mr-0.5">Rp</span>{{ number_format($itemValue, 0, ',', '.') }}
                                                    @else
                                                        <span class="text-slate-300 dark:text-slate-600">—</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Belum ada bahan yang dibawa ke dalam sesi ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    @endif
</div>

@endsection

