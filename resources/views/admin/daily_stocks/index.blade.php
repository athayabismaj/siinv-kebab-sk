@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Stok Harian Kasir')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Stok Harian Kasir" 
        subtitle="Kelola sesi harian operasional kasir. Buka sesi baru, transfer stok dari gudang utama, dan tutup sesi dengan memasukkan sisa akhir bahan baku." 
        breadcrumb-parent="Kasir & Stok" 
        breadcrumb-child="Stok Harian Kasir">
    </x-page-header>

    @php
        $todayDate = now()->startOfDay();
        $isSelectedFutureDate = $selectedDate->isAfter($todayDate);
        $isSelectedPastDate = $selectedDate->isBefore($todayDate);
        $isAtToday = $selectedDate->isSameDay($todayDate);
        
        $prevDate = $selectedDate->copy()->subDay()->toDateString();
        $nextDate = $selectedDate->copy()->addDay()->toDateString();
        $baseQuery = request()->except(['date', 'cashier_id', 'page']);
        
        // Atur ulang muncul jika tidak di hari ini, kategori aktif, atau kasir yang dipilih bukan kasir default (pertama)
        $firstCashierId = (int) ($cashiers->first()->id ?? 0);
        $hasActiveFilters = !$isAtToday || $selectedCategoryId > 0 || ((int) $selectedCashierId !== $firstCashierId);
    @endphp

    <div class="flex flex-col gap-3 w-full mb-4 relative z-10">
        {{-- ROW 1: Date Navigator + Atur Ulang --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
            {{-- DATE NAVIGATOR --}}
            <div class="flex-1 flex items-center px-1 w-full h-10 rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
                <a href="{{ route('admin.daily-stocks.index', array_merge($baseQuery, ['date' => $prevDate, 'cashier_id' => $selectedCashierId])) }}" class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                </a>

                <form method="GET" action="{{ route('admin.daily-stocks.index') }}" class="flex-1 min-w-0 h-full">
                    @foreach($baseQuery as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <input type="hidden" name="cashier_id" value="{{ $selectedCashierId }}">
                    <input type="date" name="date" value="{{ $selectedDate->toDateString() }}" max="{{ $todayDate->toDateString() }}" data-submit-on-change class="h-full w-full bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">
                </form>

                @if($isAtToday)
                    <span class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </span>
                @else
                    <a href="{{ route('admin.daily-stocks.index', array_merge($baseQuery, ['date' => $nextDate, 'cashier_id' => $selectedCashierId])) }}" class="flex shrink-0 h-8 w-10 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endif
            </div>

            {{-- ATUR ULANG --}}
            @if($hasActiveFilters)
                <a href="{{ route('admin.daily-stocks.index') }}" class="inline-flex h-10 w-full sm:w-auto items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[12px] font-bold text-slate-500 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-rose-500/30 dark:hover:bg-rose-500/10 dark:hover:text-rose-300 shrink-0 whitespace-nowrap">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    Atur Ulang
                </a>
            @endif
        </div>

        {{-- ROW 2: Cashier Dropdown + Category Dropdown --}}
        <form method="GET" action="{{ route('admin.daily-stocks.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
            <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
            
            {{-- CASHIER DROPDOWN --}}
            <div class="w-full sm:flex-1 relative">
                <select name="cashier_id" data-submit-on-change class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-white pl-4 pr-10 text-center text-[13px] font-bold text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                    <option value="">Semua Kasir</option>
                    @forelse($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" {{ (int) $selectedCashierId === (int) $cashier->id ? 'selected' : '' }}>
                            {{ $cashier->name }}{{ $cashier->branch?->name ? ' - '.$cashier->branch->name : '' }}
                        </option>
                    @empty
                        <option value="">Belum ada kasir</option>
                    @endforelse
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

            {{-- CATEGORY DROPDOWN --}}
            <div class="w-full sm:flex-1 relative">
                <select name="category_id" data-submit-on-change class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-center text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 appearance-none">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (int) $selectedCategoryId === (int) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </form>
    </div>

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
            @if($isSelectedPastDate)
                <p class="text-slate-500 dark:text-slate-400 text-[14px] font-medium max-w-md mb-4">
                    Tidak ada sesi operasional yang tercatat pada tanggal ini.
                </p>
                <div class="inline-flex max-w-md items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-left shadow-sm dark:border-slate-700/70 dark:bg-slate-800/50 dark:shadow-none">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 ring-1 ring-slate-200 dark:bg-slate-900/80 dark:text-slate-300 dark:ring-slate-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"></path>
                        </svg>
                    </span>
                    <p class="text-[12px] font-semibold leading-relaxed text-slate-500 dark:text-slate-300">
                        Tanggal ini sudah lewat. Sesi tidak dapat dibuka mundur.
                    </p>
                </div>
            @elseif($isSelectedFutureDate)
                <p class="text-slate-500 dark:text-slate-400 text-[14px] font-medium max-w-md mb-4">
                    Belum ada sesi operasional untuk tanggal ini.
                </p>
                <div class="inline-flex max-w-md items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 py-3 text-left shadow-sm dark:border-slate-700/70 dark:bg-slate-800/50 dark:shadow-none">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 ring-1 ring-slate-200 dark:bg-slate-900/80 dark:text-slate-300 dark:ring-slate-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-6 4h.01M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </span>
                    <p class="text-[12px] font-semibold leading-relaxed text-slate-500 dark:text-slate-300">
                        Sesi harian hanya dapat dibuka pada hari berjalan.
                    </p>
                </div>
            @else
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
            @endif
        </div>
    @else
        @php
            $sessionStatus = strtolower(trim((string) $session->status));
            $isSessionOpen = $sessionStatus === 'open';
        @endphp

        {{-- ================= SUMMARY CARDS ================= --}}
        @php
            $cardCount = count($summary['by_unit'] ?? []) + 2;
        @endphp
        <div class="grid gap-3 mb-6 pb-2 overflow-x-auto custom-scrollbar hide-scrollbar-mobile" style="grid-template-columns: repeat({{ $cardCount }}, minmax(220px, 1fr));">
            {{-- Card 1: Total Bahan --}}
            <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-[0_2px_10px_-3px_rgba(37,99,235,0.05)] hover:shadow-[0_8px_20px_-6px_rgba(37,99,235,0.15)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Jenis Bahan</p>
                        <div class="flex items-baseline gap-1.5 mt-2">
                            <span class="text-3xl font-black text-slate-800 dark:text-white tracking-tight">{{ $summary['items_count'] }}</span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Item</span>
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 transition-all duration-300 shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                </div>
                
                {{-- Decorative element at bottom for balance --}}
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800/60 mt-auto">
                    <span class="text-[10px] font-medium text-slate-400">Total varian dalam sesi</span>
                </div>
            </div>

            @php
                $unitColors = [
                    ['bg' => 'emerald', 'icon' => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['bg' => 'amber', 'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
                    ['bg' => 'violet', 'icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
                ];
            @endphp

            {{-- Per-Unit Cards (Dinamis) --}}
            @foreach($summary['by_unit'] as $idx => $unitData)
                @php
                    $colorSet = $unitColors[$idx % count($unitColors)];
                    $bgColor = $colorSet['bg'];
                @endphp
                <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_20px_-6px_rgba(0,0,0,0.08)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between group">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Stok {{ $unitData['unit'] }}</p>
                            <div class="flex items-baseline gap-1.5 mt-1">
                                <span class="text-2xl font-black text-{{ $bgColor }}-600 dark:text-{{ $bgColor }}-400 tracking-tight">{{ rtrim(rtrim(number_format($unitData['used'], 2, '.', ''), '0'), '.') }}</span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase">Terpakai</span>
                            </div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-{{ $bgColor }}-50 dark:bg-{{ $bgColor }}-900/20 flex items-center justify-center text-{{ $bgColor }}-600 dark:text-{{ $bgColor }}-400 group-hover:scale-110 group-hover:bg-{{ $bgColor }}-100 dark:group-hover:bg-{{ $bgColor }}-900/40 transition-all duration-300 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $colorSet['icon'] }}"></path></svg>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800/60 mt-auto">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Awal</span>
                            <span class="text-[13px] font-extrabold text-slate-700 dark:text-slate-300">{{ rtrim(rtrim(number_format($unitData['opening'], 2, '.', ''), '0'), '.') }}</span>
                        </div>
                        <div class="h-5 w-px bg-slate-200 dark:bg-slate-700"></div>
                        <div class="flex flex-col text-right">
                            <span class="text-[9px] font-bold text-amber-500 uppercase tracking-wider">Sisa</span>
                            <span class="text-[13px] font-extrabold text-slate-700 dark:text-slate-300">{{ rtrim(rtrim(number_format($unitData['remaining'], 2, '.', ''), '0'), '.') }}</span>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Card Terakhir: Est Nilai Terpakai (Rupiah - selalu bisa dijumlahkan) --}}
            <div class="relative overflow-hidden p-5 bg-gradient-to-br from-rose-50 to-white dark:from-rose-900/20 dark:to-slate-900 rounded-2xl border border-rose-100 dark:border-rose-800/50 shadow-[0_2px_10px_-3px_rgba(225,29,72,0.05)] hover:shadow-[0_8px_20px_-6px_rgba(225,29,72,0.15)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between group">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <p class="text-[11px] font-bold text-rose-500 dark:text-rose-400 uppercase tracking-widest mb-1">Nilai Terpakai</p>
                        <div class="flex items-baseline gap-1 mt-2">
                            <span class="text-sm font-bold text-rose-400 dark:text-rose-500">Rp</span>
                            <span class="text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">{{ number_format($summary['total_value'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-rose-900/40 flex items-center justify-center text-rose-600 dark:text-rose-400 group-hover:scale-110 shadow-sm transition-all duration-300 shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v8m0-8V6m0 12v2M5 12a7 7 0 1114 0 7 7 0 01-14 0z"></path></svg>
                    </div>
                </div>
                
                {{-- Decorative element at bottom --}}
                <div class="pt-3 border-t border-rose-100/50 dark:border-rose-800/30 mt-auto">
                    <span class="text-[10px] font-medium text-rose-400/80">Total estimasi bahan habis</span>
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
                <div class="grid w-full grid-cols-[1fr_1fr_auto] gap-2.5 sm:flex sm:w-auto sm:flex-row sm:items-center sm:justify-end md:mt-0">

                    @if($isSessionOpen)
                        {{-- Tambah Bahan --}}
                        <a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]) }}"
                           class="col-span-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-[13px] font-bold text-white shadow-sm transition hover:bg-blue-700 active:scale-[0.98] sm:w-auto">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14m7-7H5"></path>
                            </svg>
                            <span class="whitespace-nowrap">Tambah Bahan</span>
                        </a>

                        {{-- Tutup Sesi --}}
                        <a href="{{ route('admin.daily-stocks.close.form', ['session_id' => $session->id]) }}"
                           class="col-span-1 inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-4 text-[13px] font-bold text-amber-700 shadow-sm transition hover:bg-amber-100 active:scale-[0.98] dark:border-amber-700/70 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/15 sm:w-auto">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18 18 6M6 6l12 12"></path>
                            </svg>
                            <span class="whitespace-nowrap">Tutup Sesi</span>
                        </a>
                    @elseif(!$isSelectedPastDate)
                        {{-- Reopen Sesi --}}
                        <form method="POST" action="{{ route('admin.daily-stocks.reopen') }}" class="col-span-2 sm:w-auto">
                            @csrf
                            <input type="hidden" name="session_id" value="{{ $session->id }}">
                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 text-[13px] font-bold text-white shadow-sm transition hover:bg-amber-600 active:scale-[0.98] sm:w-auto">
                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Buka Kembali</span>
                            </button>
                        </form>
                    @else
                        <div class="col-span-2 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 px-4 text-[13px] font-bold text-slate-500 shadow-sm dark:border-slate-700/70 dark:bg-slate-800/50 dark:text-slate-300 dark:shadow-none sm:w-auto">
                            <svg class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16.5 10.5V7a4.5 4.5 0 10-9 0v3.5m-.75 0h10.5A1.75 1.75 0 0119 12.25v6A1.75 1.75 0 0117.25 20H6.75A1.75 1.75 0 015 18.25v-6a1.75 1.75 0 011.75-1.75z"></path>
                            </svg>
                            <span class="whitespace-nowrap">Sesi Dikunci</span>
                        </div>
                    @endif

                    {{-- Reconcile Data --}}
                    <form method="POST" action="{{ route('admin.daily-stocks.reconcile') }}" class="col-span-1 sm:w-auto">
                        @csrf
                        <input type="hidden" name="session_id" value="{{ $session->id }}">
                        <button type="submit"
                                title="Reconcile Data"
                                aria-label="Reconcile Data"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 active:scale-[0.98] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-blue-800/70 dark:hover:bg-blue-950/30 dark:hover:text-blue-300">
                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M16.5 7.5h3v-3"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M19.2 7.2A7.5 7.5 0 006.3 5.4"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M7.5 16.5h-3v3"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M4.8 16.8a7.5 7.5 0 0012.9 1.8"></path>
                            </svg>
                        </button>
                    </form>

                </div>

            </div>

            {{-- Tabel Daftar Bahan (Responsive) --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="hidden md:table-header-group">
                        <tr class="text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b-2 border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                            <th class="px-6 py-4 whitespace-nowrap text-left rounded-tl-xl">Bahan Baku</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Dibawa</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right">Sisa (Akhir)</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right text-blue-600 dark:text-blue-400">Total Terpakai</th>
                            <th class="px-6 py-4 whitespace-nowrap text-right text-rose-500 rounded-tr-xl">Est. Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80 dark:divide-slate-800/60">
                        @forelse($sessionItems as $item)
                            
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
                            <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition-colors group">
                                <td class="px-6 py-5 whitespace-nowrap align-middle">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-[14px] text-slate-800 dark:text-white group-hover:text-blue-600 transition-colors">{{ $item->ingredient->name }}</p>
                                            @if($selPrice > 0)
                                                <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                                                    Rp {{ number_format($selPrice, 0, ',', '.') }} / {{ $dispUnit === 'pcs' ? 'pack' : $dispUnit }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-right align-middle">
                                    <div class="inline-flex items-baseline gap-1">
                                        <span class="text-[14px] font-bold text-slate-700 dark:text-slate-300 tabular-nums">
                                            {{ rtrim(rtrim(number_format((float) $item->opening_display, 2, '.', ''), '0'), '.') }}
                                        </span>
                                        <span class="text-[10px] font-semibold text-slate-400 uppercase">{{ $item->display_unit }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-right align-middle">
                                    <div class="inline-flex items-baseline gap-1">
                                        <span class="text-[14px] font-bold text-slate-700 dark:text-slate-300 tabular-nums">
                                            {{ rtrim(rtrim(number_format((float) $item->remaining_display, 2, '.', ''), '0'), '.') }}
                                        </span>
                                        <span class="text-[10px] font-semibold text-slate-400 uppercase">{{ $item->display_unit }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-right align-middle">
                                    <div class="inline-flex items-center justify-end gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50/50 dark:bg-blue-900/10">
                                        <span class="text-[15px] font-black text-blue-600 dark:text-blue-400 tabular-nums">
                                            {{ rtrim(rtrim(number_format((float) $item->used_display, 2, '.', ''), '0'), '.') }}
                                        </span>
                                        <span class="text-[10px] font-bold text-blue-500 uppercase">{{ $item->display_unit }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 whitespace-nowrap text-right align-middle">
                                    @if($itemValue > 0)
                                        <div class="inline-flex items-baseline gap-1">
                                            <span class="text-[10px] font-bold text-rose-400">Rp</span>
                                            <span class="text-[14px] font-black text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($itemValue, 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <span class="text-[12px] text-slate-300 dark:text-slate-600 font-medium">—</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- CARD MOBILE --}}
                            <tr class="md:hidden border-b border-slate-100 dark:border-slate-800/50 last:border-0">
                                <td class="p-0">
                                    <div class="p-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <div class="flex justify-between items-start mb-3 gap-2">
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white text-[14px] leading-tight">{{ $item->ingredient->name }}</p>
                                                @if($selPrice > 0)
                                                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">
                                                        Rp {{ number_format($selPrice, 0, ',', '.') }}/{{ $dispUnit === 'pcs' ? 'pack' : $dispUnit }}
                                                    </p>
                                                @endif
                                            </div>
                                            @if($itemValue > 0)
                                                <div class="text-right">
                                                    <span class="text-[9px] font-bold text-rose-400">Rp</span>
                                                    <span class="text-[13px] font-black text-rose-600 dark:text-rose-400 tabular-nums">{{ number_format($itemValue, 0, ',', '.') }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        {{-- Metric Grid Mobile --}}
                                        <div class="flex items-center bg-slate-50 dark:bg-slate-800/40 rounded-xl p-2.5 border border-slate-100 dark:border-slate-700/50 text-center divide-x divide-slate-200/60 dark:divide-slate-700/60">
                                            <div class="flex-1">
                                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-1">Dibawa</p>
                                                <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px] tabular-nums">{{ rtrim(rtrim(number_format((float) $item->opening_display, 2, '.', ''), '0'), '.') }} <span class="text-[8px] font-normal uppercase">{{ $item->display_unit }}</span></p>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mb-1">Sisa</p>
                                                <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px] tabular-nums">{{ rtrim(rtrim(number_format((float) $item->remaining_display, 2, '.', ''), '0'), '.') }} <span class="text-[8px] font-normal uppercase">{{ $item->display_unit }}</span></p>
                                            </div>
                                            <div class="flex-1 bg-blue-50/50 dark:bg-blue-900/10 rounded-r-lg -my-2.5 py-2.5">
                                                <p class="text-[8px] font-bold text-blue-500 uppercase tracking-widest mb-1">Pakai</p>
                                                <p class="font-black text-blue-600 dark:text-blue-400 text-[12px] tabular-nums">{{ rtrim(rtrim(number_format((float) $item->used_display, 2, '.', ''), '0'), '.') }} <span class="text-[8px] font-bold uppercase">{{ $item->display_unit }}</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
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

        @include('partials.pagination_simple', [
            'paginator' => $sessionItems,
            'label' => 'data',
        ])
    @endif
</div>

@endsection
