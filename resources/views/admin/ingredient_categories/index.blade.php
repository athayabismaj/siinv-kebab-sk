@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Kategori Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Kategori Bahan" 
        subtitle="Kelola kategori bahan untuk pengelompokan dan organisasi inventory bahan baku." 
        breadcrumb-parent="Inventori" 
        breadcrumb-child="Kategori Bahan">
        
        <a href="{{ route('admin.ingredient-categories.create') }}"
           class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-[13px] font-black text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/15 sm:w-auto">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14m7-7H5" />
            </svg>
            Tambah Kategori
        </a>
    </x-page-header>

    @if($errors->any())
        <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-white px-4 py-3 shadow-sm dark:border-rose-900/60 dark:bg-slate-900">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-300">Input Belum Valid</p>
                <p class="mt-0.5 text-sm font-semibold leading-relaxed text-slate-700 dark:text-slate-200">{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    {{-- ================= TABLE & CARD SECTION ================= --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/30 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Daftar Kategori Bahan</h2>
                    @if(method_exists($categories, 'total'))
                        <span class="inline-flex h-6 items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 text-[10px] font-black uppercase tracking-wider text-blue-700 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300">
                            {{ $categories->total() }} data
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                    Kategori yang masih memiliki bahan tidak dapat dihapus.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="hidden border-b border-slate-100 bg-white text-[10px] font-black uppercase tracking-widest text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500 md:table-header-group">
                    <tr>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4 text-center">Jumlah Bahan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                    @forelse($categories as $category)
                        <tr class="hidden transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40 md:table-row">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-900/60">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M3 11.25V6a3 3 0 013-3h5.25a3 3 0 012.12.88l6.75 6.75a3 3 0 010 4.24l-5.25 5.25a3 3 0 01-4.24 0L3.88 13.37A3 3 0 013 11.25z" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-black text-slate-900 dark:text-white">{{ $category->name }}</p>
                                        <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">Kategori bahan baku</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex h-7 items-center rounded-full border border-slate-200 bg-slate-50 px-3 text-xs font-black text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ number_format($category->ingredients_count) }} bahan
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.ingredient-categories.edit', $category->id) }}"
                                       title="Edit kategori"
                                       aria-label="Edit kategori {{ $category->name }}"
                                       class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-500/10 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/15">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.25 18.402 4.5 19.5l1.098-3.75L16.862 4.487z" />
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.ingredient-categories.destroy', $category->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="Hapus kategori"
                                                aria-label="Hapus kategori {{ $category->name }}"
                                                data-confirm
                                                data-confirm-message="Yakin ingin menghapus kategori ini?"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-500/10 dark:border-rose-900/60 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/15">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 7h12m-9 0V5.75A1.75 1.75 0 0110.75 4h2.5A1.75 1.75 0 0115 5.75V7m2 0-.72 11.02A2 2 0 0114.28 20H9.72a2 2 0 01-2-1.98L7 7m3 4v5m4-5v5" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr class="md:hidden">
                            <td colspan="3" class="p-0">
                                <div class="flex items-start justify-between gap-3 p-4 transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-900/60">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M3 11.25V6a3 3 0 013-3h5.25a3 3 0 012.12.88l6.75 6.75a3 3 0 010 4.24l-5.25 5.25a3 3 0 01-4.24 0L3.88 13.37A3 3 0 013 11.25z" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="break-words font-black text-slate-900 dark:text-white">{{ $category->name }}</p>
                                            <span class="mt-2 inline-flex h-7 items-center rounded-full border border-slate-200 bg-slate-50 px-3 text-[11px] font-black text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                                {{ number_format($category->ingredients_count) }} bahan
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <a href="{{ route('admin.ingredient-categories.edit', $category->id) }}"
                                           title="Edit kategori"
                                           aria-label="Edit kategori {{ $category->name }}"
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L8.25 18.402 4.5 19.5l1.098-3.75L16.862 4.487z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.ingredient-categories.destroy', $category->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    title="Hapus kategori"
                                                    aria-label="Hapus kategori {{ $category->name }}"
                                                    data-confirm
                                                    data-confirm-message="Yakin ingin menghapus kategori ini?"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-500/10 dark:text-rose-300">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 7h12m-9 0V5.75A1.75 1.75 0 0110.75 4h2.5A1.75 1.75 0 0115 5.75V7m2 0-.72 11.02A2 2 0 0114.28 20H9.72a2 2 0 01-2-1.98L7 7m3 4v5m4-5v5" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M3 11.25V6a3 3 0 013-3h5.25a3 3 0 012.12.88l6.75 6.75a3 3 0 010 4.24l-5.25 5.25a3 3 0 01-4.24 0L3.88 13.37A3 3 0 013 11.25z" />
                                    </svg>
                                </div>
                                <p class="mt-4 text-sm font-black text-slate-900 dark:text-white">Belum ada kategori bahan.</p>
                                <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Tambahkan kategori pertama untuk mulai mengelompokkan bahan baku.</p>

                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

    @include('partials.pagination_simple', [
        'paginator' => $categories,
        'label' => 'data',
    ])
</div>
@endsection
