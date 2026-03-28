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
        <span class="text-slate-600 dark:text-slate-300">Inventori</span>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <a href="{{ route('admin.ingredients.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Manajemen Bahan</a>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <span class="text-slate-600 dark:text-slate-300">Arsip</span>
    </nav>

    <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
        Arsip Bahan
    </h1>

    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
        Daftar bahan yang telah dinonaktifkan. Anda dapat memulihkan bahan di bawah ini jika diperlukan.
    </p>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.ingredients.index') }}"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-[13px] font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Bahan Aktif
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
      action="{{ route('admin.ingredients.archive') }}"
      class="mb-8 flex flex-col md:flex-row md:items-center gap-3">

    <input type="text"
           name="search"
           value="{{ request('search') }}"
           placeholder="Cari bahan nonaktif..."
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
        <a href="{{ route('admin.ingredients.archive') }}"
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
                    <th class="px-6 py-4 text-left">Nama</th>
                    <th class="px-6 py-4 text-left">Kategori</th>
                    <th class="px-6 py-4 text-left">Dinonaktifkan</th>
                    <th class="px-6 py-4 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ingredients as $ingredient)
                    <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                        <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">
                            {{ $ingredient->name }}
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            {{ $ingredient->category->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-slate-500">
                            {{ optional($ingredient->deleted_at)->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <form action="{{ route('admin.ingredients.restore', $ingredient->id) }}"
                                  method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        onclick="return confirm('Aktifkan kembali bahan ini?')"
                                        class="text-blue-600 hover:underline transition">
                                    Aktifkan
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            Tidak ada bahan nonaktif.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="block md:hidden divide-y divide-slate-200 dark:divide-slate-800">
        @forelse($ingredients as $ingredient)
            <div class="p-5">
                <div class="flex justify-between items-start gap-3">
                    <div>
                        <div class="font-medium text-slate-800 dark:text-white">
                            {{ $ingredient->name }}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">
                            {{ $ingredient->category->name ?? '-' }}
                        </div>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full bg-red-100 text-red-600">
                        Nonaktif
                    </span>
                </div>

                <div class="text-xs text-slate-400 mt-3">
                    Dinonaktifkan: {{ optional($ingredient->deleted_at)->format('d M Y H:i') }}
                </div>

                <div class="mt-4">
                    <form action="{{ route('admin.ingredients.restore', $ingredient->id) }}"
                          method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                onclick="return confirm('Aktifkan kembali bahan ini?')"
                                class="text-sm text-blue-600 hover:underline transition">
                            Aktifkan
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-slate-500">
                Tidak ada bahan nonaktif.
            </div>
        @endforelse
    </div>
</div>

<div class="mt-8">
    {{ $ingredients->links() }}
</div>

@endsection
