@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Resep')

@section('content')
<div class="w-full space-y-5 overflow-x-hidden pb-10">
    <x-page-header
        title="Edit Resep"
        subtitle="{{ $variant->menu?->name }} · {{ $variant->name }}"
        breadcrumb-parent="Manajemen Resep"
        breadcrumb-child="Edit Resep"
    >
        <a href="{{ route('admin.recipes.index') }}" class="inline-flex h-10 w-full shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[12px] font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 sm:w-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M15 19l-7-7 7-7" /></svg>
            Kembali
        </a>
    </x-page-header>

    @if(session('success'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M5 13l4 4L19 7" /></svg>
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.recipes.edit', $variant) }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px_auto]">
        <label class="relative block">
            <span class="sr-only">Cari bahan</span>
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" /></svg>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari bahan baku..." @input.debounce.500ms="$el.form.requestSubmit()" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-[13px] font-medium text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:bg-slate-900">
        </label>

        <label>
            <span class="sr-only">Filter kategori bahan</span>
            <select name="category" data-submit-on-change class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs font-bold text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <option value="">Semua Kategori</option>
                @foreach($allCategories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </label>

        @if(request()->filled('search') || request()->filled('category'))
            <a href="{{ route('admin.recipes.edit', $variant) }}" class="inline-flex h-11 items-center justify-center px-2 text-xs font-bold text-slate-400 transition hover:text-rose-600">Reset</a>
        @endif
    </form>

    @include('admin.recipes.partials.form', [
        'variant' => $variant,
        'ingredients' => $ingredients,
        'quantities' => $quantities,
        'recipeIngredientCount' => $recipeIngredientCount,
    ])
</div>
@endsection
