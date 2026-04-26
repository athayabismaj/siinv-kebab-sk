<form method="GET" x-data x-ref="filterForm" class="flex flex-col sm:flex-row gap-3 w-full relative z-10 py-2 mb-2">
    <div class="flex-1 relative flex items-center w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
        <svg class="w-4 h-4 text-slate-400 absolute left-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
        </svg>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari bahan baku (Tekan Enter)..."
               @search="$refs.filterForm.submit()"
               class="w-full h-10 bg-transparent pl-10 pr-4 text-[13px] font-medium text-slate-700 outline-none dark:text-slate-200 placeholder:text-slate-400">
    </div>

    <select name="category" @change="$refs.filterForm.submit()" class="w-full sm:w-56 h-10 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
        <option value="">Semua Kategori</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
        @endforeach
    </select>

    <select name="has_price" @change="$refs.filterForm.submit()" class="w-full sm:w-48 h-10 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
        <option value="">Semua Harga</option>
        <option value="1" {{ request('has_price') === '1' ? 'selected' : '' }}>Ada Harga Jual</option>
        <option value="0" {{ request('has_price') === '0' ? 'selected' : '' }}>Belum Ada Harga</option>
    </select>
</form>
