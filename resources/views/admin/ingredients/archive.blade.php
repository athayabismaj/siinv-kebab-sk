@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Arsip Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
        <div class="flex-1">
            <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-slate-500 dark:text-slate-400">Inventori</span>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <a href="{{ route('admin.ingredients.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Manajemen Bahan</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-blue-600 dark:text-blue-400">Arsip Bahan</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Arsip Bahan
            </h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Daftar bahan yang telah dinonaktifkan. Bahan yang ada di sini tidak akan muncul di daftar restok atau pemakaian, namun dapat dipulihkan kapan saja.
            </p>
        </div>

        <div class="shrink-0 mt-2 lg:mt-0">
            <a href="{{ route('admin.ingredients.index') }}"
               class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-[13px] font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Manajemen Bahan
            </a>
        </div>
    </div>

    {{-- ================= ALERTS ================= --}}
    @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300 shadow-sm">
            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ================= FILTER SECTION (Tanpa Container Pembungkus) ================= --}}
    <form method="GET" action="{{ route('admin.ingredients.archive') }}" class="flex flex-col sm:flex-row gap-3 w-full relative z-10 py-2 mb-2">
        
        <div class="flex-1 relative flex items-center w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari nama bahan yang dinonaktifkan..."
                   class="w-full h-10 bg-transparent pl-10 pr-4 text-[13px] font-medium text-slate-700 outline-none dark:text-slate-200 placeholder:text-slate-400">
        </div>

        <select name="category" class="w-full sm:w-48 h-10 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <div class="flex items-center gap-3 shrink-0 justify-end w-full sm:w-auto">
            @if(request()->filled('search') || request()->filled('category'))
                <a href="{{ route('admin.ingredients.archive') }}" class="mr-1 inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-400 hover:text-red-500 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    Reset
                </a>
            @endif
            
            <button type="submit" class="flex-1 sm:flex-none px-6 h-10 rounded-xl bg-slate-900 text-white text-[13px] font-semibold hover:bg-slate-800 transition shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                Filter
            </button>
        </div>
    </form>

    {{-- ================= TABLE & CARD SECTION ================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Daftar Bahan Nonaktif</h2>
            </div>
            @if(method_exists($ingredients, 'total'))
            <div class="text-xs font-medium text-slate-500 bg-slate-50 dark:bg-slate-800/50 px-3 py-1.5 rounded-full border border-slate-100 dark:border-slate-700">
                Menampilkan <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $ingredients->firstItem() ?? 0 }} - {{ $ingredients->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $ingredients->total() }}</span> data
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                    <tr>
                        <th class="px-6 py-4">Nama Bahan</th>
                        <th class="px-6 py-4">Dinonaktifkan Pada</th>
                        <th class="px-6 py-4 text-right">Aksi Pemulihan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                    @forelse($ingredients as $ingredient)
                        
                        <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                            
                            {{-- Nama & Kategori --}}
                            <td class="px-6 py-5 w-1/2 align-top">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-[15px]">{{ $ingredient->name }}</p>
                                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mt-0.5">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Waktu Dinonaktifkan --}}
                            <td class="px-6 py-5 align-middle">
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ optional($ingredient->deleted_at)->format('d F Y') }}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    Pukul {{ optional($ingredient->deleted_at)->format('H:i') }} WIB
                                </div>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-5 text-right align-middle">
                                <form action="{{ route('admin.ingredients.restore', $ingredient->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            onclick="return confirm('Apakah Anda yakin ingin mengaktifkan kembali bahan ini?')" 
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-4 py-2 text-[12px] font-bold text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                        Pulihkan Bahan
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <tr class="md:hidden">
                            <td colspan="3" class="p-0">
                                <div class="p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                    <div class="flex justify-between items-start gap-3 mb-4">
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white">{{ $ingredient->name }}</p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
                                        </div>
                                        <span class="shrink-0 px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold rounded text-[10px] uppercase tracking-wider border border-slate-200 dark:border-slate-700">
                                            Nonaktif
                                        </span>
                                    </div>
                                    
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-4">
                                        Dihapus pada: <span class="font-semibold text-slate-700 dark:text-slate-300">{{ optional($ingredient->deleted_at)->format('d M Y, H:i') }}</span>
                                    </div>

                                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800/50">
                                        <form action="{{ route('admin.ingredients.restore', $ingredient->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" 
                                                    onclick="return confirm('Aktifkan kembali bahan ini?')" 
                                                    class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-50 px-4 py-2.5 text-[13px] font-bold text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                Pulihkan Bahan
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Tidak ada data bahan di dalam arsip saat ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ================= PAGINATION ================= --}}
        @if(method_exists($ingredients, 'hasPages') && $ingredients->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-[13px] text-slate-500 dark:text-slate-400 text-center sm:text-left font-medium">
                    Halaman <span class="font-bold text-slate-700 dark:text-slate-300">{{ $ingredients->currentPage() }}</span> 
                    dari <span class="font-bold text-slate-700 dark:text-slate-300">{{ $ingredients->lastPage() }}</span>
                </div>
                
                <div class="flex items-center gap-6 text-[13px] font-semibold">
                    @if ($ingredients->onFirstPage())
                        <span class="text-slate-400 cursor-not-allowed dark:text-slate-600">&lt; Prev</span>
                    @else
                        <a href="{{ $ingredients->previousPageUrl() }}" class="text-blue-600 hover:text-blue-700 transition dark:text-blue-400 dark:hover:text-blue-300">&lt; Prev</a>
                    @endif

                    @if ($ingredients->hasMorePages())
                        <a href="{{ $ingredients->nextPageUrl() }}" class="text-blue-600 hover:text-blue-700 transition dark:text-blue-400 dark:hover:text-blue-300">Next &gt;</a>
                    @else
                        <span class="text-slate-400 cursor-not-allowed dark:text-slate-600">Next &gt;</span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection