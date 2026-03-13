@extends('layouts.app')

@section('title', 'Monitoring Stok')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

@php
    function formatStockOwner($value)
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    function stockPercentOwner($stock, $minimum)
    {
        if ($minimum <= 0) return 100;
        return min(100, ($stock / ($minimum * 2)) * 100);
    }
@endphp

<div class="space-y-8">

    {{-- ================= HEADER ================= --}}
    <div class="space-y-6">

        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
                Monitoring Stok
            </h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Pantau stok bahan secara real-time (read only)
            </p>
        </div>

        {{-- ================= STAT CARDS ================= --}}
        <div class="grid grid-cols-3 gap-3 md:max-w-md">

            <div class="px-4 py-3 text-center rounded-xl
                        bg-white dark:bg-slate-900
                        border border-slate-200 dark:border-slate-800">

                <p class="text-[11px] uppercase text-slate-500">Total</p>

                <p class="text-lg font-semibold text-slate-800 dark:text-slate-100">
                    {{ number_format($summary['total']) }}
                </p>
            </div>

            <div class="px-4 py-3 text-center rounded-xl
                        bg-amber-50 dark:bg-amber-900/10
                        border border-amber-200 dark:border-amber-900/40">

                <p class="text-[11px] uppercase text-amber-700 dark:text-amber-300">
                    Rendah
                </p>

                <p class="text-lg font-semibold text-amber-700 dark:text-amber-300">
                    {{ number_format($summary['low']) }}
                </p>
            </div>

            <div class="px-4 py-3 text-center rounded-xl
                        bg-red-50 dark:bg-red-900/10
                        border border-red-200 dark:border-red-900/40">

                <p class="text-[11px] uppercase text-red-700 dark:text-red-300">
                    Habis
                </p>

                <p class="text-lg font-semibold text-red-700 dark:text-red-300">
                    {{ number_format($summary['out']) }}
                </p>
            </div>

        </div>

    </div>


    {{-- ================= ALERT ================= --}}
    @if ($summary['low'] > 0)
        <div class="px-4 py-3 text-sm rounded-xl
                    bg-amber-50 border border-amber-200 text-amber-700">
            {{ $summary['low'] }} bahan memiliki stok rendah / habis.
        </div>
    @endif


    {{-- ================= FILTER ================= --}}
    <form method="GET"
          class="flex flex-col md:flex-row md:items-center gap-3
                 md:rounded-2xl md:border md:border-slate-200 md:dark:border-slate-800
                 md:bg-white md:dark:bg-slate-900 md:p-4">

        {{-- SEARCH --}}
        <div class="relative flex-1">

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari bahan..."
                class="w-full pl-10 pr-4 py-2.5 rounded-xl
                       border border-slate-300 dark:border-slate-700
                       bg-white dark:bg-slate-800 text-sm
                       focus:ring-2 focus:ring-blue-500"
            >

            <svg
                class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"
                />
            </svg>

        </div>


        {{-- CATEGORY --}}
        <select
            name="category"
            class="px-4 py-2.5 rounded-xl
                   border border-slate-300 dark:border-slate-700
                   bg-white dark:bg-slate-800 text-sm"
        >

            <option value="">Semua Kategori</option>

            @foreach ($categories as $cat)

                <option
                    value="{{ $cat->id }}"
                    {{ (string) request('category') === (string) $cat->id ? 'selected' : '' }}
                >
                    {{ $cat->name }}
                </option>

            @endforeach

        </select>


        {{-- BUTTON --}}
        <button
            type="submit"
            class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700"
        >
            Filter
        </button>


        {{-- RESET --}}
        @if (request()->has('search') || request()->has('category'))

            <a
                href="{{ route('owner.stocks.index') }}"
                class="text-sm text-slate-500 hover:text-blue-600"
            >
                Reset
            </a>

        @endif

    </form>


    {{-- ================= MOBILE CARD ================= --}}
    <div class="space-y-5 md:hidden">

        @forelse ($ingredients as $ingredient)

            @php
                $stock = $ingredient->converted_stock;
                $minimum = $ingredient->converted_minimum_stock;
                $percent = stockPercentOwner($stock, $minimum);
            @endphp

            <div class="p-6 rounded-2xl
                        bg-white dark:bg-slate-900
                        border border-slate-200 dark:border-slate-800">

                {{-- HEADER --}}
                <div class="flex justify-between items-start mb-4">

                    <div>

                        <div class="text-base font-semibold text-slate-800 dark:text-white">
                            {{ $ingredient->name }}
                        </div>

                        <div class="text-xs text-slate-500 mt-1">
                            {{ $ingredient->category->name ?? '-' }}
                        </div>

                    </div>

                    {{-- STATUS --}}
                    @if ($stock <= 0)

                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-600">
                            Habis
                        </span>

                    @elseif ($stock <= $minimum)

                        <span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-600">
                            Rendah
                        </span>

                    @else

                        <span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-600">
                            Aman
                        </span>

                    @endif

                </div>


                {{-- STOCK --}}
                <div class="mb-4">

                    <div class="text-xl font-semibold text-slate-800 dark:text-white">

                        {{ formatStockOwner($stock) }}

                        <span class="text-sm text-slate-500">
                            {{ $ingredient->display_stock_unit }}
                        </span>

                    </div>

                    <div class="text-xs text-slate-400 mt-1">

                        Minimum:
                        {{ formatStockOwner($minimum) }}
                        {{ $ingredient->display_stock_unit }}

                    </div>

                </div>


                {{-- PROGRESS --}}
                <div class="w-full h-2 mb-4 rounded-full overflow-hidden
                            bg-slate-100 dark:bg-slate-800">

                    <div
                        class="h-full rounded-full
                        {{ $stock <= 0 ? 'bg-red-500' : ($stock <= $minimum ? 'bg-amber-500' : 'bg-emerald-500') }}"
                        style="width: {{ $percent }}%"
                    ></div>

                </div>


                {{-- UPDATE --}}
                <div class="text-xs text-slate-500">

                    Update:
                    {{ $ingredient->updated_at?->format('d M Y H:i') ?? '-' }}

                </div>

            </div>

        @empty

            <div class="py-12 text-center text-slate-500">
                Tidak ada data bahan
            </div>

        @endforelse

    </div>


    {{-- ================= DESKTOP TABLE ================= --}}
    <div class="hidden md:block rounded-3xl overflow-hidden
                bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800">

        <table class="min-w-full text-sm">

            <thead class="text-xs uppercase tracking-wide
                          text-slate-500 dark:text-slate-400
                          bg-slate-50/80 dark:bg-slate-800/60
                          border-b border-slate-200 dark:border-slate-800">

                <tr>
                    <th class="px-6 py-4 text-left">Nama</th>
                    <th class="px-6 py-4 text-left">Stok</th>
                    <th class="px-6 py-4 text-left">Status</th>
                    <th class="px-6 py-4 text-right">Update</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($ingredients as $ingredient)

                    @php
                        $stock = $ingredient->converted_stock;
                        $minimum = $ingredient->converted_minimum_stock;
                        $percent = stockPercentOwner($stock, $minimum);
                    @endphp

                    <tr class="border-b border-slate-100 dark:border-slate-800
                               hover:bg-slate-50 dark:hover:bg-slate-800/60">

                        {{-- NAME --}}
                        <td class="px-6 py-6">

                            <div class="font-medium text-slate-800 dark:text-white">
                                {{ $ingredient->name }}
                            </div>

                            <div class="text-xs text-slate-400 mt-1">
                                {{ $ingredient->category->name ?? '-' }}
                            </div>

                        </td>


                        {{-- STOCK --}}
                        <td class="px-6 py-6 w-72">

                            <div class="text-lg font-semibold text-slate-800 dark:text-white">

                                {{ formatStockOwner($stock) }}

                                <span class="text-sm text-slate-500">
                                    {{ $ingredient->display_stock_unit }}
                                </span>

                            </div>

                            <div class="text-xs text-slate-400 mt-1 mb-3">

                                Minimum:
                                {{ formatStockOwner($minimum) }}
                                {{ $ingredient->display_stock_unit }}

                            </div>

                            <div class="w-full h-2 rounded-full overflow-hidden
                                        bg-slate-100 dark:bg-slate-800">

                                <div
                                    class="h-full rounded-full
                                    {{ $stock <= 0 ? 'bg-red-500' : ($stock <= $minimum ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                    style="width: {{ $percent }}%"
                                ></div>

                            </div>

                        </td>


                        {{-- STATUS --}}
                        <td class="px-6 py-6">

                            @if ($stock <= 0)

                                <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-600">
                                    Habis
                                </span>

                            @elseif ($stock <= $minimum)

                                <span class="px-3 py-1 text-xs rounded-full bg-amber-100 text-amber-600">
                                    Stok Rendah
                                </span>

                            @else

                                <span class="px-3 py-1 text-xs rounded-full bg-emerald-100 text-emerald-600">
                                    Aman
                                </span>

                            @endif

                        </td>


                        {{-- UPDATE --}}
                        <td class="px-6 py-6 text-right text-slate-500">

                            {{ $ingredient->updated_at?->format('d M Y H:i') ?? '-' }}

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            Tidak ada data bahan
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- PAGINATION --}}
    <div class="mt-8 md:rounded-2xl
                md:border md:border-slate-200 md:dark:border-slate-800
                md:bg-white md:dark:bg-slate-900 md:p-3">

        {{ $ingredients->links() }}

    </div>

</div>

@endsection