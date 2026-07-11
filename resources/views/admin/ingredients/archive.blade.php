@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Arsip Bahan')

@push('styles')
<style>
    .archive-filter-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: .75rem;
    }

    @media (min-width: 1024px) {
        .archive-filter-row {
            grid-template-columns: minmax(0, 4fr) minmax(220px, 1fr);
            align-items: center;
        }
    }
</style>
@endpush

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-2 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-slate-500 dark:text-slate-400">Inventori</span>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <a href="{{ route('admin.ingredients.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Manajemen Bahan</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-blue-600 dark:text-blue-400">Arsip Bahan</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                Arsip Bahan
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Daftar bahan yang telah dinonaktifkan. Bahan di arsip tidak muncul di restok atau pemakaian, namun tetap bisa dipulihkan.
            </p>
        </div>

        <a href="{{ route('admin.ingredients.index') }}"
           class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[13px] font-black text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto">
            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
    </div>

    {{-- ================= FILTER SECTION ================= --}}
    <form method="GET" action="{{ route('admin.ingredients.archive') }}" class="archive-filter-row w-full">
        <div class="relative flex h-11 items-center rounded-xl border border-slate-200 bg-white shadow-sm transition focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10 dark:border-slate-800 dark:bg-slate-900">
            <svg class="absolute left-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari nama bahan nonaktif..."
                   class="h-full w-full bg-transparent pl-10 pr-4 text-[13px] font-semibold text-slate-700 outline-none placeholder:text-slate-400 dark:text-slate-200">
        </div>

        <select name="category" onchange="this.form.submit()" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-semibold text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

    </form>

    {{-- ================= TABLE & CARD SECTION ================= --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/30 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Daftar Bahan Nonaktif</h2>
                    @if(method_exists($ingredients, 'total'))
                        <span class="inline-flex h-6 items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 text-[10px] font-black uppercase tracking-wider text-blue-700 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300">
                            {{ $ingredients->total() }} data
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                    Pulihkan bahan jika ingin digunakan kembali di transaksi stok dan resep.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="hidden border-b border-slate-100 bg-white text-[10px] font-black uppercase tracking-widest text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500 md:table-header-group">
                    <tr>
                        <th class="px-6 py-4">Bahan</th>
                        <th class="px-6 py-4">Dinonaktifkan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($ingredients as $ingredient)
                        <tr class="hidden transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40 md:table-row">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-900/60">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7.5l-8-4.5-8 4.5m16 0-8 4.5m8-4.5v9L12 21m0-9L4 7.5m8 4.5v9M4 7.5v9L12 21" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-slate-900 dark:text-white">{{ $ingredient->name }}</p>
                                        <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-700 dark:text-slate-200">{{ optional($ingredient->deleted_at)->format('d F Y') }}</p>
                                <p class="mt-0.5 text-xs font-medium text-slate-400">Pukul {{ optional($ingredient->deleted_at)->format('H:i') }} WIB</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('admin.ingredients.restore', $ingredient->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            onclick="return confirm('Apakah Anda yakin ingin mengaktifkan kembali bahan ini?')"
                                            class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 text-xs font-black text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-500/10 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/15">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 4v5h5M20 20v-5h-5M5.64 15A7 7 0 0018 17.66M18.36 9A7 7 0 006 6.34" />
                                        </svg>
                                        Pulihkan
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <tr class="md:hidden">
                            <td colspan="3" class="p-0">
                                <div class="flex items-start justify-between gap-3 p-4 transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-900/60">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7.5l-8-4.5-8 4.5m16 0-8 4.5m8-4.5v9L12 21m0-9L4 7.5m8 4.5v9M4 7.5v9L12 21" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="break-words font-black text-slate-900 dark:text-white">{{ $ingredient->name }}</p>
                                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
                                            <p class="mt-2 text-xs font-medium text-slate-400">
                                                {{ optional($ingredient->deleted_at)->format('d M Y, H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                    <form action="{{ route('admin.ingredients.restore', $ingredient->id) }}" method="POST" class="shrink-0">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                title="Pulihkan bahan"
                                                onclick="return confirm('Aktifkan kembali bahan ini?')"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 4v5h5M20 20v-5h-5M5.64 15A7 7 0 0018 17.66M18.36 9A7 7 0 006 6.34" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7.5l-8-4.5-8 4.5m16 0-8 4.5m8-4.5v9L12 21m0-9L4 7.5m8 4.5v9M4 7.5v9L12 21" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm font-black text-slate-900 dark:text-white">Arsip bahan masih kosong.</p>
                                <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Bahan yang dinonaktifkan akan muncul di halaman ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

    @include('partials.pagination_simple', [
        'paginator' => $ingredients,
        'label' => 'data',
    ])

</div>
@endsection
