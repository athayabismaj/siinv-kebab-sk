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
                Manajemen Menu
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kelola menu utama (harga ada di variant)
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.menus.archive') }}"
               class="px-4 py-2.5 rounded-xl
                      border border-slate-300 dark:border-slate-700
                      text-slate-700 dark:text-slate-200 text-sm font-medium
                      hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">
                Arsip Menu
            </a>

            <a href="{{ route('admin.menus.create') }}"
               class="px-5 py-2.5 rounded-xl
                      bg-blue-600 text-white text-sm font-medium
                      hover:bg-blue-700 transition text-center">
                + Tambah Menu
            </a>
        </div>

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

@forelse($menus as $menu)

    <div class="p-5 rounded-2xl
                bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800">

        <div class="flex justify-between items-start">

            <div class="flex gap-4">

                {{-- IMAGE --}}
                @if($menu->image_path)
                    <img src="{{ asset('storage/'.$menu->image_path) }}"
                         class="w-16 h-16 object-cover rounded-xl">
                @else
                    <div class="w-16 h-16 bg-slate-200 rounded-xl"></div>
                @endif

                <div>
                    <div class="font-medium text-slate-800 dark:text-white">
                        {{ $menu->name }}
                    </div>

                    <div class="text-xs text-slate-500 mt-1">
                        {{ $menu->category->name ?? '-' }}
                    </div>
                </div>

            </div>

            @if($menu->is_active)
                <span class="px-2 py-1 text-xs rounded-full
                             bg-emerald-100 text-emerald-700">
                    Aktif
                </span>
            @else
                <span class="px-2 py-1 text-xs rounded-full
                             bg-red-100 text-red-600">
                    Nonaktif
                </span>
            @endif

        </div>

        <div class="mt-4 text-sm text-slate-600 dark:text-slate-300">
            {{ $menu->variants_count }} variant
        </div>

        <div class="mt-2 text-xs text-slate-400">
            Urutan: {{ $menu->sort_order }}
        </div>

        <div class="mt-4 flex gap-6 text-sm">

            <a href="{{ route('admin.menu-variants.index', $menu->id) }}"
               class="text-indigo-600 hover:text-indigo-800 transition">
                Variant
            </a>

            <a href="{{ route('admin.menus.edit', $menu->id) }}"
               class="text-blue-600 hover:text-blue-800 transition">
                Edit
            </a>

            <form action="{{ route('admin.menus.destroy', $menu->id) }}"
                  method="POST">
                @csrf
                @method('DELETE')

                <button type="submit"
                        onclick="return confirm('Yakin?')"
                        class="text-red-600 hover:text-red-800 transition">
                    Hapus
                </button>
            </form>

        </div>

    </div>

@empty
    <div class="text-center text-slate-500 py-10">
        Tidak ada data menu.
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
    <th class="px-6 py-4 text-left">Menu</th>
    <th class="px-6 py-4 text-left">Kategori</th>
    <th class="px-6 py-4 text-left">Variant</th>
    <th class="px-6 py-4 text-left">Status</th>
    <th class="px-6 py-4 text-left">Urutan</th>
    <th class="px-6 py-4 text-left">Aksi</th>
</tr>
</thead>

<tbody>

@forelse($menus as $menu)

<tr class="border-b border-slate-100 dark:border-slate-800
           hover:bg-slate-50 dark:hover:bg-slate-800 transition">

<td class="px-6 py-6">

    <div class="flex items-center gap-4">

        @if($menu->image_path)
            <img src="{{ asset('storage/'.$menu->image_path) }}"
                 class="w-14 h-14 object-cover rounded-lg">
        @else
            <div class="w-14 h-14 bg-slate-200 rounded-lg"></div>
        @endif

        <span class="font-medium text-slate-800 dark:text-white">
            {{ $menu->name }}
        </span>

    </div>

</td>

<td class="px-6 py-6 text-slate-500">
    {{ $menu->category->name ?? '-' }}
</td>

<td class="px-6 py-6 text-slate-500">
    {{ $menu->variants_count }} variant
</td>

<td class="px-6 py-6">
    @if($menu->is_active)
        <span class="px-3 py-1 text-xs rounded-full
                     bg-emerald-100 text-emerald-700">
            Aktif
        </span>
    @else
        <span class="px-3 py-1 text-xs rounded-full
                     bg-red-100 text-red-600">
            Nonaktif
        </span>
    @endif
</td>

<td class="px-6 py-6 text-slate-500">
    {{ $menu->sort_order }}
</td>

<td class="px-6 py-6">
    <div class="flex gap-6 text-sm">

        <a href="{{ route('admin.menu-variants.index', $menu->id) }}"
           class="text-indigo-600 hover:text-indigo-800 transition">
            Variant
        </a>

        <a href="{{ route('admin.menus.edit', $menu->id) }}"
           class="text-blue-600 hover:text-blue-800 transition">
            Edit
        </a>

        <form action="{{ route('admin.menus.destroy', $menu->id) }}"
              method="POST">
            @csrf
            @method('DELETE')

            <button type="submit"
                    onclick="return confirm('Yakin?')"
                    class="text-red-600 hover:text-red-800 transition">
                Hapus
            </button>
        </form>

    </div>
</td>

</tr>

@empty
<tr>
    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
        Tidak ada data menu.
    </td>
</tr>
@endforelse

</tbody>
</table>

</div>


<div class="mt-8">
    {{ $menus->links() }}
</div>

@endsection
