<form method="GET" class="flex flex-col sm:flex-row gap-3 w-full relative z-10 py-2 mb-2">
    <div class="flex-1 relative flex items-center w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
        <svg class="w-4 h-4 text-slate-400 absolute left-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
        </svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari bahan baku..."
               class="w-full h-10 bg-transparent pl-10 pr-4 text-[13px] font-medium text-slate-700 outline-none dark:text-slate-200 placeholder:text-slate-400">
    </div>

    <select name="category" class="w-full sm:w-56 h-10 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
        <option value="">Semua Kategori</option>
        @foreach($allCategories as $category)
            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                {{ $category->name }} {{ $category->status_marker }}
            </option>
        @endforeach
    </select>

    <div class="flex items-center gap-3 shrink-0 justify-end w-full sm:w-auto">
        @if(request()->filled('search') || request()->filled('category'))
            <a href="{{ route('admin.stocks.index') }}" class="mr-1 inline-flex items-center gap-1.5 text-[12px] font-semibold text-slate-400 hover:text-red-500 transition-colors">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                Reset
            </a>
        @endif

        <button type="submit" class="flex-1 sm:flex-none px-6 h-10 rounded-xl bg-blue-600 text-white text-[13px] font-semibold hover:bg-blue-700 transition shadow-sm shadow-blue-500/20">
            Filter
        </button>
    </div>
</form>
