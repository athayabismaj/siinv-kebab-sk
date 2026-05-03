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
    <div class="flex items-center gap-4 p-4 md:p-5 bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/50 rounded-2xl shadow-sm">
        <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-md shadow-blue-500/30">
            {{ strtoupper(substr($session->cashier->name ?? 'U', 0, 1)) }}
        </div>
        <div>
            <p class="text-[11px] font-bold text-blue-500 dark:text-blue-400 uppercase tracking-widest mb-0.5">Sesi Aktif #{{ $session->id }}</p>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[14px]">
                {{ $session->cashier->name ?? 'User Tidak Diketahui' }} 
                <span class="font-normal text-slate-500 mx-1.5">&bull;</span> 
                <span class="font-medium text-slate-600 dark:text-slate-400">{{ $session->session_date->translatedFormat('d F Y') }}</span>
            </p>
        </div>
    </div>

    {{-- ================= SPLIT LAYOUT ================= --}}
    <div class="flex flex-col-reverse lg:flex-row gap-6 items-start">

        {{-- KOLOM KIRI: PENCARIAN & TABEL BAHAN --}}
        <div class="w-full lg:w-7/12 xl:w-2/3 space-y-4">
            
            {{-- Form Pencarian --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-4">
                <form method="GET" action="{{ route('admin.daily-stocks.transfer.form') }}" class="flex flex-col sm:flex-row gap-3 relative z-10">
                    <input type="hidden" name="session_id" value="{{ $session->id }}">
                    
                    <div class="flex-1 relative flex items-center w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Cari nama bahan di gudang..."
                               class="w-full h-10 bg-transparent pl-10 pr-4 text-[13px] font-medium text-slate-700 outline-none dark:text-slate-200 placeholder:text-slate-400">
                    </div>

                    <select name="category_id" class="w-full sm:w-56 h-10 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (int) $selectedCategoryId === (int) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($search || $selectedCategoryId > 0)
                            <a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]) }}" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-400 hover:text-red-500 transition-colors px-2">
                                Reset
                            </a>
                        @endif
                        <button type="submit" class="w-full sm:w-auto px-6 h-10 rounded-xl bg-slate-900 text-white text-[13px] font-bold hover:bg-slate-800 transition shadow-sm dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                            Cari
                        </button>
                    </div>
                </form>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    Langkah cepat: cari bahan, klik <strong>Pilih</strong>, isi jumlah dibawa, lalu simpan.
                </p>
            </div>

            {{-- Tabel Daftar Bahan --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 flex items-center justify-between">
                    <h2 class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Daftar Bahan Gudang</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="hidden md:table-header-group">
                            <tr class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800">
                                <th class="px-5 py-3 whitespace-nowrap">Bahan</th>
                                <th class="px-5 py-3 text-right whitespace-nowrap">Stok Gudang</th>
                                <th class="px-5 py-3 text-right whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @forelse($ingredients as $ingredient)
                                @php
                                    $isSelected = (int) ($selectedIngredient->id ?? 0) === (int) $ingredient->id;
                                @endphp
                                
                                {{-- ROW DESKTOP --}}
                                <tr class="hidden md:table-row transition-colors {{ $isSelected ? 'bg-blue-50/50 dark:bg-blue-900/10' : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/40' }}">
                                    <td class="px-5 py-3 align-middle">
                                        <p class="font-bold text-[13px] {{ $isSelected ? 'text-blue-700 dark:text-blue-400' : 'text-slate-800 dark:text-slate-200' }}">{{ $ingredient->name }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-right align-middle">
                                        <span class="font-semibold text-slate-600 dark:text-slate-300 text-[13px] tabular-nums">{{ number_format((float) $ingredient->transfer_stock_value, 2, ',', '.') }}</span>
                                        <span class="text-[10px] text-slate-400 ml-1 uppercase">{{ $ingredient->transfer_stock_unit }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right align-middle">
                                        @if($isSelected)
                                            <span class="inline-flex h-8 items-center rounded-lg bg-blue-600 px-4 text-[11px] font-bold text-white shadow-sm cursor-default">
                                                Terpilih
                                            </span>
                                        @else
                                            <a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id, 'search' => $search, 'category_id' => $selectedCategoryId ?: null, 'page' => $ingredients->currentPage(), 'ingredient_id' => $ingredient->id]) }}"
                                               class="inline-flex h-8 items-center rounded-lg bg-slate-100 dark:bg-slate-800 px-4 text-[11px] font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                                Pilih
                                            </a>
                                        @endif
                                    </td>
                                </tr>

                                {{-- CARD MOBILE --}}
                                <tr class="md:hidden border-b border-slate-100 dark:border-slate-800/50 last:border-0 {{ $isSelected ? 'bg-blue-50/50 dark:bg-blue-900/10' : '' }}">
                                    <td class="p-0">
                                        <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                            <div>
                                                <p class="font-bold text-[14px] {{ $isSelected ? 'text-blue-700 dark:text-blue-400' : 'text-slate-900 dark:text-white' }} leading-tight mb-1">{{ $ingredient->name }}</p>
                                                <p class="text-[11px] font-medium text-slate-500">Stok: <span class="font-bold text-slate-700 dark:text-slate-300 tabular-nums">{{ number_format((float) $ingredient->transfer_stock_value, 2, ',', '.') }}</span> <span class="uppercase">{{ $ingredient->transfer_stock_unit }}</span></p>
                                            </div>
                                            <div class="shrink-0">
                                                @if($isSelected)
                                                    <span class="inline-flex h-8 items-center rounded-lg bg-blue-600 px-3 text-[11px] font-bold text-white shadow-sm">
                                                        Terpilih
                                                    </span>
                                                @else
                                                    <a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id, 'search' => $search, 'category_id' => $selectedCategoryId ?: null, 'page' => $ingredients->currentPage(), 'ingredient_id' => $ingredient->id]) }}"
                                                       class="inline-flex h-8 items-center rounded-lg bg-slate-100 dark:bg-slate-800 px-3 text-[11px] font-bold text-slate-600 dark:text-slate-300 transition-colors">
                                                        Pilih
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-12 text-center">
                                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                        </div>
                                        <p class="text-slate-500 dark:text-slate-400 text-[13px] font-medium">Bahan tidak ditemukan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($ingredients->hasPages())
                    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900">
                        {{ $ingredients->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- KOLOM KANAN: STICKY FORM TRANSFER --}}
        <div class="w-full lg:w-5/12 xl:w-1/3 relative">
            <div class="sticky top-24 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-slate-200/40 dark:shadow-none overflow-hidden transition-all">
                
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                    <h2 class="text-[12px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        Form Input Transfer
                    </h2>
                    @if($selectedIngredient)
                        <div class="mt-2 inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Bahan aktif: {{ $selectedIngredient->name }}
                        </div>
                    @endif
                </div>

                <div class="p-6">
                    @if($selectedIngredient)
                        {{-- FORM MENGGUNAKAN STANDAR HTML/CSS BIASA (TANPA ALPINEJS) --}}
                        <form method="POST" action="{{ route('admin.daily-stocks.transfer') }}" class="space-y-5">
                            @csrf
                            <input type="hidden" name="session_id" value="{{ $session->id }}">
                            <input type="hidden" name="ingredient_id" value="{{ $selectedIngredient->id }}">

                            {{-- Info Bahan Terpilih --}}
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 border border-slate-100 dark:border-slate-700">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Bahan Terpilih</p>
                                <p class="font-bold text-[15px] text-slate-800 dark:text-white leading-tight mb-1">{{ $selectedIngredient->name }}</p>
                                @php
                                    $displayUnit = strtolower((string) $selectedIngredient->display_unit);
                                    $transferInputUnit = strtolower((string) $selectedIngredient->transfer_input_unit);
                                    $transferUnitOptions = $selectedIngredient->transfer_unit_options ?? [$transferInputUnit => $transferInputUnit];
                                    $packSize = max(1, (int) ($selectedIngredient->pack_size ?? 1));
                                    $stockBase = (float) $selectedIngredient->stock;
                                    $stockPack = $displayUnit === 'pcs' ? $stockBase / $packSize : null;
                                @endphp
                                <p class="text-[11px] font-medium text-slate-500">
                                    Stok Tersedia:
                                    <span class="font-bold text-slate-700 dark:text-slate-300">
                                        {{ number_format((float) $selectedIngredient->transfer_stock_value, 2, ',', '.') }}
                                    </span>
                                    {{ $selectedIngredient->transfer_stock_unit }}
                                </p>
                                @if($displayUnit === 'pcs')
                                    <p class="text-[11px] font-medium text-slate-500 mt-1">
                                        Setara
                                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($stockPack, 2, ',', '.') }}</span>
                                        pack &bull; 1 pack = {{ $packSize }} pcs
                                    </p>
                                @endif
                            </div>

                            @if($displayUnit === 'pcs')
                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                                        Satuan Input
                                    </label>
                                    <select
                                        name="transfer_unit"
                                        class="w-full h-11 px-4 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-[13px] font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                    >
                                        <option value="pack" {{ old('transfer_unit', 'pack') === 'pack' ? 'selected' : '' }}>Pack (1 pack = {{ $packSize }} pcs)</option>
                                        <option value="pcs" {{ old('transfer_unit') === 'pcs' ? 'selected' : '' }}>Pcs</option>
                                    </select>
                                    <p class="text-[11px] text-slate-500">
                                        Gunakan <strong>Pack</strong> untuk input dus/paket, atau <strong>Pcs</strong> untuk input ecer.
                                    </p>
                                </div>
                            @elseif(count($transferUnitOptions) > 1)
                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                                        Satuan Input
                                    </label>
                                    <select
                                        name="transfer_unit"
                                        class="w-full h-11 px-4 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-[13px] font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                    >
                                        @foreach($transferUnitOptions as $unitValue => $unitLabel)
                                            <option value="{{ $unitValue }}" {{ old('transfer_unit', $transferInputUnit) === $unitValue ? 'selected' : '' }}>
                                                {{ $unitLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[11px] text-slate-500">
                                        Pilih satuan sesuai cara input fisik di gudang.
                                    </p>
                                </div>
                            @else
                                <input type="hidden" name="transfer_unit" value="{{ $transferInputUnit }}">
                            @endif

                            {{-- Input Jumlah --}}
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                                    Jumlah Dibawa{{ $displayUnit === 'pcs' ? '' : ' (' . strtoupper($transferInputUnit) . ')' }} <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" 
                                           name="quantity" 
                                           min="0.01" 
                                           step="0.01" 
                                           required 
                                           placeholder="0.00"
                                           class="w-full h-11 px-4 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-[14px] font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none tabular-nums transition-all">
                                </div>
                            </div>

                            {{-- Input Catatan --}}
                            <div class="space-y-1.5">
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-widest">Catatan Tambahan</label>
                                <input type="text" 
                                       name="note" 
                                       maxlength="255" 
                                       placeholder="Opsional..."
                                       class="w-full h-11 px-4 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-[13px] font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                            </div>

                            {{-- Submit Button (Menggunakan class bg-blue-600 yang pasti ada) --}}
                            <div class="pt-4 flex flex-col sm:flex-row items-center justify-end gap-3">
                                <a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]) }}"
                                   class="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-[13px] text-center hover:bg-slate-50 transition-colors">
                                    Ganti Bahan
                                </a>
                                <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
                                   class="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-[13px] text-center hover:bg-slate-50 transition-colors">
                                    Batal
                                </a>
                                
                                <button type="submit" 
                                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-blue-600 text-white font-bold text-[13px] text-center hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all">
                                    Tambah ke Stok Harian
                                </button>
                            </div>
                        </form>
                    @else
                        {{-- State Jika Belum Ada Bahan Terpilih --}}
                        <div class="flex flex-col items-center justify-center text-center py-8">
                            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4 border border-slate-100 dark:border-slate-700">
                                <svg class="w-7 h-7 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                            </div>
                            <h3 class="text-[14px] font-bold text-slate-800 dark:text-slate-200 mb-1.5">Belum Ada Bahan Terpilih</h3>
                            <p class="text-[12px] font-medium text-slate-500 dark:text-slate-400">Silakan cari dan klik tombol <strong class="text-blue-600 dark:text-blue-400">Pilih</strong> pada tabel di sebelah kiri (atau di bawah) untuk mulai menginput.</p>
                        </div>
                        
                        <div class="pt-4 flex flex-col sm:flex-row justify-end gap-3">
                            <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
                               class="w-full sm:w-auto px-6 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-[13px] text-center hover:bg-slate-50 transition-colors">
                                Batal
                            </a>
                            <button type="button" 
                                    disabled
                                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-slate-200 text-slate-500 font-bold text-[13px] text-center cursor-not-allowed">
                                Pilih Bahan Dulu
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

@endsection
