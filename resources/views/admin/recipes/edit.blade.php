@extends('layouts.app')

@section('title', 'Edit Resep - Sistem Inventory')

@section('content')

<div class="space-y-8">

    {{-- HEADER --}}
    <div>
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
            Edit Resep
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ $variant->menu?->name }} - {{ $variant->name }}
        </p>
    </div>


    {{-- FILTER --}}
    <form method="GET"
          class="flex flex-col md:flex-row md:items-center gap-3">

        <select name="category"
                class="px-4 py-2.5 rounded-xl
                       border border-slate-300 dark:border-slate-700
                       bg-white dark:bg-slate-800 text-sm
                       focus:ring-2 focus:ring-blue-500
                       focus:outline-none transition">

            <option value="">Semua Kategori</option>

            @foreach($allCategories as $cat)
                <option value="{{ $cat->id }}"
                    {{ request('category') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach

        </select>

        <button type="submit"
                class="px-5 py-2.5 rounded-xl
                       bg-blue-600 text-white text-sm font-medium
                       hover:bg-blue-700 transition">
            Filter
        </button>

        @if(request()->filled('category'))
            <a href="{{ route('admin.recipes.edit', $variant->id) }}"
               class="text-sm text-slate-500 hover:text-blue-600 transition">
                Reset
            </a>
        @endif

    </form>


    {{-- FORM --}}
    @include('admin.recipes.partials.form', [
        'variant' => $variant,
        'ingredientCategories' => $ingredientCategories
    ])

</div>

@endsection
