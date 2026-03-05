@extends('layouts.app')

@section('title', 'Manajemen Resep - Sistem Inventory')

@section('content')

<div class="space-y-8">

    {{-- ================= HEADER ================= --}}
    <div>
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
            Manajemen Resep
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Kelola komposisi bahan pada setiap variant menu
        </p>
    </div>


    {{-- ================= ALERT ================= --}}
    @if(session('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 7500)"
            x-show="show"
            x-transition
            class="mb-6">

            <div class="flex justify-between items-center
                        bg-green-50 dark:bg-green-900/30
                        border border-green-200 dark:border-green-800
                        text-green-700 dark:text-green-300
                        px-5 py-4 rounded-2xl shadow-sm">

                <span class="text-sm">
                    {{ session('success') }}
                </span>

                <button @click="show = false"
                        class="text-green-500 hover:text-green-700">
                    x
                </button>
            </div>
        </div>
    @endif


    @if(session('error'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 7500)"
            x-show="show"
            x-transition
            class="mb-6">

            <div class="flex justify-between items-center
                        bg-red-50 dark:bg-red-900/30
                        border border-red-200 dark:border-red-800
                        text-red-700 dark:text-red-300
                        px-5 py-4 rounded-2xl shadow-sm">

                <span class="text-sm">
                    {{ session('error') }}
                </span>

                <button @click="show = false"
                        class="text-red-500 hover:text-red-700">
                    x
                </button>
            </div>
        </div>
    @endif


    {{-- ================= FILTER ================= --}}
    <form method="GET"
          action="{{ route('admin.recipes.index') }}"
          class="flex flex-col md:flex-row md:items-center gap-3">

        {{-- SEARCH --}}
        <div class="relative flex-1">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari menu / kategori / variant..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-800 text-sm
                          focus:ring-2 focus:ring-blue-500
                          focus:outline-none transition">

            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"
                 fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
        </div>

        {{-- CATEGORY --}}
        <select name="category"
                class="px-4 py-2.5 rounded-xl
                       border border-slate-300 dark:border-slate-700
                       bg-white dark:bg-slate-800 text-sm
                       focus:ring-2 focus:ring-blue-500
                       focus:outline-none transition">

            <option value="">Semua Kategori</option>

            @foreach($categories as $category)
                <option value="{{ $category->id }}"
                    {{ request('category') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach

        </select>

        {{-- BUTTON --}}
        <button type="submit"
                class="px-5 py-2.5 rounded-xl
                       bg-blue-600 text-white text-sm font-medium
                       hover:bg-blue-700 transition">
            Filter
        </button>

        {{-- RESET --}}
        @if(request()->filled('search') || request()->filled('category'))
            <a href="{{ route('admin.recipes.index') }}"
               class="text-sm text-slate-500 hover:text-blue-600 transition">
                Reset
            </a>
        @endif

    </form>


    {{-- ================= LIST MENU ================= --}}
    <div x-data="{ openMenu: null, openVariant: null }" class="space-y-4">

        @forelse($menus as $menu)

            <div class="bg-white dark:bg-slate-900
                        border border-slate-200 dark:border-slate-800
                        rounded-2xl shadow-sm overflow-hidden">

                {{-- MENU HEADER --}}
                <button
                    @click="openMenu === {{ $menu->id }} ? openMenu = null : openMenu = {{ $menu->id }}"
                    class="w-full px-6 py-5 flex justify-between items-center text-left">

                    <div>
                        <h2 class="font-semibold text-lg text-slate-800 dark:text-white">
                            {{ $menu->name }}
                        </h2>
                        <p class="text-xs text-slate-500">
                            {{ $menu->category?->name ?? 'Tanpa Kategori' }}
                            | {{ $menu->variants->count() }} variant
                        </p>
                    </div>

                    <span :class="openMenu === {{ $menu->id }} ? 'rotate-180' : ''"
                          class="transition-transform text-slate-400 text-xl">
                        v
                    </span>
                </button>


                {{-- VARIANTS --}}
                <div x-show="openMenu === {{ $menu->id }}"
                     x-collapse
                     x-cloak
                     class="border-t border-slate-200 dark:border-slate-800 px-6 py-5 space-y-4">

                    @forelse($menu->variants as $variant)

                        <div class="border border-slate-200 dark:border-slate-700
                                    rounded-xl p-4 bg-slate-50 dark:bg-slate-800/40">

                            <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">

                                <div>
                                    <p class="font-medium text-slate-800 dark:text-white">
                                        {{ $variant->name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $variant->ingredients->count() }} bahan
                                    </p>
                                </div>

                                <div class="flex gap-3">
                                    <button
                                        @click="openVariant === {{ $variant->id }} ? openVariant = null : openVariant = {{ $variant->id }}"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                        Detail
                                    </button>

                                    <a href="{{ route('admin.recipes.edit', $variant->id) }}"
                                       class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">
                                        Edit
                                    </a>
                                </div>

                            </div>


                            {{-- DETAIL BAHAN --}}
                            <div x-show="openVariant === {{ $variant->id }}"
                                 x-collapse
                                 x-cloak
                                 class="mt-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">

                                @forelse($variant->ingredients as $ingredient)

                                    <div class="bg-white dark:bg-slate-900
                                                border border-slate-200 dark:border-slate-700
                                                rounded-lg p-3 text-sm">

                                        <p class="font-medium text-slate-700 dark:text-slate-200">
                                            {{ $ingredient->name }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $ingredient->pivot->quantity }} {{ $ingredient->base_unit }}
                                        </p>

                                    </div>

                                @empty
                                    <div class="text-sm text-red-500 col-span-full">
                                        Belum ada bahan pada variant ini.
                                    </div>
                                @endforelse

                            </div>

                        </div>

                    @empty
                        <div class="text-sm text-slate-500 italic">
                            Belum ada variant untuk menu ini.
                        </div>
                    @endforelse

                </div>
            </div>

        @empty
            <div class="text-center text-slate-500 py-12
                        bg-white dark:bg-slate-900
                        border border-slate-200 dark:border-slate-800
                        rounded-2xl">
                Tidak ada data resep ditemukan.
            </div>
        @endforelse

    </div>


    {{-- ================= PAGINATION ================= --}}
    <div class="mt-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        {{-- INFO --}}
        <div class="text-sm text-slate-500 dark:text-slate-400 text-center md:text-left">
            Halaman
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $menus->currentPage() }}
            </span>
            dari
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $menus->lastPage() }}
            </span>
            | Total
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $menus->total() }}
            </span> data
        </div>

        {{-- BUTTON GROUP --}}
        <div class="flex justify-center md:justify-end gap-2">

            {{-- Previous --}}
            @if ($menus->onFirstPage())
                <span class="px-4 py-2 text-sm rounded-xl
                             bg-slate-200 dark:bg-slate-800
                             text-slate-400 cursor-not-allowed">
                    &lt; Previous
                </span>
            @else
                <a href="{{ $menus->previousPageUrl() }}"
                   class="px-4 py-2 text-sm rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-900
                          text-slate-700 dark:text-slate-200
                          hover:bg-slate-50 dark:hover:bg-slate-800
                          transition">
                    &lt; Previous
                </a>
            @endif


            {{-- Next --}}
            @if ($menus->hasMorePages())
                <a href="{{ $menus->nextPageUrl() }}"
                   class="px-4 py-2 text-sm rounded-xl
                          bg-blue-600 text-white
                          hover:bg-blue-700 transition">
                    Next >
                </a>
            @else
                <span class="px-4 py-2 text-sm rounded-xl
                             bg-slate-200 dark:bg-slate-800
                             text-slate-400 cursor-not-allowed">
                    Next >
                </span>
            @endif

        </div>

    </div>

</div>

@endsection
