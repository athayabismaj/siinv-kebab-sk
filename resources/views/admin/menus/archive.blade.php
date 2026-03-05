@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
                Arsip Menu
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Daftar menu yang dinonaktifkan dan bisa diaktifkan kembali.
            </p>
        </div>

        <a href="{{ route('admin.menus.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-xl
                  border border-slate-300 dark:border-slate-700
                  text-sm text-slate-700 dark:text-slate-200
                  hover:bg-slate-100 dark:hover:bg-slate-800 transition">
            Kembali ke Menu Aktif
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

<form method="GET"
      action="{{ route('admin.menus.archive') }}"
      class="mb-8 flex flex-col md:flex-row md:items-center gap-3">

    <input type="text"
           name="search"
           value="{{ request('search') }}"
           placeholder="Cari menu nonaktif..."
           class="w-full md:flex-1 px-4 py-2.5 rounded-xl
                  border border-slate-300 dark:border-slate-700
                  bg-white dark:bg-slate-800 text-sm
                  focus:ring-2 focus:ring-blue-500">

    <select name="category"
            class="px-4 py-2.5 rounded-xl
                   border border-slate-300 dark:border-slate-700
                   bg-white dark:bg-slate-800 text-sm
                   focus:ring-2 focus:ring-blue-500">
        <option value="">Semua Kategori</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}"
                {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>
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

    @if(request()->filled('search') || request()->filled('category'))
        <a href="{{ route('admin.menus.archive') }}"
           class="text-sm text-slate-500 hover:text-blue-600 transition">
            Reset
        </a>
    @endif
</form>

<div class="bg-white dark:bg-slate-900
            rounded-2xl border border-slate-200 dark:border-slate-800
            overflow-hidden">

    <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-6 py-4 text-left">Menu</th>
                    <th class="px-6 py-4 text-left">Kategori</th>
                    <th class="px-6 py-4 text-left">Variant</th>
                    <th class="px-6 py-4 text-left">Dinonaktifkan</th>
                    <th class="px-6 py-4 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($menus as $menu)
                    <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($menu->image_path)
                                    <img src="{{ asset('storage/'.$menu->image_path) }}"
                                         alt="{{ $menu->name }}"
                                         class="w-10 h-10 rounded-lg object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-slate-200 dark:bg-slate-700"></div>
                                @endif
                                <span class="font-medium text-slate-800 dark:text-white">
                                    {{ $menu->name }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            {{ $menu->category->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            {{ $menu->variants_count }} variant
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            {{ optional($menu->deleted_at)->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <form action="{{ route('admin.menus.restore', $menu->id) }}"
                                  method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        onclick="return confirm('Aktifkan kembali menu ini?')"
                                        class="text-blue-600 hover:underline transition">
                                    Aktifkan
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            Tidak ada menu nonaktif.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="block md:hidden divide-y divide-slate-200 dark:divide-slate-800">
        @forelse($menus as $menu)
            <div class="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        @if($menu->image_path)
                            <img src="{{ asset('storage/'.$menu->image_path) }}"
                                 alt="{{ $menu->name }}"
                                 class="w-12 h-12 rounded-lg object-cover">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-slate-200 dark:bg-slate-700"></div>
                        @endif
                        <div>
                            <div class="font-medium text-slate-800 dark:text-white">
                                {{ $menu->name }}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                {{ $menu->category->name ?? '-' }} • {{ $menu->variants_count }} variant
                            </div>
                        </div>
                    </div>

                    <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-600">
                        Nonaktif
                    </span>
                </div>

                <div class="text-xs text-slate-400 mt-3">
                    Dinonaktifkan: {{ optional($menu->deleted_at)->format('d M Y H:i') }}
                </div>

                <div class="mt-4">
                    <form action="{{ route('admin.menus.restore', $menu->id) }}"
                          method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                onclick="return confirm('Aktifkan kembali menu ini?')"
                                class="text-sm text-blue-600 hover:underline transition">
                            Aktifkan
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-slate-500">
                Tidak ada menu nonaktif.
            </div>
        @endforelse
    </div>
</div>

<div class="mt-8">
    {{ $menus->links() }}
</div>

@endsection
