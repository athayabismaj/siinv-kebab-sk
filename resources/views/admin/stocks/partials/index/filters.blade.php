@php
    $hasFilters = request()->filled('search') || request()->filled('category') || request()->filled('has_price');
@endphp

<form method="GET" x-data x-ref="filterForm" class="relative z-10 flex w-full flex-col gap-2 sm:flex-row" data-stock-filters>
    <label class="relative flex h-10 min-w-0 flex-1 items-center rounded-xl border border-slate-200 bg-white shadow-sm transition focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/15 dark:border-slate-800 dark:bg-slate-900">
        <span class="sr-only">Cari bahan</span>
        <svg class="absolute left-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
        </svg>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari bahan..."
               @search="$refs.filterForm.submit()"
               class="h-full w-full bg-transparent pl-10 pr-4 text-xs font-semibold text-slate-700 outline-none placeholder:text-slate-400 dark:text-slate-200">
    </label>

    <select name="category" aria-label="Filter kategori" @change="$refs.filterForm.submit()" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 sm:w-52 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
        <option value="">Semua Kategori</option>
        @foreach($allCategories as $category)
            <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>
                {{ $category->name }} {{ $category->status_marker }}
            </option>
        @endforeach
    </select>

    <select name="has_price" aria-label="Filter harga" @change="$refs.filterForm.submit()" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-600 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/15 sm:w-44 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
        <option value="">Semua harga</option>
        <option value="1" {{ request('has_price') === '1' ? 'selected' : '' }}>Sudah ada harga</option>
        <option value="0" {{ request('has_price') === '0' ? 'selected' : '' }}>Tanpa harga</option>
    </select>

    @if($hasFilters)
        <a href="{{ route('admin.stocks.index') }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl px-3 text-xs font-bold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
            Reset
        </a>
    @endif
</form>
