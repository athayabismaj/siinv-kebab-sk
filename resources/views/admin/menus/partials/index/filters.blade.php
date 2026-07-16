<form method="GET" x-data x-ref="menuFilterForm" class="grid w-full grid-cols-1 gap-3 lg:grid-cols-[minmax(0,4fr)_minmax(220px,1fr)]">
    <input type="hidden" name="record_status" value="{{ $recordStatus }}">
    <div class="relative flex h-11 items-center rounded-xl border border-slate-200 bg-white shadow-sm transition focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10 dark:border-slate-800 dark:bg-slate-900">
        <svg class="absolute left-3.5 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"/></svg>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama menu..." @search="$refs.menuFilterForm.submit()" class="h-full w-full bg-transparent pl-10 pr-4 text-[13px] font-semibold text-slate-700 outline-none placeholder:text-slate-400 dark:text-slate-200">
    </div>
    <select name="category" @change="$refs.menuFilterForm.submit()" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-[13px] font-semibold text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
        <option value="">Semua Kategori</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" {{ (string) request('category') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
        @endforeach
    </select>
</form>
