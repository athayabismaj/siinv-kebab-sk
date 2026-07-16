@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Arsip Bahan')

@push('styles')
@vite('resources/css/pages/admin-archive.css')
@endpush

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10" x-data="{
        restoreUrl: '',
        ingredientName: '',
        openIngredientRestore(url, name) {
            this.restoreUrl = url;
            this.ingredientName = name;
            document.getElementById('ingredient_restore_confirmation').value = '';
            $dispatch('open-modal', 'ingredient-restore-modal');
        }
    }">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <x-page-header
        title="Arsip Bahan"
        subtitle="Daftar bahan yang telah dinonaktifkan. Bahan di arsip tidak muncul di stok, namun tetap bisa dipulihkan."
        breadcrumb-parent="Manajemen Bahan"
        breadcrumb-child="Arsip Bahan">

        <a href="{{ route('admin.ingredients.index') }}"
           class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[13px] font-black text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto">
            <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali
        </a>
    </x-page-header>

    {{-- ================= TABS NAVIGATION ================= --}}
    <div class="flex rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 w-full mb-2">
        <a href="{{ route('admin.ingredients.archive') }}" class="flex-1 rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400">Arsip Bahan</a>
        <a href="{{ route('admin.menus.archive') }}" class="flex-1 rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Arsip Menu</a>
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

        <select name="category" data-submit-on-change class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-semibold text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
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
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-3">
                    <h2 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Daftar Bahan Nonaktif</h2>
                    @if(method_exists($ingredients, 'total'))
                        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500 shadow-sm">
                            {{ $ingredients->total() }} bahan
                        </span>
                    @endif
                </div>
                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Pulihkan bahan jika ingin digunakan kembali.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="hidden border-b border-slate-100 bg-white text-[11px] font-black uppercase tracking-widest text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500 md:table-header-group">
                    <tr>
                        <th class="px-6 py-4">Bahan</th>
                        <th class="px-6 py-4">Dinonaktifkan Pada</th>
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
                                        <p class="truncate font-black text-[14px] text-slate-900 dark:text-white">{{ $ingredient->name }}</p>
                                        <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-[13px] font-bold text-slate-700 dark:text-slate-200">{{ optional($ingredient->deleted_at)->format('d F Y') }}</p>
                                <p class="mt-0.5 text-[11px] font-medium text-slate-400">Pukul {{ optional($ingredient->deleted_at)->format('H:i') }} WIB</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button"
                                        title="Pulihkan Bahan"
                                        @click="openIngredientRestore('{{ route('admin.ingredients.restore', $ingredient->id) }}', '{{ addslashes($ingredient->name) }}')"
                                        class="inline-flex items-center justify-center rounded-xl bg-blue-50 p-2 text-blue-700 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 shadow-sm">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h5M20 20v-5h-5M5.64 15A7 7 0 0018 17.66M18.36 9A7 7 0 006 6.34" />
                                    </svg>
                                </button>
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
                                            <p class="break-words font-black text-[14px] text-slate-900 dark:text-white">{{ $ingredient->name }}</p>
                                            <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
                                            <p class="mt-2 text-[11px] font-medium text-slate-400">
                                                {{ optional($ingredient->deleted_at)->format('d M Y, H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button"
                                            title="Pulihkan Bahan"
                                            @click="openIngredientRestore('{{ route('admin.ingredients.restore', $ingredient->id) }}', '{{ addslashes($ingredient->name) }}')"
                                            class="inline-flex items-center justify-center rounded-xl bg-blue-50 p-2.5 text-blue-700 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 shadow-sm shrink-0">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h5M20 20v-5h-5M5.64 15A7 7 0 0018 17.66M18.36 9A7 7 0 006 6.34" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-20 text-center">
                                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 ring-4 ring-white shadow-sm dark:bg-slate-800 dark:ring-slate-900 text-slate-400 dark:text-slate-500 mb-4">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    </svg>
                                </div>
                                <h3 class="text-[14px] font-bold text-slate-900 dark:text-white">Arsip bahan masih kosong</h3>
                                <p class="mt-1 text-[13px] font-medium text-slate-500 dark:text-slate-400">Belum ada bahan yang dinonaktifkan saat ini.</p>
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

    {{-- Modal Aktifkan Kembali Bahan Baku --}}
    <x-modal id="ingredient-restore-modal" maxWidth="md" type="success">
        <x-slot name="title">Aktifkan Kembali Bahan Baku</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </x-slot>
        <x-slot name="description">
            Anda yakin ingin mengaktifkan kembali bahan baku <span class="font-bold text-slate-900 dark:text-white" x-text="ingredientName"></span>? Bahan ini akan tersedia kembali untuk digunakan.
        </x-slot>

        <form x-bind:action="restoreUrl" method="POST">
            @csrf
            @method('PATCH')
            <div class="pt-2">
                <label class="sr-only" for="ingredient_restore_confirmation">Konfirmasi</label>
                <input type="text" name="restore_confirmation" id="ingredient_restore_confirmation" required pattern="AKTIFKAN" title="Ketik AKTIFKAN" placeholder="Ketik AKTIFKAN"
                       data-uppercase-input
                       class="uppercase block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 placeholder:normal-case focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500 dark:focus:ring-emerald-500" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'ingredient-restore-modal')"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:w-auto">
                    Ya, Aktifkan
                </button>
            </div>
        </form>
    </x-modal>

</div>
@endsection
