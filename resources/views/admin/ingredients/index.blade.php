@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

@php
    function convertDisplay($ingredient) {
        if (in_array($ingredient->display_unit, ['kg','l'])) {
            return [
                'stock' => $ingredient->stock / 1000,
                'minimum' => $ingredient->minimum_stock / 1000,
            ];
        }

        return [
            'stock' => $ingredient->stock,
            'minimum' => $ingredient->minimum_stock,
        ];
    }
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <h1 class="text-xl font-semibold text-slate-800 dark:text-white">
        Manajemen Bahan
    </h1>

    <a href="{{ route('admin.ingredients.create') }}"
       class="bg-blue-600 hover:bg-blue-700 text-white
              px-4 py-2 rounded-xl text-sm transition w-full sm:w-auto text-center">
        + Tambah Bahan
    </a>
</div>

@if(session('success'))
    <div class="mb-6 p-3 text-sm rounded-xl
                bg-green-50 text-green-700
                border border-green-200">
        {{ session('success') }}
    </div>
@endif


{{-- ================= MOBILE ================= --}}
<div class="space-y-4 sm:hidden">

@forelse($ingredients as $ingredient)

    @php
        $converted = convertDisplay($ingredient);
    @endphp

    <div class="bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800
                rounded-2xl p-5 shadow-sm">

        <div class="flex justify-between items-start">
            <div>
                <div class="font-medium text-slate-800 dark:text-white">
                    {{ $ingredient->name }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $ingredient->display_unit }}
                </div>
            </div>

            @if($converted['stock'] <= 0)
                <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-600">
                    Habis
                </span>
            @elseif($converted['stock'] <= $converted['minimum'])
                <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-600">
                    Hampir Habis
                </span>
            @else
                <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-600">
                    Aman
                </span>
            @endif
        </div>

        <div class="mt-4 text-sm text-slate-600 dark:text-slate-300">
            Stok: <span class="font-medium">
                {{ $converted['stock'] }} {{ $ingredient->display_unit }}
            </span>
        </div>

        <div class="text-sm text-slate-500">
            Minimum: {{ $converted['minimum'] }} {{ $ingredient->display_unit }}
        </div>

        <div class="mt-4 flex gap-4 text-sm">
            <a href="{{ route('admin.ingredients.edit', $ingredient->id) }}"
               class="text-slate-500 hover:text-blue-600 transition">
                Edit
            </a>

            <form action="{{ route('admin.ingredients.destroy', $ingredient->id) }}"
                  method="POST">
                @csrf
                @method('DELETE')

                <button type="submit"
                        onclick="return confirm('Yakin ingin menonaktifkan bahan ini?')"
                        class="text-slate-500 hover:text-blue-600 transition">
                    Nonaktifkan
                </button>
            </form>
        </div>

    </div>

@empty
    <div class="text-center text-slate-500 text-sm py-8">
        Tidak ada data bahan.
    </div>
@endforelse

</div>


{{-- ================= DESKTOP ================= --}}
<div class="hidden sm:block bg-white dark:bg-slate-900
            rounded-2xl border border-slate-200 dark:border-slate-800
            shadow-sm overflow-hidden">

<table class="min-w-full text-sm">

<thead class="text-xs uppercase text-slate-400
              border-b border-slate-200 dark:border-slate-800">
    <tr>
        <th class="px-6 py-4 text-left">Nama</th>
        <th class="px-6 py-4 text-left">Satuan</th>
        <th class="px-6 py-4 text-left">Stok</th>
        <th class="px-6 py-4 text-left">Minimum</th>
        <th class="px-6 py-4 text-left">Status</th>
        <th class="px-6 py-4 text-left">Aksi</th>
    </tr>
</thead>

<tbody>

@forelse($ingredients as $ingredient)

    @php
        $converted = convertDisplay($ingredient);
    @endphp

<tr class="border-b border-slate-100 dark:border-slate-800
           hover:bg-slate-50 dark:hover:bg-slate-800 transition">

    <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">
        {{ $ingredient->name }}
    </td>

    <td class="px-6 py-4 text-slate-500">
        {{ $ingredient->display_unit }}
    </td>

    <td class="px-6 py-4 text-slate-500">
        {{ $converted['stock'] }} {{ $ingredient->display_unit }}
    </td>

    <td class="px-6 py-4 text-slate-500">
        {{ $converted['minimum'] }} {{ $ingredient->display_unit }}
    </td>

    <td class="px-6 py-4">
        @if($converted['stock'] <= 0)
            <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-600">
                Habis
            </span>
        @elseif($converted['stock'] <= $converted['minimum'])
            <span class="text-xs px-2 py-1 rounded-full bg-yellow-100 text-yellow-600">
                Hampir Habis
            </span>
        @else
            <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-600">
                Aman
            </span>
        @endif
    </td>

    <td class="px-6 py-4">
        <div class="flex gap-4 text-sm">
            <a href="{{ route('admin.ingredients.edit', $ingredient->id) }}"
               class="text-slate-500 hover:text-blue-600 transition">
                Edit
            </a>

            <form action="{{ route('admin.ingredients.destroy', $ingredient->id) }}"
                  method="POST">
                @csrf
                @method('DELETE')

                <button type="submit"
                        onclick="return confirm('Yakin ingin menonaktifkan bahan ini?')"
                        class="text-slate-500 hover:text-blue-600 transition">
                    Nonaktifkan
                </button>
            </form>
        </div>
    </td>

</tr>

@empty
<tr>
    <td colspan="6" class="px-6 py-10 text-center text-slate-500">
        Tidak ada data bahan.
    </td>
</tr>
@endforelse

</tbody>
</table>
</div>

@if(method_exists($ingredients, 'links'))
    <div class="mt-6">
        {{ $ingredients->links() }}
    </div>
@endif

@endsection