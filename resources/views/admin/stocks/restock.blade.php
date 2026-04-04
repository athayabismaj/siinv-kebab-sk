@extends('layouts.app')

@section('title', 'Restok Bahan')

@section('content')
@php
    $isPackMode = ($ingredient->display_unit ?? '') === 'pcs' && (int) ($ingredient->pack_size ?? 1) > 1;
    $packSize = max(1, (int) ($ingredient->pack_size ?? 1));
    $currentStockPcs = (float) $ingredient->stock;
    $currentDisplayStock = $isPackMode ? ($currentStockPcs / $packSize) : (float) $ingredient->converted_stock;
    $displayUnit = $isPackMode ? 'pack' : $ingredient->display_unit;
@endphp

<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
            <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-slate-500 dark:text-slate-400">Inventori</span>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <a href="{{ route('admin.stocks.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Restok & Penyesuaian</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-blue-600 dark:text-blue-400">Restok</span>
        </nav>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Restok Bahan
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Tambahkan jumlah stok bahan baku baru ke dalam sistem inventaris.
            </p>
        </div>
    </div>

    {{-- ================= CARD MAIN ================= --}}
    <div class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        {{-- ================= ERROR ALERT ================= --}}
        @if(session('error'))
            <div class="border-b border-red-200 bg-red-50 px-6 py-4 dark:border-red-900/50 dark:bg-red-900/20">
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

        <div class="p-6 md:p-8 space-y-10">
            
            {{-- ================= INFO BAHAN (WIDGET) ================= --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 rounded-xl border border-slate-100 bg-slate-50/50 p-6 dark:border-slate-700/50 dark:bg-slate-800/30">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Target Bahan</p>
                    <p class="text-lg font-bold text-slate-900 dark:text-white leading-tight">
                        {{ $ingredient->name }}
                    </p>
                    @if($isPackMode)
                        <div class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 px-2 py-1 text-[10px] font-bold text-slate-500 dark:text-slate-400 shadow-sm">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Konversi: 1 pack = {{ $packSize }} pcs
                        </div>
                    @endif
                </div>

                <div class="sm:text-right">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Stok Saat Ini</p>
                    <p class="flex items-baseline sm:justify-end gap-1.5 text-2xl font-black text-blue-600 dark:text-blue-400 tabular-nums leading-none">
                        {{ rtrim(rtrim(number_format($currentDisplayStock, 2, '.', ''), '0'), '.') }}
                        <span class="text-[11px] font-bold uppercase text-slate-400 tracking-widest">{{ $displayUnit }}</span>
                    </p>
                    @if($isPackMode)
                        <p class="mt-1.5 text-[11px] font-semibold text-slate-400">
                            Setara {{ number_format($currentStockPcs, 0, ',', '.') }} pcs
                        </p>
                    @endif
                </div>
            </div>

            {{-- ================= FORM DENGAN ALPINE.JS ================= --}}
            <form method="POST"
                  x-data="{ 
                      submitting: false, 
                      qty: '{{ old('quantity') }}',
                      packSize: {{ $packSize }},
                      isPackMode: {{ $isPackMode ? 'true' : 'false' }}
                  }"
                  @submit="submitting = true">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                    
                    {{-- Kiri: INPUT QUANTITY --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-800 dark:text-slate-200">
                            Jumlah Restok Masuk <span class="text-rose-500">*</span>
                        </label>
                        
                        <div class="relative">
                            <input
                                type="number"
                                step="0.01"
                                name="quantity"
                                x-model="qty"
                                placeholder="0.00"
                                class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-4 pr-16 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm tabular-nums"
                                required>
                            
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                                <span class="text-xs font-bold uppercase tracking-widest text-slate-400">{{ $displayUnit }}</span>
                            </div>
                        </div>

                        @error('quantity')
                            <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror

                        <template x-if="isPackMode && qty > 0">
                            <p class="mt-2.5 inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 transition-all" x-transition>
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                Sistem akan menambah stok sebesar <strong x-text="(qty * packSize).toLocaleString('id-ID') + ' pcs'"></strong>
                            </p>
                        </template>
                    </div>

                    {{-- Kanan: INPUT NOTE --}}
                    <div>
                        <label class="mb-1.5 flex items-center justify-between text-sm font-semibold text-slate-800 dark:text-slate-200">
                            <span>Catatan <span class="font-normal text-slate-400">(Opsional)</span></span>
                        </label>
                        
                        <textarea
                            name="note"
                            rows="1"
                            placeholder="Contoh: Restok mingguan dari supplier A..."
                            class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm h-[46px] resize-none">{{ old('note') }}</textarea>

                        @error('note')
                            <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ================= ACTION BUTTONS ================= --}}
                <div class="flex flex-col-reverse gap-3 pt-8 mt-8 border-t border-slate-100 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('admin.stocks.index') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700">
                        Batal
                    </a>

                    <button
                        type="submit"
                        :disabled="submitting || !qty"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto shadow-blue-500/20">
                        
                        <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>

                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span x-text="submitting ? 'Menyimpan...' : 'Konfirmasi Restok'"></span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection