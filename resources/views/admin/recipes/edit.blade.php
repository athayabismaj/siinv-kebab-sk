@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Resep Varian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-24">

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
            
            <a href="{{ route('admin.recipes.index') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Manajemen Resep
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            
            <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                Edit Resep
            </span>
        </nav>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Edit Resep Varian
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Tentukan komposisi bahan baku untuk varian <strong class="text-slate-700 dark:text-slate-300">{{ $variant->menu?->name }} - {{ $variant->name }}</strong>. Biarkan kosong "0" jika bahan tidak digunakan.
            </p>
        </div>
    </div>

    {{-- ================= FILTER KATEGORI BAHAN ================= --}}
    <form method="GET" action="{{ route('admin.recipes.edit', $variant->id) }}" class="flex flex-col sm:flex-row gap-3 w-full relative z-10 py-2 mb-2">
        
        <select name="category" class="w-full sm:flex-1 h-10 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <option value="">Semua Kategori Bahan</option>
            @foreach($allCategories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>

        <div class="flex items-center gap-3 shrink-0 justify-end w-full sm:w-auto">
            @if(request()->filled('category'))
                <a href="{{ route('admin.recipes.edit', $variant->id) }}" class="mr-1 inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-400 hover:text-red-500 transition-colors">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    Reset
                </a>
            @endif
            
            <button type="submit" class="flex-1 sm:flex-none px-6 h-10 rounded-xl bg-slate-900 text-white text-[13px] font-semibold hover:bg-slate-800 transition shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                Filter
            </button>
        </div>
    </form>

    {{-- Render Form Partial --}}
    @include('admin.recipes.partials.form', [
        'variant' => $variant,
        'ingredientCategories' => $ingredientCategories
    ])

</div>

@endsection