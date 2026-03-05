@extends('layouts.app')

@section('title','Manajemen Stok')

@section('content')

<div class="w-full px-6 lg:px-10 space-y-10">

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
                Restok dan Penyesuaian
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Kelola stok bahan dapur
            </p>
        </div>

        <a href="{{ route('admin.stocks.logs') }}"
           class="inline-flex items-center justify-center rounded-xl
                  bg-slate-800 px-4 py-2 text-sm font-medium text-white
                  transition hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600">
            Riwayat Stok
        </a>
    </div>



    {{-- ================= SUCCESS ================= --}}
    @if(session('success'))
        <div class="px-4 py-3 rounded-xl
                    bg-emerald-50 text-emerald-700
                    border border-emerald-200 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="px-4 py-3 rounded-xl
                    bg-red-50 text-red-700
                    border border-red-200 text-sm">
            {{ session('error') }}
        </div>
    @endif



    {{-- ================= ALERT STOK RENDAH ================= --}}

    @if($lowStockCount > 0)
        <div class="px-4 py-3 rounded-xl
                    bg-amber-50 border border-amber-200
                    text-amber-700 text-sm">
            ! {{ $lowStockCount }} bahan memiliki stok rendah.
        </div>
    @endif



    {{-- ================= FILTER ================= --}}
    <form method="GET"
          class="flex flex-col md:flex-row md:items-center gap-3">

        {{-- SEARCH --}}
        <div class="relative flex-1">

            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari bahan..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-800 text-sm
                          focus:ring-2 focus:ring-blue-500">

            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>

        </div>


        {{-- CATEGORY --}}
        <select name="category"
                class="px-4 py-2.5 rounded-xl
                       border border-slate-300 dark:border-slate-700
                       bg-white dark:bg-slate-800 text-sm
                       focus:ring-2 focus:ring-blue-500">

            <option value="">Semua Kategori</option>

            @foreach($allCategories as $category)
                @php
                    $outCount = (int) ($category->out_of_stock_count ?? 0);
                    $lowCount = (int) ($category->low_stock_count ?? 0);
                @endphp
                <option value="{{ $category->id }}"
                    {{ request('category') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }} (H:{{ $outCount }} R:{{ $lowCount }})
                </option>

            @endforeach

        </select>


        {{-- BUTTON --}}
        <button type="submit"
                class="px-5 py-2.5 rounded-xl
                       bg-blue-600 text-white text-sm
                       hover:bg-blue-700 transition">
            Filter
        </button>


        {{-- RESET --}}
        @if(request()->filled('search') || request()->filled('category'))

            <a href="{{ route('admin.stocks.index') }}"
               class="text-sm text-slate-500 hover:text-blue-600 transition">
                Reset
            </a>

        @endif

    </form>



    {{-- ================= CATEGORY LIST ================= --}}
    <div x-data="{ openCategory: null }" class="space-y-6">

        @foreach($categories as $category)

            @if($category->ingredients->count())

                <div class="bg-white dark:bg-slate-900
                            border border-slate-200 dark:border-slate-800
                            rounded-2xl shadow-sm overflow-hidden">


                    {{-- CATEGORY HEADER --}}
                    <button
                        @click="openCategory === {{ $category->id }}
                                ? openCategory = null
                                : openCategory = {{ $category->id }}"
                        class="w-full px-6 py-4 flex justify-between items-center
                               text-left bg-slate-50 dark:bg-slate-800">

                        <span class="font-semibold text-slate-800 dark:text-white">
                            {{ $category->name }}
                        </span>

                        @php
                            $outCount = $category->ingredients->where('stock', '<=', 0)->count();
                            $lowCount = $category->ingredients
                                ->filter(fn($ing) => $ing->stock > 0 && $ing->stock <= $ing->minimum_stock)
                                ->count();
                        @endphp

                        <div class="flex items-center gap-2">
                            @if($outCount > 0)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                    H {{ $outCount }}
                                </span>
                            @endif

                            @if($lowCount > 0)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                    R {{ $lowCount }}
                                </span>
                            @endif

                            <svg
                                :class="openCategory === {{ $category->id }} ? 'rotate-180' : ''"
                                class="w-5 h-5 text-slate-400 transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M19 9l-7 7-7-7"/>

                            </svg>
                        </div>

                    </button>



                    {{-- INGREDIENT LIST --}}
                    <div
                        x-show="openCategory === {{ $category->id }}"
                        x-collapse
                        x-cloak
                        class="p-6 grid md:grid-cols-2 xl:grid-cols-3 gap-5">

                        @foreach($category->ingredients as $item)

                            @php

                                $progress = $item->minimum_stock > 0
                                    ? min(($item->stock / $item->minimum_stock) * 100,100)
                                    : 100;

                            @endphp


                            <div class="p-5 rounded-xl border
                                        border-slate-200 dark:border-slate-800
                                        space-y-3">


                                {{-- NAME --}}
                                <div class="flex justify-between items-center">

                                    <p class="font-medium text-slate-800 dark:text-white">
                                        {{ $item->name }}
                                    </p>

                                    <span class="text-sm text-slate-500">
                                        {{ $item->display_stock_value }} {{ $item->display_stock_unit }}
                                    </span>

                                </div>



                                {{-- PROGRESS --}}
                                <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800">

                                    <div
                                        class="h-2 rounded-full
                                        {{ $item->stock <= $item->minimum_stock ? 'bg-red-500' : 'bg-emerald-500' }}"
                                        style="width: {{ $progress }}%">
                                    </div>

                                </div>



                                {{-- MINIMUM --}}
                                <p class="text-xs text-slate-400">
                                    Minimum {{ $item->display_minimum_stock_value }} {{ $item->display_stock_unit }}
                                </p>



                                {{-- ACTION --}}
                                <div class="flex gap-2 pt-2">

                                    <a href="{{ route('admin.stocks.restock.form',$item->id) }}"
                                       class="flex-1 text-center px-3 py-2 text-xs rounded-lg
                                              bg-emerald-600 hover:bg-emerald-700 text-white">
                                        Restok
                                    </a>

                                    <a href="{{ route('admin.stocks.adjust.form',$item->id) }}"
                                       class="flex-1 text-center px-3 py-2 text-xs rounded-lg
                                              bg-amber-500 hover:bg-amber-600 text-white">
                                        Adjust
                                    </a>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endif

        @endforeach

    </div>



    {{-- ================= PAGINATION ================= --}}
    <div class="mt-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        {{-- INFO --}}
        <div class="text-sm text-slate-500 dark:text-slate-400 text-center md:text-left">
            Halaman
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $categories->currentPage() }}
            </span>
            dari
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $categories->lastPage() }}
            </span>
            | Total
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $categories->total() }}
            </span>
            kategori
        </div>


        {{-- BUTTON GROUP --}}
        <div class="flex justify-center md:justify-end gap-2">

            {{-- Previous --}}
            @if ($categories->onFirstPage())

                <span class="px-4 py-2 text-sm rounded-xl
                             bg-slate-200 dark:bg-slate-800
                             text-slate-400 cursor-not-allowed">
                    &lt; Previous
                </span>

            @else

                <a href="{{ $categories->previousPageUrl() }}"
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
            @if ($categories->hasMorePages())

                <a href="{{ $categories->nextPageUrl() }}"
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
