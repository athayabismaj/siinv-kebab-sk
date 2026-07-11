@extends('layouts.app')

@section('title', 'Penyesuaian Stok')

@section('content')
@php
    $isPackMode = ($ingredient->display_unit ?? '') === 'pcs' && (int) ($ingredient->pack_size ?? 1) > 1;
    $packSize = max(1, (int) ($ingredient->pack_size ?? 1));
    $currentStockPcs = (float) $ingredient->stock;
    $currentDisplayStock = $isPackMode ? ($currentStockPcs / $packSize) : (float) $ingredient->converted_stock;
    $displayUnit = $isPackMode ? 'pack' : $ingredient->display_unit;
    $selectedInputUnit = old('input_unit', $isPackMode ? 'pack' : $displayUnit);
@endphp

<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6 flex items-start justify-between">
        <div>
            <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-slate-500 dark:text-slate-400">Inventori</span>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <a href="{{ route('admin.stocks.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Restok & Penyesuaian</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-blue-600 dark:text-blue-400">Penyesuaian</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Penyesuaian Stok
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Perbarui dan sinkronkan jumlah stok bahan baku secara manual.
            </p>
        </div>

        {{-- Tombol Back Kanan Atas --}}
        <a href="{{ route('admin.stocks.index') }}" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-800 border border-slate-700 text-slate-400 hover:text-white hover:bg-slate-700 transition shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
    </div>

    {{-- ================= CARD MAIN ================= --}}
    <div class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">

        <style>
            ::-webkit-scrollbar { width: 6px; height: 6px; }
            ::-webkit-scrollbar-track { background: transparent; }
            ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
            .dark ::-webkit-scrollbar-thumb { background: #334155; }
        </style>

        <div class="p-6 md:p-8">
            <form method="POST"
                  x-data="{
                      submitting: false,
                      newStock: '{{ old('new_stock') }}',
                      inputUnit: '{{ $selectedInputUnit }}',
                      packSize: {{ $packSize }},
                      isPackMode: {{ $isPackMode ? 'true' : 'false' }}
                  }"
                  @submit="submitting = true"
                  class="flex flex-col h-full">
                @csrf

                <div class="space-y-8 flex-1">

                    {{-- ================= ROW 1: INFO BAHAN & STOK ================= --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Target Bahan</p>
                            <p class="text-xl font-bold text-slate-900 dark:text-white leading-tight">
                                {{ $ingredient->name }}
                            </p>
                            @if($isPackMode)
                                <p class="mt-1 text-[11px] font-medium text-slate-500">Konversi: 1 pack = {{ $packSize }} pcs</p>
                            @endif
                        </div>

                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Stok Saat Ini</p>
                            <p class="flex items-baseline gap-1 text-2xl font-black text-blue-600 dark:text-blue-400 tabular-nums leading-none">
                                {{ rtrim(rtrim(number_format($currentDisplayStock, 2, '.', ''), '0'), '.') }}
                                <span class="text-xs font-bold uppercase text-slate-400 tracking-widest ml-1">{{ $displayUnit }}</span>
                            </p>
                            @if($isPackMode)
                                <p class="mt-1 text-[11px] font-medium text-slate-500">
                                    Setara {{ number_format($currentStockPcs, 0, ',', '.') }} pcs
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- ================= ROW 2: INPUTS (SEJAJAR ABSOLUT) ================= --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start">

                        {{-- KIRI: Stok Akhir --}}
                        <div class="flex flex-col">
                            {{-- HEADER KIRI: Dikunci dengan lg:h-14 dan lg:items-end --}}
                            <div class="flex flex-col lg:flex-row lg:items-end justify-between lg:h-14 pb-2 mb-1">
                                <label class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-3 lg:mb-0">
                                    Stok Akhir (Baru) <span class="text-rose-500">*</span>
                                </label>

                                @if($isPackMode)
                                    <div class="flex bg-slate-100 dark:bg-slate-800/80 p-1 rounded-lg w-full lg:w-auto">
                                        <label class="cursor-pointer flex-1 lg:flex-none">
                                            <input type="radio" name="input_unit" value="pack" x-model="inputUnit" class="peer sr-only">
                                            <span class="flex items-center justify-center rounded-md px-4 py-1 text-[11px] font-bold uppercase tracking-widest text-slate-500 transition peer-checked:bg-white peer-checked:text-blue-600 peer-checked:shadow-sm dark:text-slate-400 dark:peer-checked:bg-slate-700 dark:peer-checked:text-blue-400">Pack</span>
                                        </label>
                                        <label class="cursor-pointer flex-1 lg:flex-none">
                                            <input type="radio" name="input_unit" value="pcs" x-model="inputUnit" class="peer sr-only">
                                            <span class="flex items-center justify-center rounded-md px-4 py-1 text-[11px] font-bold uppercase tracking-widest text-slate-500 transition peer-checked:bg-white peer-checked:text-blue-600 peer-checked:shadow-sm dark:text-slate-400 dark:peer-checked:bg-slate-700 dark:peer-checked:text-blue-400">Pcs</span>
                                        </label>
                                    </div>
                                @endif
                            </div>

                            <div class="relative">
                                {{-- h-[52px] menggantikan py-3 untuk mengunci tinggi absolut --}}
                                <input
                                    type="number"
                                    step="0.01"
                                    name="new_stock"
                                    x-model="newStock"
                                    placeholder="0"
                                    class="w-full h-[52px] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 pr-16 text-lg font-bold text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:text-white tabular-nums [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                    required>

                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                                    <span class="text-xs font-bold uppercase text-slate-400" x-text="isPackMode ? inputUnit : '{{ $displayUnit }}'"></span>
                                </div>
                            </div>

                            @error('new_stock')
                                <p class="text-[11px] font-medium text-rose-600 dark:text-rose-400 mt-2">{{ $message }}</p>
                            @enderror

                            <template x-if="isPackMode && newStock !== ''">
                                <p class="text-[11px] font-medium text-slate-500 mt-2 transition-opacity" x-transition>
                                    Otomatis terkonversi menjadi <span class="font-bold text-slate-700 dark:text-slate-300" x-text="inputUnit === 'pack' ? ((newStock * packSize).toLocaleString('id-ID') + ' pcs') : ((newStock / packSize).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' pack')"></span>
                                </p>
                            </template>
                        </div>

                        {{-- KANAN: Alasan Penyesuaian --}}
                        <div class="flex flex-col">
                            {{-- HEADER KANAN: Kloning tinggi persis dengan lg:h-14 dan lg:items-end --}}
                            <div class="flex flex-col lg:flex-row lg:items-end lg:h-14 pb-2 mb-1">
                                <label class="text-sm font-semibold text-slate-800 dark:text-slate-200 mb-3 lg:mb-0">
                                    Alasan Penyesuaian <span class="text-rose-500">*</span>
                                </label>
                            </div>

                            {{-- h-[52px] menggantikan py-3 untuk mengunci tinggi absolut --}}
                            <input
                                type="text"
                                name="note"
                                placeholder="Contoh: Stok rusak, selisih opname..."
                                value="{{ old('note') }}"
                                class="w-full h-[52px] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 text-sm font-medium text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:text-white"
                                required>

                            @error('note')
                                <p class="text-[11px] font-medium text-rose-600 dark:text-rose-400 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ================= ACTION BUTTONS ================= --}}
                <div class="flex flex-col-reverse gap-3 pt-8 mt-8 border-t border-slate-100 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('admin.stocks.index') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl bg-white dark:bg-slate-800 px-6 py-2.5 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm ring-1 ring-inset ring-slate-300 dark:ring-slate-700 transition hover:bg-slate-50 dark:hover:bg-slate-700 sm:w-auto">
                        Batal
                    </a>

                    <button
                        type="submit"
                        :disabled="submitting || newStock === ''"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto shadow-amber-500/20 active:scale-95">

                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Penyesuaian'"></span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
