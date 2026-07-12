@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Transfer Stok Harian')
@section('disableGlobalAlerts', 'true')

@push('styles')
<style>
    .dark .transfer-stock-page input[name^="transfers["],
    .dark .transfer-stock-page select[name^="transfers["] {
        background-color: #0f172a !important;
        border-color: #475569 !important;
        color: #f8fafc !important;
        color-scheme: dark;
    }

    .dark .transfer-stock-page input[name^="transfers["]::placeholder {
        color: #94a3b8 !important;
        opacity: 1;
    }

    .dark .transfer-stock-page .transfer-unit-toggle {
        background-color: #0f172a !important;
        border-color: #475569 !important;
    }

    .dark .transfer-stock-page .transfer-unit-option {
        color: #94a3b8 !important;
    }

    .dark .transfer-stock-page input[type="radio"]:checked + .transfer-unit-option {
        background-color: #2563eb !important;
        color: #ffffff !important;
    }

    .transfer-stock-page .transfer-filter-form {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .transfer-stock-page .transfer-filter-category {
        width: 100%;
    }

    .transfer-stock-page .transfer-filter-search {
        flex: 1 1 auto;
        min-width: 0;
    }

    @media (min-width: 640px) {
        .transfer-stock-page .transfer-filter-form {
            flex-direction: row;
            align-items: center;
        }

        .transfer-stock-page .transfer-filter-category {
            flex: 0 0 176px;
            width: 176px;
        }
    }
</style>
@endpush

@section('content')
<div class="transfer-stock-page w-full space-y-5 overflow-x-hidden pb-10">
    {{-- ================= HEADER & SESSION SUMMARY ================= --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <nav class="mb-3 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 sm:text-[11px]">
                <a href="{{ route('admin.panel') }}" class="whitespace-nowrap transition-colors hover:text-blue-600 dark:hover:text-blue-400">Beranda</a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}" class="whitespace-nowrap transition-colors hover:text-blue-600 dark:hover:text-blue-400">Stok Harian</a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">Transfer Bahan</span>
            </nav>

            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white md:text-3xl">
                Input Bahan Dibawa
            </h1>
            <p class="mt-2 max-w-3xl text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                Transfer stok dari gudang utama ke sesi kasir. Isi hanya bahan yang benar-benar dibawa untuk operasional hari ini.
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-2">
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
        </div>

        <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
           class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 sm:w-auto dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Sesi
        </a>
    </div>

    {{-- ================= ALERTS ================= --}}
    @include('partials.flash_alerts', ['class' => 'w-full space-y-2'])

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

        <select name="category_id" onchange="this.form.submit()" class="transfer-filter-category h-11 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 text-[12px] font-bold text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
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
                    Daftar Bahan Gudang
                </h2>
                <p class="mt-1 text-[12px] font-medium text-slate-500 dark:text-slate-400">Isi hanya bahan yang dibawa. Baris kosong tidak akan ikut ditransfer.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-600 dark:border-blue-900/70 dark:bg-blue-500/10 dark:text-blue-300">
                {{ number_format($ingredients->total(), 0, ',', '.') }} bahan tersedia
            </span>
        </div>

        <div class="bg-slate-50/40 p-4 dark:bg-slate-950/20 sm:p-5 md:overflow-visible">
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
                    <!-- DESKTOP VIEW (PURE TABLE)                  -->
                    <!-- ========================================== -->
                    <div class="hidden overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 md:block">
                        <table class="w-full border-collapse text-left">
                            <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-800/50">
                                <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    <th class="px-4 py-3.5 whitespace-nowrap">Bahan & Jumlah Dibawa</th>
                                    <th class="px-4 py-3.5 whitespace-nowrap w-48">Satuan Transfer</th>
                                    <th class="px-4 py-3.5 whitespace-nowrap min-w-[220px]">Catatan (Opsional)</th>
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
                                        $defaultUnit = $displayUnit === 'pcs' ? 'pack' : $transferInputUnit;
                                    @endphp

                                    <tr x-data="{ unit: '{{ $defaultUnit }}', qty: '' }" class="group transition-colors hover:bg-slate-50/80 focus-within:bg-blue-50/40 dark:hover:bg-slate-800/40 dark:focus-within:bg-blue-950/20">
                                        {{-- Info Bahan --}}
                                        <td class="px-4 py-4 align-top">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="min-w-0">
                                                    <p class="mb-1.5 text-[13px] font-black leading-tight text-slate-800 dark:text-slate-100">{{ $ingredient->name }}</p>
                                                    <div class="flex items-center gap-1">
                                                        <span class="inline-flex h-6 items-center rounded-full border border-blue-200 bg-blue-50 px-2 text-[10px] font-bold text-blue-600 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300">
                                                            Stok <span class="ml-1 tabular-nums">{{ number_format($stockAvailable, 2, ',', '.') }}</span> <span class="ml-0.5 uppercase tracking-wider">{{ $ingredient->transfer_stock_unit }}</span>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="shrink-0">
                                                    <div class="flex h-9 items-center overflow-hidden rounded-xl border border-slate-300 bg-slate-100 shadow-sm dark:border-slate-700 dark:bg-slate-950">
                                                        <button type="button"
                                                                x-bind:disabled="isMobile"
                                                                @click="qty = Math.max(0, (parseFloat(qty) || 0) - 1)"
                                                                class="flex h-full w-9 items-center justify-center bg-slate-50 text-lg font-black text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">
                                                            -
                                                        </button>
                                                        <input
                                                            type="number"
                                                            x-bind:disabled="isMobile"
                                                            x-model="qty"
                                                            name="transfers[{{ $ingredient->id }}][quantity]"
                                                            min="0"
                                                            step="0.01"
                                                            placeholder="0"
                                                            class="h-full w-16 border-0 border-x border-slate-300 bg-white px-2 text-center text-[13px] font-black tabular-nums text-slate-900 outline-none focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                        >
                                                        <button type="button"
                                                                x-bind:disabled="isMobile"
                                                                @click="qty = (parseFloat(qty) || 0) + 1"
                                                                class="flex h-full w-9 items-center justify-center bg-slate-50 text-lg font-black text-blue-600 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-800 dark:text-blue-300 dark:hover:bg-blue-950/50">
                                                            +
                                                        </button>
                                                    </div>
                                                    <p class="mt-1 text-center text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500" x-text="unit"></p>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Satuan Input --}}
                                        <td class="px-4 py-4 align-top">
                                            @if($displayUnit === 'pcs')
                                                <div class="w-full">
                                                    <div class="transfer-unit-toggle flex w-full rounded-lg border border-slate-200 bg-slate-100 p-1 dark:border-slate-700 dark:bg-slate-950/70">
                                                        <label class="cursor-pointer flex-1 relative">
                                                            <input type="radio" x-bind:disabled="isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pack" class="peer sr-only" x-model="unit">
                                                            <div class="transfer-unit-option flex items-center justify-center rounded-md py-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500 transition-all duration-300 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-sm dark:text-slate-400">Pack</div>
                                                        </label>
                                                        <label class="cursor-pointer flex-1 relative">
                                                            <input type="radio" x-bind:disabled="isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pcs" class="peer sr-only" x-model="unit">
                                                            <div class="transfer-unit-option flex items-center justify-center rounded-md py-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500 transition-all duration-300 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-sm dark:text-slate-400">Pcs</div>
                                                        </label>
                                                    </div>
                                                    @if($packSize > 1)
                                                        <div class="text-center mt-1">
                                                            <p class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 tracking-wider">
                                                                <i class="fa-solid fa-box-open mr-1 opacity-70"></i> 1 PACK = {{ $packSize }} PCS
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif(count($transferUnitOptions) > 1)
                                                <select x-bind:disabled="isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" x-model="unit" class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50 px-2 text-[11px] font-semibold text-slate-700 outline-none transition-shadow focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300">
                                                    @foreach($transferUnitOptions as $unitValue => $unitLabel)
                                                        <option value="{{ $unitValue }}" {{ $transferInputUnit === $unitValue ? 'selected' : '' }}>
                                                            {{ $unitLabel }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="hidden" x-bind:disabled="isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="{{ $transferInputUnit }}">
                                                <span class="inline-flex h-9 w-full items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-[11px] font-bold uppercase tracking-widest text-slate-600 dark:border-slate-800 dark:bg-slate-950/70 dark:text-slate-300">
                                                    {{ strtoupper($transferInputUnit) }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Input Catatan --}}
                                        <td class="px-4 py-4 align-top">
                                            <input
                                                type="text"
                                                x-bind:disabled="isMobile"
                                                name="transfers[{{ $ingredient->id }}][note]"
                                                placeholder="Catatan..."
                                                class="h-9 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-[12px] text-slate-700 transition-all focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-300 dark:focus:bg-slate-900"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- ========================================== -->
                    <!-- MOBILE VIEW (CARDS)                        -->
                    <!-- ========================================== -->
                    <div class="md:hidden space-y-4">
                        @foreach($ingredients as $ingredient)
                            @php
                                $displayUnit = strtolower((string) $ingredient->display_unit);
                                $transferInputUnit = strtolower((string) $ingredient->transfer_input_unit);
                                $transferUnitOptions = $ingredient->transfer_unit_options ?? [$transferInputUnit => $transferInputUnit];
                                $packSize = max(1, (int) ($ingredient->pack_size ?? 1));
                                $stockAvailable = (float) $ingredient->transfer_stock_value;
                                $defaultUnit = $displayUnit === 'pcs' ? 'pack' : $transferInputUnit;
                            @endphp

                            <div x-data="{ unit: '{{ $defaultUnit }}', qty: '' }" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900">
                                {{-- Header Card --}}
                                <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/40">
                                    <div class="flex flex-col gap-3">
                                        <div class="min-w-0">
                                            <p class="mb-1.5 break-words text-[14px] font-black leading-tight text-slate-800 dark:text-slate-100">{{ $ingredient->name }}</p>
                                            <span class="inline-flex h-6 items-center rounded-full border border-blue-200 bg-blue-50 px-2 text-[10px] font-bold text-blue-600 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300">
                                                Stok <span class="ml-1 tabular-nums">{{ number_format($stockAvailable, 2, ',', '.') }}</span> <span class="ml-0.5 uppercase tracking-wider">{{ $ingredient->transfer_stock_unit }}</span>
                                            </span>
                                        </div>

                                        <div class="w-full">
                                            <div class="flex h-10 w-full items-center overflow-hidden rounded-xl border border-slate-300 bg-slate-100 shadow-sm dark:border-slate-700 dark:bg-slate-950">
                                                <button type="button"
                                                        x-bind:disabled="!isMobile"
                                                        @click="qty = Math.max(0, (parseFloat(qty) || 0) - 1)"
                                                        class="flex h-full w-11 items-center justify-center bg-slate-50 text-lg font-black text-slate-500 transition hover:bg-slate-200 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">
                                                    -
                                                </button>
                                                <input
                                                    type="number"
                                                    x-bind:disabled="!isMobile"
                                                    x-model="qty"
                                                    name="transfers[{{ $ingredient->id }}][quantity]"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="0"
                                                    class="h-full min-w-0 flex-1 border-0 border-x border-slate-300 bg-white px-2 text-center text-[14px] font-black tabular-nums text-slate-900 outline-none focus:ring-0 dark:border-slate-700 dark:bg-slate-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                >
                                                <button type="button"
                                                        x-bind:disabled="!isMobile"
                                                        @click="qty = (parseFloat(qty) || 0) + 1"
                                                        class="flex h-full w-11 items-center justify-center bg-slate-50 text-lg font-black text-blue-600 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-slate-800 dark:text-blue-300 dark:hover:bg-blue-950/50">
                                                    +
                                                </button>
                                            </div>
                                            <p class="mt-1 text-center text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500" x-text="unit"></p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Body Card --}}
                                <div class="p-4 space-y-4">
                                    {{-- Satuan --}}
                                    <div>
                                        <div class="text-[10px] font-bold uppercase text-slate-400 dark:text-slate-500 mb-1.5 tracking-widest">Satuan Transfer</div>
                                        @if($displayUnit === 'pcs')
                                            <div class="w-full">
                                                <div class="transfer-unit-toggle flex bg-slate-100 dark:bg-slate-900 p-0.5 rounded-md w-full border border-slate-200 dark:border-slate-700">
                                                    <label class="cursor-pointer flex-1 relative">
                                                        <input type="radio" x-bind:disabled="!isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pack" class="peer sr-only" x-model="unit">
                                                        <div class="transfer-unit-option flex items-center justify-center py-2 rounded text-[11px] font-bold uppercase tracking-widest text-slate-500 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-md dark:text-slate-400 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white transition-all duration-300">Pack</div>
                                                    </label>
                                                    <label class="cursor-pointer flex-1 relative">
                                                        <input type="radio" x-bind:disabled="!isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pcs" class="peer sr-only" x-model="unit">
                                                        <div class="transfer-unit-option flex items-center justify-center py-2 rounded text-[11px] font-bold uppercase tracking-widest text-slate-500 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-md dark:text-slate-400 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white transition-all duration-300">Pcs</div>
                                                    </label>
                                                </div>
                                                @if($packSize > 1)
                                                    <div class="text-center mt-1.5">
                                                        <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 tracking-wider">
                                                            <i class="fa-solid fa-box-open mr-1 opacity-70"></i> 1 PACK = {{ $packSize }} PCS
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif(count($transferUnitOptions) > 1)
                                            <select x-bind:disabled="!isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" x-model="unit" class="w-full h-9 px-3 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-[12px] font-semibold text-slate-700 dark:text-slate-300 outline-none focus:ring-2 focus:ring-blue-500 transition-shadow">
                                                @foreach($transferUnitOptions as $unitValue => $unitLabel)
                                                    <option value="{{ $unitValue }}" {{ $transferInputUnit === $unitValue ? 'selected' : '' }}>
                                                        {{ $unitLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="hidden" x-bind:disabled="!isMobile" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="{{ $transferInputUnit }}">
                                            <span class="inline-flex h-9 items-center px-3 rounded-md bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-[12px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest w-full">
                                                {{ strtoupper($transferInputUnit) }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Catatan --}}
                                    <div>
                                        <div class="text-[10px] font-bold uppercase text-slate-400 dark:text-slate-500 mb-1.5 tracking-widest">Catatan (Opsional)</div>
                                        <input
                                            type="text"
                                            x-bind:disabled="!isMobile"
                                            name="transfers[{{ $ingredient->id }}][note]"
                                            placeholder="Catatan..."
                                            class="w-full h-9 px-3 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-[13px] text-slate-700 dark:text-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white dark:focus:bg-slate-800 transition-all"
                                        />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if($ingredients->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                {{ $ingredients->links() }}
            </div>
        @endif

        {{-- ACTION FOOTER --}}
        <div class="px-6 py-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 sticky bottom-0 z-20">
            <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
               class="w-full sm:w-auto h-[48px] inline-flex items-center justify-center rounded-xl border border-slate-300 dark:border-slate-600 px-8 text-[14px] font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 transition-all shadow-sm bg-white dark:bg-slate-900">
                Batal
            </a>
            <button type="submit" class="w-full sm:w-auto h-[48px] min-w-[240px] flex items-center justify-center gap-2 rounded-xl bg-blue-600 text-white text-[15px] font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/30">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                Simpan Transfer Harian
            </button>
        </div>
    </form>

</div>

@endsection
