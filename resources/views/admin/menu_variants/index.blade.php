@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="mb-8">

    <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
        <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <a href="{{ route('admin.menus.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Menu & Resep</a>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <span class="text-slate-600 dark:text-slate-300">{{ $menu->name }}</span>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <span class="text-slate-600 dark:text-slate-300">Variant</span>
    </nav>

    <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
        Variant Menu
    </h1>

    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
        Kelola varian dari <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $menu->name }}</span>. Setiap varian memiliki harga modal dan harga jual.
    </p>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.menu-variants.create', $menu->id) }}"
           class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-[13px] font-bold rounded-xl active:scale-95 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            Tambah Variant
        </a>
        <a href="{{ route('admin.menus.index') }}"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-[13px] font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Menu
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

                <div class="text-xs text-slate-500 mt-1">
                    Modal: Rp {{ number_format($variant->cost_price ?? 0, 0, ',', '.') }}
                </div>
                <div class="text-sm text-slate-500 mt-1">
                    Jual: Rp {{ number_format($variant->sell_price ?? $variant->price, 0, ',', '.') }}
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

<div class="hidden md:block
            bg-white dark:bg-slate-900
            rounded-2xl border border-slate-200 dark:border-slate-800
            overflow-hidden">

<table class="min-w-full text-sm">

<thead class="text-xs uppercase text-slate-400 border-b">
<tr>
    <th class="px-6 py-4 text-left">Nama</th>
    <th class="px-6 py-4 text-left">Harga Modal</th>
    <th class="px-6 py-4 text-left">Harga Jual</th>
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
    Rp {{ number_format($variant->cost_price ?? 0, 0, ',', '.') }}
</td>

<td class="px-6 py-6 text-slate-600 dark:text-slate-300">
    Rp {{ number_format($variant->sell_price ?? $variant->price, 0, ',', '.') }}
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
    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
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
        &larr; Kembali ke Menu
    </a>
</div>

@endsection
