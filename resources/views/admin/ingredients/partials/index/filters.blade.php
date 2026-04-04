<form method="GET" class="flex flex-col sm:flex-row gap-3 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3 rounded-2xl shadow-sm relative z-10">
    <div class="flex-1 relative flex items-center w-full rounded-xl border border-slate-200 bg-slate-50/50 transition-all focus-within:border-blue-500 focus-within:bg-white focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:focus-within:bg-slate-900">
        <svg class="w-4 h-4 text-slate-400 absolute left-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
        </svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari bahan baku..."
               class="w-full h-10 bg-transparent pl-10 pr-4 text-[13px] font-medium text-slate-700 outline-none dark:text-slate-200 placeholder:text-slate-400">
    </div>

    <select name="category" class="w-full sm:w-48 h-10 rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-medium text-slate-700 shadow-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
        <option value="">Semua Kategori</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
        @endforeach
    </select>

    <div class="flex items-center gap-2 shrink-0">
        <button type="submit" class="w-full sm:w-auto px-5 h-10 rounded-xl bg-slate-900 text-white text-[13px] font-semibold hover:bg-slate-800 transition shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
            Filter
        </button>
        @if(request()->has('search') || request()->has('category'))
            <a href="{{ route('admin.ingredients.index') }}" class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors dark:border-slate-700 dark:hover:bg-red-500/10" title="Reset Filter">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
            </a>
        @endif
    </div>
</form>
