@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

{{-- ================= HEADER ================= --}}
<div class="mb-10">

    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-6">

        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
                Variant Menu
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ $menu->name }}
            </p>
        </div>

        <a href="{{ route('admin.menu-variants.create', $menu->id) }}"
           class="px-5 py-2.5 rounded-xl
                  bg-blue-600 text-white text-sm font-medium
                  hover:bg-blue-700 transition text-center">
            + Tambah Variant
        </a>

    </div>

</div>


@if(session('success'))
    <div class="mb-6 px-4 py-3 rounded-xl
                bg-emerald-50 text-emerald-700
                border border-emerald-200 text-sm">
        {{ session('success') }}
    </div>
@endif


{{-- ================= MOBILE VIEW ================= --}}
<div class="space-y-4 md:hidden">

@forelse($variants as $variant)

    <div class="p-5 rounded-2xl
                bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800">

        <div class="flex justify-between items-start">

            <div>
                <div class="font-medium text-slate-800 dark:text-white">
                    {{ $variant->name }}
                </div>

                <div class="text-sm text-slate-500 mt-1">
                    Rp {{ number_format($variant->price, 0, ',', '.') }}
                </div>
            </div>

            @if($variant->is_available)
                <span class="px-2 py-1 text-xs rounded-full
                             bg-emerald-100 text-emerald-700">
                    Available
                </span>
            @else
                <span class="px-2 py-1 text-xs rounded-full
                             bg-red-100 text-red-600">
                    Nonaktif
                </span>
            @endif

        </div>

        <div class="mt-4 text-xs text-slate-400">
            Urutan: {{ $variant->sort_order }}
        </div>

        <div class="mt-4 flex gap-6 text-sm">

            <a href="{{ route('admin.menu-variants.edit', [$menu->id, $variant->id]) }}"
               class="text-slate-500 hover:text-blue-600 transition">
                Edit
            </a>

            <form action="{{ route('admin.menu-variants.destroy', [$menu->id, $variant->id]) }}"
                  method="POST">
                @csrf
                @method('DELETE')

                <button type="submit"
                        onclick="return confirm('Yakin ingin menghapus variant ini?')"
                        class="text-slate-500 hover:text-red-600 transition">
                    Hapus
                </button>
            </form>

        </div>

    </div>

@empty
    <div class="text-center text-slate-500 py-10">
        Belum ada variant.
    </div>
@endforelse

</div>


{{-- ================= DESKTOP VIEW ================= --}}
<div class="hidden md:block
            bg-white dark:bg-slate-900
            rounded-2xl border border-slate-200 dark:border-slate-800
            overflow-hidden">

<table class="min-w-full text-sm">

<thead class="text-xs uppercase text-slate-400 border-b">
<tr>
    <th class="px-6 py-4 text-left">Nama</th>
    <th class="px-6 py-4 text-left">Harga</th>
    <th class="px-6 py-4 text-left">Status</th>
    <th class="px-6 py-4 text-left">Urutan</th>
    <th class="px-6 py-4 text-left">Aksi</th>
</tr>
</thead>

<tbody>

@forelse($variants as $variant)

<tr class="border-b border-slate-100 dark:border-slate-800
           hover:bg-slate-50 dark:hover:bg-slate-800 transition">

<td class="px-6 py-6 font-medium text-slate-800 dark:text-white">
    {{ $variant->name }}
</td>

<td class="px-6 py-6 text-slate-600 dark:text-slate-300">
    Rp {{ number_format($variant->price, 0, ',', '.') }}
</td>

<td class="px-6 py-6">
    @if($variant->is_available)
        <span class="px-3 py-1 text-xs rounded-full
                     bg-emerald-100 text-emerald-700">
            Available
        </span>
    @else
        <span class="px-3 py-1 text-xs rounded-full
                     bg-red-100 text-red-600">
            Nonaktif
        </span>
    @endif
</td>

<td class="px-6 py-6 text-slate-500">
    {{ $variant->sort_order }}
</td>

<td class="px-6 py-6">
    <div class="flex gap-6 text-sm">

        <a href="{{ route('admin.menu-variants.edit', [$menu->id, $variant->id]) }}"
           class="text-slate-500 hover:text-blue-600 transition">
            Edit
        </a>

        <form action="{{ route('admin.menu-variants.destroy', [$menu->id, $variant->id]) }}"
              method="POST">
            @csrf
            @method('DELETE')

            <button type="submit"
                    onclick="return confirm('Yakin ingin menghapus variant ini?')"
                    class="text-slate-500 hover:text-red-600 transition">
                Hapus
            </button>
        </form>

    </div>
</td>

</tr>

@empty
<tr>
    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
        Belum ada variant.
    </td>
</tr>
@endforelse

</tbody>
</table>

</div>


<div class="mt-8">
    <a href="{{ route('admin.menus.index') }}"
       class="text-sm text-slate-500 hover:text-blue-600 transition">
        ← Kembali ke Menu
    </a>
</div>

@endsection