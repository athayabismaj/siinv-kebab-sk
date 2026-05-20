@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Transfer Stok Harian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= ALERTS ================= --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300 shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300 shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-300 shadow-sm">
            <p class="font-bold mb-1.5 flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Input belum valid:
            </p>
            <ul class="list-disc pl-7 space-y-0.5 font-medium">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex-1 w-full overflow-hidden">
            
            {{-- BREADCRUMB --}}
            <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto pb-1">
                <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">Kasir & Stok</span>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Stok Harian</a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">Transfer Bahan</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Input Bahan Dibawa
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Pilih bahan dari gudang utama dan tentukan jumlah yang dibawa oleh kasir untuk sesi ini.
            </p>
        </div>

        <div class="shrink-0 mt-2 sm:mt-0">
            <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
               class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-[13px] font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Sesi
            </a>
        </div>
    </div>

    {{-- ================= INFO SESI (BANNER) ================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm mb-6 gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-lg shrink-0 border border-blue-100 dark:border-slate-700">
                {{ strtoupper(substr($session->cashier->name ?? 'U', 0, 1)) }}
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Sesi Aktif #{{ $session->id }}</p>
                <h2 class="font-extrabold text-slate-800 dark:text-white text-[16px] leading-tight mb-0.5">
                    {{ $session->cashier->name ?? 'User Tidak Diketahui' }} 
                </h2>
                <p class="text-xs font-medium text-slate-500">
                    {{ $session->session_date->translatedFormat('d F Y') }}
                </p>
            </div>
        </div>
        <div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-900 border border-emerald-100 dark:border-emerald-800 text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest shadow-sm">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> BUKA
            </span>
        </div>
    </div>

    {{-- ================= FORM PENCARIAN & FILTER ================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden mb-6">
        <form method="GET" action="{{ route('admin.daily-stocks.transfer.form') }}" class="flex flex-col sm:flex-row divide-y sm:divide-y-0 sm:divide-x divide-slate-100 dark:divide-slate-800 relative z-10">
            <input type="hidden" name="session_id" value="{{ $session->id }}">
            
            <div class="flex-1 relative flex items-center bg-transparent">
                <svg class="w-4 h-4 text-slate-400 absolute left-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                <input type="text"
                       name="search"
                       value="{{ $search }}"
                       placeholder="Cari nama bahan di gudang..."
                       class="w-full h-14 bg-transparent pl-11 pr-4 text-[13px] font-medium text-slate-700 outline-none dark:text-slate-200 placeholder:text-slate-400 border-0 focus:ring-0">
            </div>

            <select name="category_id" class="w-full sm:w-48 h-14 border-0 bg-transparent px-4 text-[13px] font-medium text-slate-700 outline-none focus:ring-0 dark:text-slate-200 cursor-pointer">
                <option value="" class="bg-white text-slate-900 dark:bg-slate-800 dark:text-white">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" class="bg-white text-slate-900 dark:bg-slate-800 dark:text-white" {{ (int) $selectedCategoryId === (int) $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <div class="p-2 flex shrink-0 items-center gap-2">
                @if($search || $selectedCategoryId > 0)
                    <a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]) }}" class="inline-flex h-10 items-center justify-center px-4 text-[12px] font-bold text-slate-400 hover:text-rose-500 transition-colors rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900">
                        Reset
                    </a>
                @endif
                <button type="submit" class="w-full sm:w-auto px-6 h-10 rounded-xl bg-slate-900 text-white text-[13px] font-bold hover:bg-slate-800 transition shadow-sm dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    Cari
                </button>
            </div>
        </form>
    </div>

    {{-- ================= FORM BATCH TRANSFER ================= --}}
    <form method="POST" action="{{ route('admin.daily-stocks.transfer', ['search' => $search, 'category_id' => $selectedCategoryId, 'page' => request()->query('page')]) }}" class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-md overflow-hidden">
        @csrf
        <input type="hidden" name="session_id" value="{{ $session->id }}">

        {{-- HEADER FORM --}}
        <div class="px-6 py-5 border-b border-blue-200/60 dark:border-slate-800 bg-blue-50/50 dark:bg-slate-800/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-[13px] font-bold text-blue-700 dark:text-blue-500 uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Input Jumlah Transfer Harian (Batch)
                </h2>
                <p class="text-[12px] text-slate-600 dark:text-slate-400 mt-1 font-medium">Bahan yang tidak diinput jumlahnya tidak akan ditransfer. Pastikan tidak melebihi stok gudang.</p>
            </div>
        </div>

        <div class="p-6 bg-slate-50/30 dark:bg-slate-900">
            @if($ingredients->isEmpty())
                <div class="flex flex-col items-center justify-center text-center py-12">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 mb-4 border border-slate-200 dark:border-slate-700">
                        <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <h3 class="text-[15px] font-bold text-slate-800 dark:text-slate-200 mb-2">Bahan Tidak Ditemukan</h3>
                    <p class="text-[13px] font-medium text-slate-500 dark:text-slate-400 max-w-md">Tidak ada bahan yang cocok dengan kata kunci pencarian atau filter yang Anda gunakan.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <tr class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="px-4 py-3 whitespace-nowrap">Bahan & Stok Gudang</th>
                                <th class="px-4 py-3 whitespace-nowrap w-48">Satuan Transfer</th>
                                <th class="px-4 py-3 whitespace-nowrap w-40">Jml Dibawa <span class="text-rose-500">*</span></th>
                                <th class="px-4 py-3 whitespace-nowrap min-w-[200px]">Catatan (Opsional)</th>
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
                                
                                <tr x-data="{ unit: '{{ $defaultUnit }}' }" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/50 focus-within:bg-blue-50/30 dark:focus-within:bg-blue-900/10 transition-colors">
                                    {{-- Info Bahan --}}
                                    <td class="px-4 py-3 align-top">
                                        <p class="text-[13px] font-bold text-slate-800 dark:text-slate-200 leading-tight mb-1">{{ $ingredient->name }}</p>
                                        <div class="flex items-center gap-1">
                                            <span class="inline-flex h-5 items-center px-1.5 rounded bg-blue-50 dark:bg-blue-900/30 text-[10px] font-medium text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-800/50">
                                                Tersedia: <span class="font-bold ml-1 tabular-nums">{{ number_format($stockAvailable, 2, ',', '.') }}</span> <span class="ml-0.5 uppercase tracking-wider">{{ $ingredient->transfer_stock_unit }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    
                                    {{-- Satuan Input --}}
                                    <td class="px-4 py-3 align-top">
                                        @if($displayUnit === 'pcs')
                                            <div class="w-full">
                                                <div class="flex bg-slate-100 dark:bg-slate-900 p-0.5 rounded-md w-full border border-slate-200 dark:border-slate-700">
                                                    <label class="cursor-pointer flex-1 relative">
                                                        <input type="radio" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pack" class="peer sr-only" x-model="unit">
                                                        <div class="flex items-center justify-center py-1.5 rounded text-[10px] font-bold uppercase tracking-widest text-slate-500 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-md dark:text-slate-400 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white transition-all duration-300">Pack</div>
                                                    </label>
                                                    <label class="cursor-pointer flex-1 relative">
                                                        <input type="radio" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="pcs" class="peer sr-only" x-model="unit">
                                                        <div class="flex items-center justify-center py-1.5 rounded text-[10px] font-bold uppercase tracking-widest text-slate-500 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:shadow-md dark:text-slate-400 dark:peer-checked:bg-blue-600 dark:peer-checked:text-white transition-all duration-300">Pcs</div>
                                                    </label>
                                                </div>
                                                @if($packSize > 1)
                                                    <div class="text-center mt-1">
                                                        <p class="text-[9px] font-semibold text-slate-400 dark:text-slate-500 tracking-wider">
                                                            1 PACK = {{ $packSize }} PCS
                                                        </p>
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif(count($transferUnitOptions) > 1)
                                            <select name="transfers[{{ $ingredient->id }}][transfer_unit]" x-model="unit" class="w-full h-8 px-2 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-[11px] font-semibold text-slate-700 dark:text-slate-300 outline-none focus:ring-2 focus:ring-blue-500 transition-shadow">
                                                @foreach($transferUnitOptions as $unitValue => $unitLabel)
                                                    <option value="{{ $unitValue }}" {{ $transferInputUnit === $unitValue ? 'selected' : '' }}>
                                                        {{ $unitLabel }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="hidden" name="transfers[{{ $ingredient->id }}][transfer_unit]" value="{{ $transferInputUnit }}">
                                            <span class="inline-flex h-8 items-center px-3 rounded-md bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest w-full">
                                                {{ strtoupper($transferInputUnit) }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Input Jumlah --}}
                                    <td class="px-4 py-3 align-top">
                                        <div class="relative w-full">
                                            <input
                                                type="number"
                                                name="transfers[{{ $ingredient->id }}][quantity]"
                                                min="0"
                                                step="0.01"
                                                placeholder="0"
                                                class="w-full h-8 pl-3 pr-14 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-[13px] font-bold text-slate-900 dark:text-white outline-none tabular-nums focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all shadow-sm"
                                            >
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest" x-text="unit"></span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Input Catatan --}}
                                    <td class="px-4 py-3 align-top">
                                        <input
                                            type="text"
                                            name="transfers[{{ $ingredient->id }}][note]"
                                            placeholder="Catatan..."
                                            class="w-full h-8 px-3 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 text-[12px] text-slate-700 dark:text-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white dark:focus:bg-slate-800 transition-all"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
