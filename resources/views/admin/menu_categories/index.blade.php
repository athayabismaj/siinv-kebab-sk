@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

{{-- ════ HEADER ════ --}}
<div class="mb-8">

    <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
        <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <span class="text-slate-600 dark:text-slate-300">Menu & Resep</span>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <span class="text-slate-600 dark:text-slate-300">Kategori Menu</span>
    </nav>

    <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
        Kategori Menu
    </h1>
    
    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
        Kelola pengelompokan menu untuk sistem POS. Kategori memudahkan kasir menemukan menu yang tepat saat transaksi.
    </p>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.menu-categories.create') }}"
           class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-[13px] font-bold rounded-xl active:scale-95 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            Tambah Kategori
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
                    {{ $category->menus()->count() }} menu
                </div>
            </div>

            <span class="px-3 py-1 text-xs rounded-full
                         bg-slate-100 dark:bg-slate-800
                         text-slate-600 dark:text-slate-300">
                {{ $category->menus()->count() }}
            </span>

        </div>

        <div class="mt-4 flex gap-6 text-sm">
            <a href="{{ route('admin.menu-categories.edit', $category->id) }}"
               class="text-slate-500 hover:text-blue-600 transition">
                Edit
            </a>

            <form action="{{ route('admin.menu-categories.destroy', $category->id) }}"
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
        Belum ada kategori menu.
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
    <th class="px-6 py-4 text-left">Jumlah Menu</th>
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
        {{ $category->menus()->count() }} menu
    </span>
</td>

<td class="px-6 py-6">
    <div class="flex gap-6 text-sm">
        <a href="{{ route('admin.menu-categories.edit', $category->id) }}"
           class="text-slate-500 hover:text-blue-600 transition">
            Edit
        </a>

        <form action="{{ route('admin.menu-categories.destroy', $category->id) }}"
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
        Belum ada kategori menu.
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