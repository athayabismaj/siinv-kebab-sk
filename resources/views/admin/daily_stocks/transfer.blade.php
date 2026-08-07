@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Transfer Stok Harian')
@section('disableGlobalAlerts', 'true')

@push('styles')
@vite('resources/css/pages/daily-stock-transfer.css')
@endpush

@section('content')
<div class="transfer-stock-page w-full space-y-5 overflow-x-hidden pb-10">
    <x-page-header 
        title="Siapkan Saldo Awal Stok"
        subtitle="Isi total stok awal hari ini. Sistem menghitung tambahan gudang atau selisih fisik secara otomatis."
        breadcrumb-parent="Stok Harian" 
        breadcrumb-child="Transfer Bahan">
        
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                        <span class="block translate-x-px translate-y-[0.5px] text-[10px] font-black leading-none">
                            {{ strtoupper(substr($session->cashier->name ?? 'U', 0, 1)) }}
                        </span>
                    </span>
                    {{ $session->cashier->name ?? 'User Tidak Diketahui' }}
                </span>
                <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                    {{ $session->session_date->translatedFormat('d F Y') }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-black uppercase tracking-wider text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Sesi #{{ $session->id }} Buka
                </span>
            </div>

            <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
               class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 sm:w-auto dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Sesi
            </a>
        </div>
    </x-page-header>

    {{-- ================= ALERTS ================= --}}
    @include('partials.flash_alerts', ['class' => 'w-full space-y-2'])

    @if($session->carryForwardSource)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-amber-600 ring-1 ring-amber-200 dark:bg-slate-900 dark:text-amber-300 dark:ring-amber-500/30">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <div>
                <p class="text-sm font-black">Saldo sisa dibawa dari sesi {{ $session->carryForwardSource->session_date->translatedFormat('d F Y') }}</p>
                <p class="mt-0.5 text-xs font-semibold leading-relaxed text-amber-700 dark:text-amber-200/80">Angka stok awal sudah terisi otomatis. Naikkan bila mengambil dari gudang, atau turunkan bila stok fisik lebih sedikit.</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="w-full">
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-white px-4 py-3 shadow-sm dark:border-rose-900/60 dark:bg-slate-900">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-300">Input Belum Valid</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4 text-sm font-semibold leading-relaxed text-slate-700 dark:text-slate-200">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= FORM PENCARIAN & FILTER ================= --}}
    <form method="GET" action="{{ route('admin.daily-stocks.transfer.form') }}" class="transfer-filter-form relative z-10">
        <input type="hidden" name="session_id" value="{{ $session->id }}">

        <div class="transfer-filter-search relative flex h-11 items-center rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <svg class="absolute left-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
            <input type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="Cari nama bahan di gudang..."
                   class="h-full w-full rounded-xl border-0 bg-transparent pl-10 pr-4 text-[13px] font-semibold text-slate-700 outline-none placeholder:text-slate-400 focus:ring-0 dark:text-slate-200">
        </div>

        <select name="category_id" data-submit-on-change class="transfer-filter-category h-11 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 text-[12px] font-bold text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <option value="" class="bg-white text-slate-900 dark:bg-slate-800 dark:text-white">Semua Kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" class="bg-white text-slate-900 dark:bg-slate-800 dark:text-white" {{ (int) $selectedCategoryId === (int) $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        @if($search || $selectedCategoryId > 0)
            <a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]) }}" class="inline-flex h-11 shrink-0 items-center justify-center rounded-xl px-4 text-[12px] font-bold text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-950/30">
                Atur Ulang
            </a>
        @endif

        <button type="submit" class="sr-only" tabindex="-1">Cari</button>
    </form>

    {{-- ================= FORM BATCH TRANSFER ================= --}}
    <form method="POST" action="{{ route('admin.daily-stocks.transfer', ['search' => $search, 'category_id' => $selectedCategoryId, 'page' => request()->query('page')]) }}" class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf
        <input type="hidden" name="session_id" value="{{ $session->id }}">

        {{-- HEADER FORM --}}
        <div class="flex flex-col justify-between gap-4 border-b border-slate-100 bg-slate-50/70 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/30 md:flex-row md:items-center">
            <div>
                <h2 class="flex items-center gap-2 text-[13px] font-black uppercase tracking-widest text-slate-900 dark:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Saldo Outlet & Bahan Gudang
                </h2>
                <p class="mt-1 text-[12px] font-medium text-slate-500 dark:text-slate-400">Periksa angka yang terisi, sesuaikan dengan stok fisik, lalu simpan.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-600 dark:border-blue-900/70 dark:bg-blue-500/10 dark:text-blue-300">
                {{ number_format($ingredients->count(), 0, ',', '.') }} bahan tersedia
            </span>
        </div>

        <div class="p-3.5 sm:p-5 md:p-0 bg-slate-50/70 dark:bg-slate-950/40 md:bg-transparent md:dark:bg-transparent">
            @if($ingredients->isEmpty())
                <div class="flex flex-col items-center justify-center text-center py-12">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 mb-4 border border-slate-200 dark:border-slate-700">
                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <h3 class="text-[15px] font-bold text-slate-800 dark:text-slate-200 mb-2">Bahan Tidak Ditemukan</h3>
                    <p class="text-[13px] font-medium text-slate-500 dark:text-slate-400 max-w-md">Tidak ada bahan yang cocok dengan kata kunci pencarian atau filter yang Anda gunakan.</p>
                </div>
            @else
                <!-- ALPINE ROOT FOR RESPONSIVE FORM SUBMISSION -->
                <div x-data="{ isMobile: window.innerWidth < 768 }" @resize.window="isMobile = window.innerWidth < 768">

                    <!-- ========================================== -->
                    <!-- DESKTOP VIEW (MINIMALIST CLEAN TABLE)      -->
                    <!-- ========================================== -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full min-w-[1080px] border-collapse text-left">
                            <thead>
                                <tr class="text-[10px] font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b-2 border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                                    <th class="px-6 py-4 whitespace-nowrap">Bahan & Saldo Stok</th>
                                    <th class="w-44 px-4 py-4 whitespace-nowrap">Sisa Kemarin</th>
                                    <th class="w-64 px-4 py-4 whitespace-nowrap">Stok Awal Hari Ini</th>
                                    <th class="w-44 px-4 py-4 whitespace-nowrap">Langkah (+/-)</th>
                                    <th class="min-w-[220px] px-6 py-4 whitespace-nowrap">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                @foreach($ingredients as $ingredient)
                                    @php
                                        $displayUnit = strtolower((string) $ingredient->display_unit);
                                        $transferInputUnit = strtolower((string) $ingredient->transfer_input_unit);
                                        $transferUnitOptions = $ingredient->transfer_unit_options ?? [$transferInputUnit => $transferInputUnit];
                                        $packSize = max(1, (int) ($ingredient->pack_size ?? 1));
                                        $stockAvailable = (float) $ingredient->transfer_stock_value;
                                        $defaultUnit = $displayUnit === 'pcs' && $stockAvailable >= $packSize ? 'pack' : $transferInputUnit;
                                        $hasCarryForward = (bool) $ingredient->has_carry_forward;
                                        $carryForwardValue = rtrim(rtrim(number_format((float) $ingredient->carry_forward_value, 2, '.', ''), '0'), '.');
                                        $sessionOpeningValue = rtrim(rtrim(number_format((float) $ingredient->session_opening_value, 2, '.', ''), '0'), '.');
                                        $transferredValue = rtrim(rtrim(number_format((float) $ingredient->transferred_to_session_value, 2, '.', ''), '0'), '.');
                                        $initialOpeningQuantity = (string) old("transfers.{$ingredient->id}.opening_quantity", $sessionOpeningValue);
                                    @endphp

                                    <tr x-data="{
                                            unit: @js($defaultUnit),
                                            openingQty: @js($initialOpeningQuantity),
                                            displayUnit: @js($displayUnit),
                                            packSize: @js($packSize),
                                            carryForward: @js((float) $ingredient->carry_forward_value),
                                            stepSize() {
                                                if (this.displayUnit === 'pcs' && this.unit === 'pack') return this.packSize;
                                                if (this.displayUnit === 'kg' && this.unit === 'g') return 0.001;
                                                if (this.displayUnit === 'l' && this.unit === 'ml') return 0.001;
                                                return 1;
                                            },
                                            changeOpening(direction) {
                                                const next = Math.max(0, (parseFloat(this.openingQty) || 0) + (direction * this.stepSize()));
                                                this.openingQty = Number(next.toFixed(3));
                                            },
                                            difference() {
                                                return Number((Math.max(0, parseFloat(this.openingQty) || 0) - this.carryForward).toFixed(2));
                                            }
                                        }" class="group transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-800/30">

                                        {{-- Bahan & Saldo Stok --}}
                                        <td class="px-6 py-4 align-top">
                                            <p class="font-extrabold text-[15px] tracking-tight text-slate-900 dark:text-white transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ $ingredient->name }}</p>
                                            <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[12px]">
                                                <span class="font-medium text-slate-500 dark:text-slate-400">
                                                    Gudang: <strong class="font-bold text-slate-800 dark:text-slate-200">{{ number_format($stockAvailable, 2, ',', '.') }} {{ strtoupper($ingredient->transfer_stock_unit) }}</strong>
                                                </span>
                                                <span class="text-slate-300 dark:text-slate-700">/</span>
                                                <span class="font-medium text-slate-500 dark:text-slate-400">
                                                    Saldo Sesi: <strong class="font-bold text-emerald-600 dark:text-emerald-400">{{ $sessionOpeningValue }} {{ strtoupper($displayUnit) }}</strong>
                                                </span>
                                                @if((float) $ingredient->transferred_to_session_value > 0)
                                                    <span class="text-slate-300 dark:text-slate-700">/</span>
                                                    <span class="font-medium text-slate-500 dark:text-slate-400">
                                                        Sudah tambah: <strong class="font-bold text-blue-600 dark:text-blue-400">{{ $transferredValue }} {{ strtoupper($displayUnit) }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- Sisa Kemarin --}}
                                        <td class="px-4 py-4 align-top">
                                            <div class="inline-flex flex-col items-start gap-1">
                                                <span class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-100/70 px-3 text-xs font-extrabold text-slate-700 shadow-inner dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-300">
                                                    <i class="fa-solid fa-clock-rotate-left text-[11px] text-slate-400 dark:text-slate-500"></i>
                                                    <span>{{ $carryForwardValue }} <span class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">{{ $displayUnit }}</span></span>
                                                </span>
                                                <span class="pl-1 text-[10px] font-semibold text-slate-400 dark:text-slate-500">Terbawa otomatis</span>
                                            </div>
                                        </td>

                                        {{-- Stok Awal Hari Ini --}}
                                        <td class="px-4 py-4 align-top">
                                            <div class="w-full max-w-[230px]">
                                                <div class="flex h-10 items-center overflow-hidden rounded-xl border border-slate-200/80 bg-slate-100/70 p-1 shadow-inner transition focus-within:border-slate-400 focus-within:bg-white dark:border-slate-800 dark:bg-slate-950/80 dark:focus-within:border-slate-700 dark:focus-within:bg-slate-900">
                                                    <button type="button" x-bind:disabled="isMobile" @click="changeOpening(-1)" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-base font-black text-slate-600 shadow-2xs transition hover:bg-slate-50 hover:text-rose-600 active:scale-95 disabled:opacity-40 dark:border-slate-700/60 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">&minus;</button>
                                                    <div class="flex min-w-0 flex-1 items-center justify-center px-1.5">
                                                        <input type="number" x-bind:disabled="isMobile" x-model="openingQty" name="transfers[{{ $ingredient->id }}][opening_quantity]" min="0" step="{{ $displayUnit === 'pcs' ? '1' : '0.01' }}" class="w-full border-0 bg-transparent p-0 text-center text-[15px] font-black tabular-nums text-slate-900 outline-none focus:ring-0 dark:text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                                        <span class="select-none pl-1 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider dark:text-slate-500">{{ strtoupper($displayUnit) }}</span>
                                                    </div>
                                                    <button type="button" x-bind:disabled="isMobile" @click="changeOpening(1)" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/80 bg-white text-base font-black text-slate-600 shadow-2xs transition hover:bg-slate-50 hover:text-blue-600 active:scale-95 disabled:opacity-40 dark:border-slate-700/60 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">+</button>
                                                </div>

                                                <div class="mt-1.5 min-h-[18px] text-[11px]">
                                                    <div x-show="difference() > 0" class="flex items-center gap-1 font-semibold text-blue-600 dark:text-blue-400">
                                                        <i class="fa-solid fa-arrow-turn-up text-[10px]"></i> Tambahan: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="difference() + ' {{ strtoupper($displayUnit) }}'"></span>
                                                    </div>
                                                    <div x-show="difference() === 0" class="flex items-center gap-1 font-medium text-slate-400 dark:text-slate-500">
                                                        <i class="fa-solid fa-check text-[10px] text-emerald-500 dark:text-emerald-400"></i> Sesuai sisa kemarin
                                                    </div>
                                                    <div x-show="difference() < 0" class="flex items-center gap-1 font-semibold text-rose-600 dark:text-rose-400">
                                                        <i class="fa-solid fa-arrow-turn-down text-[10px]"></i> Selisih fisik: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="Math.abs(difference()) + ' {{ strtoupper($displayUnit) }}'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Langkah (+/-) --}}
                                        <td class="px-4 py-4 align-top">
                                            @if($displayUnit === 'pcs')
                                                <div class="w-40">
                                                    <div class="flex h-10 items-center rounded-xl border border-slate-200/80 bg-slate-100/70 p-1 shadow-inner dark:border-slate-800 dark:bg-slate-950/80">
                                                        <label class="relative flex-1 cursor-pointer">
                                                            <input type="radio" x-bind:disabled="isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pack" class="peer sr-only" x-model="unit">
                                                            <div :class="unit === 'pack' ? 'bg-white text-slate-900 shadow-xs dark:!bg-slate-800 dark:!text-white dark:border dark:border-slate-700/80 font-black' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 font-semibold'" class="flex h-8 items-center justify-center gap-1 rounded-lg text-[11px] uppercase tracking-wider transition peer-checked:bg-white peer-checked:text-slate-900 dark:peer-checked:bg-slate-700 dark:peer-checked:text-white">
                                                                <i class="fa-solid fa-box text-[10px]"></i> Pack
                                                            </div>
                                                        </label>
                                                        <label class="relative flex-1 cursor-pointer">
                                                            <input type="radio" x-bind:disabled="isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pcs" class="peer sr-only" x-model="unit">
                                                            <div :class="unit === 'pcs' ? 'bg-white text-slate-900 shadow-xs dark:!bg-slate-800 dark:!text-white dark:border dark:border-slate-700/80 font-black' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 font-semibold'" class="flex h-8 items-center justify-center gap-1 rounded-lg text-[11px] uppercase tracking-wider transition peer-checked:bg-white peer-checked:text-slate-900 dark:peer-checked:bg-slate-700 dark:peer-checked:text-white">
                                                                <i class="fa-solid fa-cube text-[10px]"></i> Pcs
                                                            </div>
                                                        </label>
                                                    </div>
                                                    @if($packSize > 1)
                                                        <p class="mt-1.5 flex items-center justify-center gap-1 text-[10px] font-bold text-slate-400 dark:text-slate-500">
                                                            <span>1 PACK = {{ $packSize }} PCS</span>
                                                        </p>
                                                    @endif
                                                </div>
                                            @elseif(count($transferUnitOptions) > 1)
                                                <select x-bind:disabled="isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" x-model="unit" class="h-10 w-40 rounded-xl border border-slate-200/80 bg-white px-3 text-xs font-bold text-slate-700 outline-none focus:border-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-700">
                                                    @foreach($transferUnitOptions as $unitValue => $unitLabel)
                                                        <option value="{{ $unitValue }}" {{ $transferInputUnit === $unitValue ? 'selected' : '' }}>
                                                            {{ $unitLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="hidden" x-bind:disabled="isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="{{ $transferInputUnit }}">
                                                <span class="inline-flex h-10 w-40 items-center justify-center gap-1.5 rounded-xl border border-slate-200/80 bg-slate-100/70 px-3 text-xs font-bold uppercase tracking-wider text-slate-600 shadow-inner dark:border-slate-800 dark:bg-slate-950/80 dark:text-slate-300">
                                                    <i class="fa-solid fa-tag text-[10px] text-slate-400"></i> {{ strtoupper($transferInputUnit) }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Catatan --}}
                                        <td class="px-6 py-4 align-top">
                                            <input
                                                type="text"
                                                x-bind:disabled="isMobile"
                                                name="transfers[{{ $ingredient->id }}][note]"
                                                value="{{ old("transfers.{$ingredient->id}.note") }}"
                                                placeholder="Tulis catatan opsional..."
                                                class="h-10 w-full rounded-xl border border-slate-200/80 bg-slate-50/40 px-3.5 text-[13px] font-medium text-slate-800 placeholder-slate-400 transition hover:border-slate-300 focus:border-slate-400 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:hover:border-slate-700 dark:focus:border-slate-600 dark:focus:bg-slate-800/80"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- ========================================== -->
                    <!-- MOBILE VIEW (MODERN SPLIT-METRIC CARDS)    -->
                    <!-- ========================================== -->
                    <div class="space-y-5 px-0.5 md:hidden">
                        @foreach($ingredients as $ingredient)
                            @php
                                $displayUnit = strtolower((string) $ingredient->display_unit);
                                $transferInputUnit = strtolower((string) $ingredient->transfer_input_unit);
                                $transferUnitOptions = $ingredient->transfer_unit_options ?? [$transferInputUnit => $transferInputUnit];
                                $packSize = max(1, (int) ($ingredient->pack_size ?? 1));
                                $stockAvailable = (float) $ingredient->transfer_stock_value;
                                $defaultUnit = $displayUnit === 'pcs' && $stockAvailable >= $packSize ? 'pack' : $transferInputUnit;
                                $hasCarryForward = (bool) $ingredient->has_carry_forward;
                                $carryForwardValue = rtrim(rtrim(number_format((float) $ingredient->carry_forward_value, 2, '.', ''), '0'), '.');
                                $sessionOpeningValue = rtrim(rtrim(number_format((float) $ingredient->session_opening_value, 2, '.', ''), '0'), '.');
                                $transferredValue = rtrim(rtrim(number_format((float) $ingredient->transferred_to_session_value, 2, '.', ''), '0'), '.');
                                $initialOpeningQuantity = (string) old("transfers.{$ingredient->id}.opening_quantity", $sessionOpeningValue);
                            @endphp

                            <div x-data="{
                                    unit: @js($defaultUnit),
                                    openingQty: @js($initialOpeningQuantity),
                                    displayUnit: @js($displayUnit),
                                    packSize: @js($packSize),
                                    carryForward: @js((float) $ingredient->carry_forward_value),
                                    stepSize() {
                                        if (this.displayUnit === 'pcs' && this.unit === 'pack') return this.packSize;
                                        if (this.displayUnit === 'kg' && this.unit === 'g') return 0.001;
                                        if (this.displayUnit === 'l' && this.unit === 'ml') return 0.001;
                                        return 1;
                                    },
                                    changeOpening(direction) {
                                        const next = Math.max(0, (parseFloat(this.openingQty) || 0) + (direction * this.stepSize()));
                                        this.openingQty = Number(next.toFixed(3));
                                    },
                                    difference() {
                                        return Number((Math.max(0, parseFloat(this.openingQty) || 0) - this.carryForward).toFixed(2));
                                    }
                                }" class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-sm transition duration-200 dark:border-slate-800 dark:bg-slate-900">

                                {{-- Header Kartu & Status Sisa Kemarin (Ditata Vertikal agar Lega) --}}
                                <div class="flex flex-col gap-2.5 border-b border-slate-100 dark:border-slate-800/80 pb-4">
                                    <h3 class="font-extrabold text-[18px] sm:text-[19px] text-slate-900 dark:text-white tracking-tight leading-snug">{{ $ingredient->name }}</h3>
                                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 dark:bg-slate-800/80 px-3 py-1.5 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700/60 shadow-2xs font-extrabold">
                                            <i class="fa-solid fa-clock-rotate-left text-[11px] text-slate-500 dark:text-slate-400"></i>
                                            Sisa Kemarin: {{ $carryForwardValue }} {{ strtoupper($displayUnit) }}
                                        </span>
                                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 italic">(Terbawa otomatis)</span>
                                    </div>
                                </div>

                                {{-- Metric Grid 3-Kolom yang Lega & Mudah Dibaca --}}
                                <div class="mt-4 grid grid-cols-3 gap-2.5 sm:gap-3">
                                    <div class="flex flex-col items-center justify-center rounded-xl border border-slate-200/80 bg-slate-50/80 p-3 text-center shadow-2xs dark:border-slate-800/80 dark:bg-slate-800/40">
                                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Gudang</span>
                                        <strong class="mt-1.5 text-[15px] sm:text-base font-black text-slate-800 dark:text-slate-100 tabular-nums">{{ number_format($stockAvailable, 2, ',', '.') }} <span class="text-[11px] font-bold text-slate-400 uppercase ml-0.5">{{ $ingredient->transfer_stock_unit }}</span></strong>
                                    </div>
                                    <div class="flex flex-col items-center justify-center rounded-xl border border-emerald-200/60 bg-emerald-50/50 p-3 text-center shadow-2xs dark:border-emerald-900/40 dark:bg-emerald-950/20">
                                        <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600/90 dark:text-emerald-400/90">Saldo Sesi</span>
                                        <strong class="mt-1.5 text-[15px] sm:text-base font-black text-emerald-700 dark:text-emerald-300 tabular-nums">{{ $sessionOpeningValue }} <span class="text-[11px] font-bold text-emerald-600/70 dark:text-emerald-400 uppercase ml-0.5">{{ $displayUnit }}</span></strong>
                                    </div>
                                    <div class="flex flex-col items-center justify-center rounded-xl border border-blue-200/60 bg-blue-50/50 p-3 text-center shadow-2xs dark:border-blue-900/40 dark:bg-blue-950/20">
                                        <span class="text-[11px] font-black uppercase tracking-wider text-blue-600/90 dark:text-blue-400/90">Sudah Tambah</span>
                                        <strong class="mt-1.5 text-[15px] sm:text-base font-black text-blue-700 dark:text-blue-300 tabular-nums">{{ $transferredValue }} <span class="text-[11px] font-bold text-blue-600/70 dark:text-blue-400 uppercase ml-0.5">{{ $displayUnit }}</span></strong>
                                    </div>
                                </div>

                                {{-- Kontrol Interaktif Minimalis & NYAMAN --}}
                                <div class="mt-5 space-y-4">
                                    {{-- 1. Stok Awal Hari Ini --}}
                                    <div class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4 sm:p-5 dark:border-slate-800 dark:bg-slate-900/50">
                                        <div class="mb-3 flex items-center justify-between">
                                            <label class="flex items-center gap-2 text-[13px] font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                                <i class="fa-solid fa-calculator text-blue-500 dark:text-blue-400"></i> Stok Awal Hari Ini
                                            </label>
                                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Sisa: <strong class="font-extrabold text-slate-700 dark:text-slate-200">{{ $carryForwardValue }} {{ strtoupper($displayUnit) }}</strong></span>
                                        </div>

                                        <div class="flex h-[56px] w-full items-center justify-between rounded-xl border border-slate-300/80 bg-slate-100/90 p-1.5 shadow-inner dark:border-slate-700/80 dark:bg-slate-950/90">
                                            <button type="button" x-bind:disabled="!isMobile" @click="changeOpening(-1)" class="flex h-11 w-14 sm:w-16 shrink-0 items-center justify-center rounded-lg border border-slate-300/80 bg-white text-xl font-black text-slate-700 shadow-sm transition active:scale-95 hover:bg-slate-50 dark:border-slate-600/80 dark:bg-slate-800 dark:text-slate-200 dark:active:bg-slate-700">&minus;</button>
                                            <div class="flex min-w-0 flex-1 items-center justify-center px-3">
                                                <input type="number" x-bind:disabled="!isMobile" x-model="openingQty" name="transfers[{{ $ingredient->id }}][opening_quantity]" min="0" step="{{ $displayUnit === 'pcs' ? '1' : '0.01' }}" class="w-full border-0 bg-transparent p-0 text-center text-[22px] font-black tabular-nums text-slate-900 outline-none focus:ring-0 dark:text-white [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                                                <span class="select-none pl-1.5 text-[13px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-400">{{ strtoupper($displayUnit) }}</span>
                                            </div>
                                            <button type="button" x-bind:disabled="!isMobile" @click="changeOpening(1)" class="flex h-11 w-14 sm:w-16 shrink-0 items-center justify-center rounded-lg border border-slate-300/80 bg-white text-xl font-black text-slate-700 shadow-sm transition active:scale-95 hover:bg-slate-50 dark:border-slate-600/80 dark:bg-slate-800 dark:text-slate-200 dark:active:bg-slate-700">+</button>
                                        </div>

                                        {{-- Banner Indikator Selisih --}}
                                        <div class="mt-3.5">
                                            <div x-show="difference() > 0" class="flex items-center justify-between rounded-xl border border-blue-200/80 bg-blue-50/90 px-4 py-3 text-[13px] font-bold text-blue-900 shadow-2xs dark:border-blue-800/60 dark:bg-blue-950/50 dark:text-blue-200">
                                                <span class="flex items-center gap-2 font-extrabold"><i class="fa-solid fa-arrow-turn-up text-sm text-blue-600 dark:text-blue-400"></i> Tambahan Gudang</span>
                                                <span class="font-black bg-blue-100/90 dark:bg-blue-900/80 px-2.5 py-1 rounded-md text-blue-900 dark:text-blue-100 text-sm" x-text="difference() + ' {{ strtoupper($displayUnit) }}'"></span>
                                            </div>
                                            <div x-show="difference() === 0" class="flex items-center justify-between rounded-xl border border-emerald-200/80 bg-emerald-50/60 px-4 py-3 text-[13px] font-bold text-emerald-900 shadow-2xs dark:border-emerald-800/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                <span class="flex items-center gap-2 font-extrabold"><i class="fa-solid fa-check text-sm text-emerald-600 dark:text-emerald-400"></i> Status Stok</span>
                                                <span class="font-bold text-emerald-800 dark:text-emerald-200">Sesuai Sisa Kemarin</span>
                                            </div>
                                            <div x-show="difference() < 0" class="flex items-center justify-between rounded-xl border border-rose-200/80 bg-rose-50/90 px-4 py-3 text-[13px] font-bold text-rose-900 shadow-2xs dark:border-rose-800/60 dark:bg-rose-950/50 dark:text-rose-200">
                                                <span class="flex items-center gap-2 font-extrabold"><i class="fa-solid fa-arrow-turn-down text-sm text-rose-600 dark:text-rose-400"></i> Selisih Fisik (Kurang)</span>
                                                <span class="font-black bg-rose-100/90 dark:bg-rose-900/80 px-2.5 py-1 rounded-md text-rose-900 dark:text-rose-100 text-sm" x-text="Math.abs(difference()) + ' {{ strtoupper($displayUnit) }}'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 2. Langkah & Catatan (Ditata lebih besar dan proporsional) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                        {{-- Langkah (+/-) --}}
                                        <div class="flex flex-col justify-between">
                                            <label class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                                <i class="fa-solid fa-sliders text-slate-400 dark:text-slate-500"></i> Satuan Langkah (+/-)
                                            </label>
                                            @if($displayUnit === 'pcs')
                                                <div class="flex h-12 w-full items-center rounded-xl border border-slate-300/80 bg-slate-100/90 p-1.5 shadow-inner dark:border-slate-700/80 dark:bg-slate-950/90">
                                                    <label class="relative flex-1 cursor-pointer h-full">
                                                        <input type="radio" x-bind:disabled="!isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pack" class="peer sr-only" x-model="unit">
                                                        <div :class="unit === 'pack' ? 'bg-white text-slate-950 shadow-sm dark:!bg-slate-800 dark:!text-white dark:border dark:border-slate-700/80 font-black' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 font-bold'" class="flex h-full items-center justify-center gap-1.5 rounded-lg text-[13px] uppercase tracking-wider transition peer-checked:bg-white peer-checked:text-slate-900 dark:peer-checked:bg-slate-700 dark:peer-checked:text-white">
                                                            <i class="fa-solid fa-box text-xs"></i> Pack
                                                        </div>
                                                    </label>
                                                    <label class="relative flex-1 cursor-pointer h-full">
                                                        <input type="radio" x-bind:disabled="!isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pcs" class="peer sr-only" x-model="unit">
                                                        <div :class="unit === 'pcs' ? 'bg-white text-slate-950 shadow-sm dark:!bg-slate-800 dark:!text-white dark:border dark:border-slate-700/80 font-black' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 font-bold'" class="flex h-full items-center justify-center gap-1.5 rounded-lg text-[13px] uppercase tracking-wider transition peer-checked:bg-white peer-checked:text-slate-900 dark:peer-checked:bg-slate-700 dark:peer-checked:text-white">
                                                            <i class="fa-solid fa-cube text-xs"></i> Pcs
                                                        </div>
                                                    </label>
                                                </div>
                                                @if($packSize > 1)
                                                    <p class="mt-2 flex items-center justify-start gap-1.5 text-xs font-extrabold text-slate-500 dark:text-slate-400">
                                                        <i class="fa-solid fa-circle-info text-blue-500 dark:text-blue-400 text-[11px]"></i>
                                                        <span>1 Pack = {{ $packSize }} Pcs</span>
                                                    </p>
                                                @endif
                                            @elseif(count($transferUnitOptions) > 1)
                                                <select x-bind:disabled="!isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" x-model="unit" class="h-12 w-full rounded-xl border border-slate-300/80 bg-white px-3.5 text-sm font-bold text-slate-800 outline-none focus:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-slate-600 shadow-2xs">
                                                    @foreach($transferUnitOptions as $unitValue => $unitLabel)
                                                        <option value="{{ $unitValue }}" {{ $transferInputUnit === $unitValue ? 'selected' : '' }}>
                                                            {{ $unitLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="hidden" x-bind:disabled="!isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="{{ $transferInputUnit }}">
                                                <span class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-slate-300/80 bg-slate-100/80 px-4 text-sm font-black uppercase tracking-wider text-slate-700 shadow-inner dark:border-slate-800 dark:bg-slate-950/80 dark:text-slate-300">
                                                    <i class="fa-solid fa-tag text-slate-400"></i> {{ strtoupper($transferInputUnit) }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Catatan --}}
                                        <div class="flex flex-col justify-between">
                                            <label class="mb-2 block text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                                <i class="fa-solid fa-note-sticky text-slate-400 dark:text-slate-500"></i> Catatan <span class="normal-case font-semibold text-slate-400">(opsional)</span>
                                            </label>
                                            <input
                                                type="text"
                                                x-bind:disabled="!isMobile"
                                                name="transfers[{{ $ingredient->id }}][note]"
                                                value="{{ old("transfers.{$ingredient->id}.note") }}"
                                                placeholder="Tulis catatan opsional..."
                                                class="h-12 w-full rounded-xl border border-slate-300/80 bg-white px-4 text-[14px] font-semibold text-slate-800 placeholder-slate-400 shadow-2xs transition hover:border-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:hover:border-slate-600 dark:focus:border-blue-400 dark:focus:bg-slate-900"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ACTION FOOTER --}}
        <div class="sticky bottom-0 z-20 flex flex-col-reverse items-center justify-end gap-3 border-t border-slate-200/80 bg-white/95 px-6 py-4 backdrop-blur-md transition-all sm:flex-row dark:border-slate-800 dark:bg-slate-900/95 shadow-xl">
            <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
               class="inline-flex h-12 w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-7 text-[14px] font-bold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50 active:scale-95 sm:w-auto sm:min-w-32 lg:min-w-36 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                <i class="fa-solid fa-arrow-left text-slate-400 dark:text-slate-400"></i>
                Batal
            </a>
            <button type="submit" class="inline-flex h-12 w-full shrink-0 min-w-[220px] items-center justify-center gap-2 rounded-xl bg-blue-600 px-8 text-[14px] font-extrabold text-white shadow-md shadow-blue-500/25 transition hover:bg-blue-700 active:scale-95 sm:w-auto">
                <i class="fa-solid fa-check text-base"></i>
                Simpan Saldo Awal
            </button>
        </div>
    </form>

</div>

<style>
/* Mencegah tombol radio aktif berwarna putih saat dalam dark mode */
.dark input[type="radio"]:checked + div,
.dark input[type="radio"]:checked + span {
    background-color: #1e293b !important;
    color: #f8fafc !important;
    border: 1px solid #334155 !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.5) !important;
}
</style>
@endsection
