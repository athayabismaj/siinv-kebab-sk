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
                Manajemen Kategori Bahan
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Kelola kategori bahan untuk pengelompokan inventory
            </p>
        </div>

        <a href="{{ route('admin.ingredient-categories.create') }}"
           class="px-5 py-2.5 rounded-xl
                  bg-blue-600 text-white text-sm font-medium
                  hover:bg-blue-700 transition">
            + Tambah Kategori
        </a>

    </div>

</div>


{{-- SUCCESS --}}
@if(session('success'))
    <div class="mb-6 px-4 py-3 rounded-xl
                bg-emerald-50 text-emerald-700
                border border-emerald-200 text-sm">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-6 px-4 py-3 rounded-xl
                bg-red-50 text-red-700
                border border-red-200 text-sm">
        {{ $errors->first() }}
    </div>
@endif


{{-- ================= MOBILE ================= --}}
<div class="space-y-4 md:hidden">

@forelse($categories as $category)

    <div class="bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800
                rounded-2xl p-6 shadow-sm">

        <div class="flex justify-between items-center">

            <div>
                <div class="font-semibold text-slate-800 dark:text-white">
                    {{ $category->name }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{ $category->ingredients()->count() }} bahan
                </div>
            </div>

            <span class="px-3 py-1 text-xs rounded-full
                         bg-slate-100 dark:bg-slate-800
                         text-slate-600 dark:text-slate-300">
                {{ $category->ingredients()->count() }}
            </span>

        </div>

        <div class="mt-4 flex gap-6 text-sm">
            <a href="{{ route('admin.ingredient-categories.edit', $category->id) }}"
               class="text-slate-500 hover:text-blue-600 transition">
                Edit
            </a>

            <form action="{{ route('admin.ingredient-categories.destroy', $category->id) }}"
                  method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                        onclick="return confirm('Yakin ingin menghapus kategori ini?')"
                        class="text-slate-500 hover:text-red-600 transition">
                    Hapus
                </button>
            </form>
        </div>

    </div>

@empty
    <div class="text-center text-slate-500 py-12">
        Belum ada kategori.
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
    <th class="px-6 py-4 text-left">Nama Kategori</th>
    <th class="px-6 py-4 text-left">Jumlah Bahan</th>
    <th class="px-6 py-4 text-left">Aksi</th>
</tr>
</thead>

<tbody>

@forelse($categories as $category)

<tr class="border-b border-slate-100 dark:border-slate-800
           hover:bg-slate-50 dark:hover:bg-slate-800 transition">

<td class="px-6 py-6 font-medium text-slate-800 dark:text-white">
    {{ $category->name }}
</td>

<td class="px-6 py-6">
    <span class="px-3 py-1 text-xs rounded-full
                 bg-slate-100 dark:bg-slate-800
                 text-slate-600 dark:text-slate-300">
        {{ $category->ingredients()->count() }} bahan
    </span>
</td>

<td class="px-6 py-6">
    <div class="flex gap-6 text-sm">
        <a href="{{ route('admin.ingredient-categories.edit', $category->id) }}"
           class="text-slate-500 hover:text-blue-600 transition">
            Edit
        </a>

        <form action="{{ route('admin.ingredient-categories.destroy', $category->id) }}"
              method="POST">
            @csrf
            @method('DELETE')
            <button type="submit"
                    onclick="return confirm('Yakin ingin menghapus kategori ini?')"
                    class="text-slate-500 hover:text-red-600 transition">
                Hapus
            </button>
        </form>
    </div>
</td>

</tr>

@empty
<tr>
    <td colspan="3" class="px-6 py-12 text-center text-slate-500">
        Belum ada kategori.
    </td>
</tr>
@endforelse

</tbody>
</table>

</div>

<div class="mt-8">
    {{ $categories->links() }}
</div>

@endsection