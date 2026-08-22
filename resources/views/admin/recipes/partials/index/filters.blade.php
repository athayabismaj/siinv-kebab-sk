<form method="GET" action="{{ route('admin.recipes.index') }}" class="w-full">
    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px_auto]">
        <label class="relative block">
            <span class="sr-only">Cari resep</span>
            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/>
            </svg>
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari menu, varian, atau kategori..."
                @input.debounce.500ms="$el.form.requestSubmit()"
                class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 text-[13px] font-medium text-slate-700 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:focus:bg-slate-900"
            >
        </label>

        <label>
            <span class="sr-only">Filter kategori</span>
            <select name="category" data-submit-on-change class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-[13px] font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </label>

        @if(request()->filled('search') || request()->filled('category'))
            <a href="{{ route('admin.recipes.index') }}" class="inline-flex h-11 items-center justify-center px-2 text-xs font-bold text-slate-400 transition hover:text-rose-600" title="Atur ulang filter">
                Reset
            </a>
        @endif
    </div>
</form>
