<div class="flex flex-col lg:flex-row gap-3 w-full items-center justify-between py-2 relative z-10">
    <form method="GET" action="{{ route('admin.stocks.logs') }}" class="flex w-full lg:w-auto rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 shrink-0">
        <input type="hidden" name="date" value="{{ $activeDate->toDateString() }}">
        <input type="hidden" name="type" value="{{ request('type') }}">

        <button type="submit" name="period" value="daily" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $activePeriod === 'daily' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
        <button type="submit" name="period" value="weekly" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $activePeriod === 'weekly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</button>
        <button type="submit" name="period" value="monthly" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $activePeriod === 'monthly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
    </form>

    <form method="GET" action="{{ route('admin.stocks.logs') }}" class="flex-1 flex items-center px-1 w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900">
        <input type="hidden" name="period" value="{{ $activePeriod }}">
        <input type="hidden" name="type" value="{{ request('type') }}">

        <a href="{{ route('admin.stocks.logs', $prevParams) }}"
           class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
        </a>

        <input type="date" name="date" value="{{ $activeDate->toDateString() }}" onchange="this.form.submit()"
               class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">

        @if($isNextDisabled)
            <span class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
            </span>
        @else
            <a href="{{ route('admin.stocks.logs', $nextParams) }}"
               class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
            </a>
        @endif
    </form>

    <div class="flex items-center w-full lg:w-auto shrink-0 justify-end">
        <a href="{{ route('admin.stocks.logs.export', request()->query()) }}" class="flex-1 lg:flex-none inline-flex items-center justify-center gap-2 px-5 h-[38px] bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            Export CSV
        </a>
    </div>
</div>
