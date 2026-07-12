@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Input Pengeluaran')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    
    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="flex flex-col gap-4 mb-2">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">
            <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <a href="{{ route('admin.reports.cashflow') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Laporan Pengeluaran Operasional</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-blue-600 dark:text-blue-400">Input Pengeluaran</span>
        </nav>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Input Pengeluaran
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Catat biaya operasional harian. Pemasukan akan tetap dihitung secara otomatis dari transaksi menu di kasir.
            </p>
        </div>
    </div>

    {{-- ================= GLOBAL ERROR ALERT ================= --}}
    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300 shadow-sm">
            <svg class="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>
                <p class="font-bold mb-1">Terdapat kesalahan pada input Anda:</p>
                <ul class="list-disc list-inside space-y-1 ml-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ================= FORM CARD ================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Detail Pengeluaran</h2>
        </div>

        <div class="p-6 md:p-8">
            <form method="POST" action="{{ route('admin.reports.cashflow.store') }}" x-data="{ submitting: false }" @submit="submitting = true" class="space-y-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                    
                    {{-- 1. TANGGAL (Readonly) --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            Tanggal Pencatatan
                        </label>
                        <div class="relative">
                            <input type="date"
                                   name="entry_date"
                                   value="{{ $entryDate }}"
                                   min="{{ $entryDate }}"
                                   max="{{ $entryDate }}"
                                   readonly
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-slate-500 shadow-sm outline-none cursor-not-allowed dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400 sm:text-sm">
                            
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                        <p class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            Dikunci ke hari ini untuk akurasi laporan.
                        </p>
                    </div>

                    {{-- 2. NOMINAL PENGELUARAN --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            Nominal Pengeluaran <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <span class="text-sm font-bold text-slate-400 dark:text-slate-500">Rp</span>
                            </div>
                            <input type="number" 
                                   name="amount" 
                                   min="1" 
                                   step="0.01" 
                                   value="{{ old('amount') }}" 
                                   placeholder="0" 
                                   required
                                   class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-slate-900 font-medium shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                        </div>
                        @error('amount')
                            <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 3. KATEGORI / SUMBER --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            Kategori Pengeluaran <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="source" 
                               value="{{ old('source') }}" 
                               placeholder="Contoh: Gas, Transport, Listrik" 
                               required
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                        @error('source')
                            <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- 4. CATATAN --}}
                    <div>
                        <label class="flex items-center justify-between text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            <span>Catatan <span class="font-normal text-slate-400">(Opsional)</span></span>
                        </label>
                        <input type="text" 
                               name="note" 
                               value="{{ old('note') }}" 
                               placeholder="Tambahkan detail jika diperlukan..." 
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                        @error('note')
                            <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- ================= ACTION BUTTONS ================= --}}
                <div class="flex flex-col-reverse gap-3 pt-6 border-t border-slate-100 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-end">
                    <a href="{{ route('admin.reports.cashflow') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700">
                        Batal
                    </a>

                    <button type="submit" 
                            :disabled="submitting"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-8 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto">
                        
                        <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>

                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Pengeluaran'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
