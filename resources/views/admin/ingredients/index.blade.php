@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

@php
    function formatStock($value) {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    function stockPercent($stock, $minimum) {
        if ($minimum <= 0) return 100;
        return min(100, ($stock / ($minimum * 2)) * 100);
    }
@endphp


{{-- ================= HEADER ================= --}}
<div class="mb-10">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-6">

        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
                Manajemen Bahan
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kelola stok bahan secara real-time
            </p>
        </div>

        <a href="{{ route('admin.ingredients.create') }}"
           class="px-5 py-2.5 rounded-xl
                  bg-blue-600 text-white text-sm font-medium
                  hover:bg-blue-700 transition">
            + Tambah Bahan
        </a>

    </div>
</div>


{{-- ================= SUCCESS ================= --}}
@if(session('success'))
    <div class="mb-6 px-4 py-3 rounded-xl
                bg-emerald-50 text-emerald-700
                border border-emerald-200 text-sm">
        {{ session('success') }}
    </div>
@endif


{{-- ================= ALERT STOK RENDAH ================= --}}
@php
    $lowStockCount = $ingredients->filter(function ($ingredient) {
        return $ingredient->converted_stock <= $ingredient->converted_minimum_stock;
    })->count();
@endphp

@if($lowStockCount > 0)
    <div class="mb-8 px-4 py-3 rounded-xl
                bg-amber-50 border border-amber-200
                text-amber-700 text-sm">
        ⚠ {{ $lowStockCount }} bahan memiliki stok rendah.
    </div>
@endif


{{-- ================= FILTER ================= --}}
<form method="GET"
      class="mb-10 flex flex-col md:flex-row md:items-center gap-3">

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
             fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
        </svg>
    </div>

    <select name="category"
            class="px-4 py-2.5 rounded-xl
                   border border-slate-300 dark:border-slate-700
                   bg-white dark:bg-slate-800 text-sm
                   focus:ring-2 focus:ring-blue-500">

        <option value="">Semua Kategori</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}"
                {{ request('category') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>

    <button type="submit"
            class="px-5 py-2.5 rounded-xl
                   bg-blue-600 text-white text-sm
                   hover:bg-blue-700 transition">
        Filter
    </button>

    @if(request()->has('search') || request()->has('category'))
        <a href="{{ route('admin.ingredients.index') }}"
           class="text-sm text-slate-500 hover:text-blue-600 transition">
            Reset
        </a>
    @endif

</form>


{{-- ================= MOBILE ================= --}}
<div class="space-y-5 md:hidden">

@forelse($ingredients as $ingredient)

    @php
        $stock = $ingredient->converted_stock;
        $minimum = $ingredient->converted_minimum_stock;
        $percent = stockPercent($stock, $minimum);
    @endphp

    <div class="bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800
                rounded-2xl p-6 shadow-sm">

        <div class="flex justify-between items-start mb-4">

            <div>
                <div class="text-base font-semibold text-slate-800 dark:text-white">
                    {{ $ingredient->name }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $ingredient->category->name ?? '-' }}
                </div>
            </div>

            @if($stock <= 0)
                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-600">
                    Habis
                </span>
            @elseif($stock <= $minimum)
                <span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-600">
                    Rendah
                </span>
            @else
                <span class="px-2 py-1 text-xs rounded-full bg-emerald-100 text-emerald-600">
                    Aman
                </span>
            @endif
        </div>

        <div class="mb-4">
            <div class="text-xl font-semibold text-slate-800 dark:text-white">
                {{ formatStock($stock) }}
                <span class="text-sm font-normal text-slate-500">
                    {{ $ingredient->display_unit }}
                </span>
            </div>

            <div class="text-xs text-slate-400 mt-1">
                Minimum: {{ formatStock($minimum) }}
            </div>
        </div>

        <div class="w-full bg-slate-100 dark:bg-slate-800
                    h-2 rounded-full overflow-hidden mb-4">
            <div class="h-full rounded-full transition-all duration-500
                {{ $stock <= 0 ? 'bg-red-500' :
                   ($stock <= $minimum ? 'bg-amber-500' : 'bg-emerald-500') }}"
                style="width: {{ $percent }}%">
            </div>
        </div>

        <div class="flex gap-6 text-sm">
            <a href="{{ route('admin.ingredients.edit', $ingredient->id) }}"
               class="text-slate-500 hover:text-blue-600 transition">
                Edit
            </a>

            <form action="{{ route('admin.ingredients.destroy', $ingredient->id) }}"
                  method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                        onclick="return confirm('Nonaktifkan bahan ini?')"
                        class="text-slate-500 hover:text-red-600 transition">
                    Nonaktifkan
                </button>
            </form>
        </div>

    </div>

@empty
    <div class="text-center text-slate-500 py-12">
        Tidak ada data bahan.
    </div>
@endforelse

</div>


{{-- ================= DESKTOP ================= --}}
<div class="hidden md:block
            bg-white dark:bg-slate-900
            rounded-2xl border border-slate-200 dark:border-slate-800
            overflow-hidden">

<table class="min-w-full text-sm">

<thead class="text-xs uppercase text-slate-400 border-b">
<tr>
    <th class="px-6 py-4 text-left">Nama</th>
    <th class="px-6 py-4 text-left">Stok</th>
    <th class="px-6 py-4 text-left">Status</th>
    <th class="px-6 py-4 text-left">Aksi</th>
</tr>
</thead>

<tbody>

@forelse($ingredients as $ingredient)

@php
    $stock = $ingredient->converted_stock;
    $minimum = $ingredient->converted_minimum_stock;
    $percent = stockPercent($stock, $minimum);
@endphp

<tr class="border-b border-slate-100 dark:border-slate-800
           hover:bg-slate-50 dark:hover:bg-slate-800 transition">

<td class="px-6 py-6">
    <div class="font-medium text-slate-800 dark:text-white">
        {{ $ingredient->name }}
    </div>
    <div class="text-xs text-slate-400 mt-1">
        {{ $ingredient->category->name ?? '-' }}
    </div>
</td>

<td class="px-6 py-6 w-72">

    <div class="text-lg font-semibold text-slate-800 dark:text-white">
        {{ formatStock($stock) }}
        <span class="text-sm font-normal text-slate-500">
            {{ $ingredient->display_unit }}
        </span>
    </div>

    <div class="text-xs text-slate-400 mt-1 mb-3">
        Minimum: {{ formatStock($minimum) }}
    </div>

    <div class="w-full bg-slate-100 dark:bg-slate-800
                h-2 rounded-full overflow-hidden">
        <div class="h-full rounded-full transition-all duration-500
            {{ $stock <= 0 ? 'bg-red-500' :
               ($stock <= $minimum ? 'bg-amber-500' : 'bg-emerald-500') }}"
            style="width: {{ $percent }}%">
        </div>
    </div>

</td>

<td class="px-6 py-6">
    @if($stock <= 0)
        <span class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-600">
            Habis
        </span>
    @elseif($stock <= $minimum)
        <span class="px-3 py-1 text-xs rounded-full bg-amber-100 text-amber-600">
            Stok Rendah
        </span>
    @else
        <span class="px-3 py-1 text-xs rounded-full bg-emerald-100 text-emerald-600">
            Aman
        </span>
    @endif
</td>

<td class="px-6 py-6">
    <div class="flex gap-6 text-sm">
        <a href="{{ route('admin.ingredients.edit', $ingredient->id) }}"
           class="text-slate-500 hover:text-blue-600 transition">
            Edit
        </a>

        <form action="{{ route('admin.ingredients.destroy', $ingredient->id) }}"
              method="POST">
            @csrf
            @method('DELETE')
            <button type="submit"
                    onclick="return confirm('Nonaktifkan bahan ini?')"
                    class="text-slate-500 hover:text-red-600 transition">
                Nonaktifkan
            </button>
        </form>
    </div>
</td>

</tr>

@empty
<tr>
    <td colspan="4" class="px-6 py-12 text-center text-slate-500">
        Tidak ada data bahan.
    </td>
</tr>
@endforelse

</tbody>
</table>

</div>

<div class="mt-8">
    {{ $ingredients->links() }}
</div>

@endsection