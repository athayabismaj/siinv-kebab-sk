<form method="GET" action="{{ route('admin.transactions.index') }}" class="space-y-3">
    <div class="flex items-center gap-2 w-full">
        <a href="{{ route('admin.transactions.index', $prevDateParams) }}" class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800">&lt;</a>

        <input type="date" name="date" value="{{ $activeDate->toDateString() }}" max="{{ $todayDate->toDateString() }}" class="flex-1 min-w-0 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">

        @if($isToday)
            <span class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600 cursor-not-allowed">&gt;</span>
        @else
            <a href="{{ route('admin.transactions.index', $nextDateParams) }}" class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800">&gt;</a>
        @endif
    </div>

    <div class="flex flex-col md:flex-row md:items-center gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode / nama kasir..." class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">

        <button type="submit" class="w-full md:w-auto px-5 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">Terapkan</button>

        @if($hasActiveFilters)
            <a href="{{ route('admin.transactions.index') }}" class="w-full md:w-auto text-sm text-slate-500 px-2 py-2 text-center hover:text-slate-700 dark:hover:text-slate-300">Atur Ulang</a>
        @endif
    </div>
</form>
