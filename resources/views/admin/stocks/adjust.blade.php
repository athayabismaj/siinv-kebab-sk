@extends('layouts.app')

@section('title', 'Penyesuaian Stok')

@section('content')
@php
    $isPackMode = ($ingredient->display_unit ?? '') === 'pcs' && (int) ($ingredient->pack_size ?? 1) > 1;
    $packSize = max(1, (int) ($ingredient->pack_size ?? 1));
    $currentStockPcs = (float) $ingredient->stock;
    $currentDisplayStock = $isPackMode ? ($currentStockPcs / $packSize) : (float) $ingredient->converted_stock;
    $displayUnit = $isPackMode ? 'pack' : $ingredient->display_unit;
@endphp

<div class="w-full space-y-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex items-center justify-between">
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
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Penyesuaian Stok
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Perbarui dan sinkronkan jumlah stok bahan baku secara manual.
            </p>
        </div>

        <a href="{{ route('admin.stocks.index') }}"
           class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-200"
           title="Kembali">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
    </div>

    {{-- ================= CARD MAIN ================= --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        
        {{-- ================= ERROR ALERT ================= --}}
        @if(session('error'))
            <div class="border-b border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-red-800 dark:text-red-300">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        @endif

        <div class="p-6 md:p-8">
            {{-- ================= INFO BAHAN (MINI WIDGET) ================= --}}
            <div class="mb-8 flex flex-col justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/50 p-5 sm:flex-row sm:items-center dark:border-slate-700/50 dark:bg-slate-800/50">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Target Bahan</p>
                    <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">
                        {{ $ingredient->name }}
                    </p>
                    @if($isPackMode)
                        <p class="mt-0.5 text-xs text-slate-500">
                            Konversi: <span class="font-semibold text-slate-700 dark:text-slate-300">1 pack = {{ $packSize }} pcs</span>
                        </p>
                    @endif
                </div>

                <div class="sm:text-right">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Stok Saat Ini</p>
                    <p class="mt-1 flex items-baseline gap-1 text-xl font-black text-amber-500 dark:text-amber-400 sm:justify-end">
                        {{ rtrim(rtrim(number_format($currentDisplayStock, 2, '.', ''), '0'), '.') }}
                        <span class="text-sm font-semibold uppercase text-slate-500">{{ $displayUnit }}</span>
                    </p>
                    @if($isPackMode)
                        <p class="mt-0.5 text-xs font-medium text-slate-400">
                            {{ number_format($currentStockPcs, 0, ',', '.') }} pcs
                        </p>
                    @endif
                </div>
            </div>

            {{-- ================= FORM DENGAN ALPINE.JS ================= --}}
            <form method="POST"
                  x-data="{ 
                      submitting: false, 
                      newStock: '{{ old('new_stock') }}',
                      packSize: {{ $packSize }},
                      isPackMode: {{ $isPackMode ? 'true' : 'false' }}
                  }"
                  @submit="submitting = true"
                  class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
                    {{-- Kiri: INPUT NEW STOCK --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
                            Stok Akhir (Baru) <span class="text-red-500">*</span>
                        </label>
                        
                        <div class="relative">
                            <input
                                type="number"
                                step="0.01"
                                name="new_stock"
                                x-model="newStock"
                                placeholder="0.00"
                                class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-4 pr-16 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-amber-500 sm:text-sm"
                                required>
                            
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                                <span class="text-xs font-bold uppercase text-slate-400">{{ $displayUnit }}</span>
                            </div>
                        </div>

                        @error('new_stock')
                            <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror

                        <template x-if="isPackMode && newStock !== ''">
                            <p class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300" x-transition>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Sistem akan mengubah stok akhir menjadi <strong x-text="(newStock * packSize).toLocaleString('id-ID') + ' pcs'"></strong>
                            </p>
                        </template>
                    </div>

                    {{-- Kanan: INPUT NOTE --}}
                    <div>
                        <label class="mb-1.5 flex items-center justify-between text-sm font-semibold text-slate-800 dark:text-slate-200">
                            <span>Alasan Penyesuaian <span class="font-normal text-slate-400">(Opsional)</span></span>
                        </label>
                        
                        <textarea
                            name="note"
                            rows="1"
                            placeholder="Contoh: Stok rusak, selisih opname..."
                            class="h-[46px] w-full resize-none rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-amber-500 sm:text-sm">{{ old('note') }}</textarea>

                        @error('note')
                            <p class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ================= ACTION BUTTONS ================= --}}
                <div class="mt-6 border-t border-slate-100 pt-4 sm:flex sm:flex-row sm:justify-end flex-col-reverse flex gap-3 dark:border-slate-800">
                    <a href="{{ route('admin.stocks.index') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700">
                        Batal
                    </a>

                    <button
                        type="submit"
                        :disabled="submitting || newStock === ''"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto">
                        
                        <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>

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