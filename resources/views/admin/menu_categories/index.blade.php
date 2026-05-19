@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Kategori Menu')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto hide-scrollbar pb-1">
            <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Beranda
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            
            <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                Menu & Resep
            </span>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            
            <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                Kategori Menu
            </span>
        </nav>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 lg:gap-8">
            <div class="flex-1">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                    Kategori Menu
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                    Kelola pengelompokan menu untuk sistem POS. Kategori memudahkan kasir menemukan menu yang tepat saat transaksi.
                </p>
            </div>
        </div>
    </div>

    {{-- ================= ALERTS ================= --}}
    @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300 shadow-sm">
            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300 shadow-sm">
            <svg class="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ================= TABLE & CARD SECTION ================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Daftar Kategori Menu</h2>
                @if(method_exists($categories, 'total'))
                <span class="px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500">
                    {{ $categories->total() }} kategori
                </span>
                @endif
            </div>
            <a href="{{ route('admin.menu-categories.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 h-8 bg-blue-600 text-white text-[12px] font-semibold rounded-lg hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Tambah Kategori
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                    <tr>
                        <th class="px-6 py-4">Nama Kategori</th>
                        <th class="px-6 py-4 text-center">Tipe</th>
                        <th class="px-6 py-4 text-center">Jumlah Menu</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                    @forelse($categories as $category)
                        
                        <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800 dark:text-white">{{ $category->name }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($category->is_addon)
                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 font-semibold rounded-md text-xs">
                                        Add On
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 font-semibold rounded-md text-xs">
                                        Menu Utama
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold rounded-md text-xs">
                                    {{ $category->menus_count }} Menu
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3 text-[13px] font-semibold">
                                    <a href="{{ route('admin.menu-categories.edit', $category->id) }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                        Edit
                                    </a>
                                    <span class="text-slate-300 dark:text-slate-700">|</span>
                                    <form action="{{ route('admin.menu-categories.destroy', $category->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Yakin ingin menghapus kategori menu ini?')" class="text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr class="md:hidden">
                            <td colspan="4" class="p-0">
                                <div class="p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                    <div class="flex justify-between items-start gap-3 mb-4">
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white">{{ $category->name }}</p>
                                            <span class="mt-2 inline-flex px-2.5 py-1 {{ $category->is_addon ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' }} font-semibold rounded-md text-[11px] whitespace-nowrap">
                                                {{ $category->is_addon ? 'Add On' : 'Menu Utama' }}
                                            </span>
                                        </div>
                                        <span class="shrink-0 px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold rounded-md text-[11px] whitespace-nowrap">{{ $category->menus_count }} Menu</span>
                                    </div>
                                    <div class="flex items-center justify-start gap-4 text-xs font-semibold mt-2 pt-4 border-t border-slate-100 dark:border-slate-800/50">
                                        <a href="{{ route('admin.menu-categories.edit', $category->id) }}" class="flex items-center gap-1.5 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.menu-categories.destroy', $category->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Yakin ingin menghapus kategori menu ini?')" class="flex items-center gap-1.5 text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Belum ada data kategori menu.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ================= PAGINATION ================= --}}
        @if(method_exists($categories, 'hasPages') && $categories->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-[13px] text-slate-500 dark:text-slate-400 text-center sm:text-left font-medium">
                    Halaman <span class="font-bold text-slate-700 dark:text-slate-300">{{ $categories->currentPage() }}</span> 
                    dari <span class="font-bold text-slate-700 dark:text-slate-300">{{ $categories->lastPage() }}</span>
                </div>
                
                <div class="flex items-center gap-6 text-[13px] font-semibold">
                    @if ($categories->onFirstPage())
                        <span class="text-slate-400 cursor-not-allowed dark:text-slate-600">&lt; Prev</span>
                    @else
                        <a href="{{ $categories->previousPageUrl() }}" class="text-blue-600 hover:text-blue-700 transition dark:text-blue-400 dark:hover:text-blue-300">&lt; Prev</a>
                    @endif

                    @if ($categories->hasMorePages())
                        <a href="{{ $categories->nextPageUrl() }}" class="text-blue-600 hover:text-blue-700 transition dark:text-blue-400 dark:hover:text-blue-300">Next &gt;</a>
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
