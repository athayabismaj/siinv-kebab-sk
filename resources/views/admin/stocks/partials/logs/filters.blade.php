@php
    $logsRouteName = $logsRouteName ?? 'admin.stocks.logs';
    $exportRouteName = $exportRouteName ?? 'admin.stocks.logs.export';
    $hasActiveFilters = request()->filled('branch_id');
@endphp

<form method="GET" action="{{ route($logsRouteName) }}" id="filter-form" class="relative z-10 py-2">
    <input type="hidden" name="type" value="{{ request('type') }}">

    <div class="flex flex-col gap-3 w-full">
        
        {{-- ROW 1: Date Navigator + Atur Ulang --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
            {{-- DATE NAVIGATOR --}}
            <div class="min-w-0 flex items-center px-1 w-full rounded-xl border border-slate-200 bg-white shadow-sm transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 flex-1">
                <a href="{{ route($logsRouteName, $prevParams) }}"
                   class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                </a>

                <input type="{{ $activePeriod === 'monthly' ? 'month' : 'date' }}" 
                       name="date"
                       value="{{ $activePeriod === 'monthly' ? $activeDate->format('Y-m') : $activeDate->toDateString() }}" 
                       max="{{ $activePeriod === 'monthly' ? now()->format('Y-m') : now()->toDateString() }}"
                       onchange="this.form.submit()"
                       class="h-[38px] w-full flex-1 min-w-0 bg-transparent px-2 text-center text-[13px] font-bold text-slate-700 outline-none cursor-pointer dark:text-slate-200 dark:[color-scheme:dark]">

                @if($isNextDisabled)
                    <span class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-300 cursor-not-allowed dark:text-slate-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </span>
                @else
                    <a href="{{ route($logsRouteName, $nextParams) }}"
                       class="flex shrink-0 h-8 w-10 mt-1 mb-1 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-50 hover:text-slate-700 transition-colors dark:hover:bg-slate-800 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                    </a>
                @endif
            </div>
            
            {{-- Atur Ulang Button --}}
            @if($hasActiveFilters)
                <a href="{{ route($logsRouteName) }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 h-[38px] text-[13px] font-semibold text-slate-600 shadow-sm transition-all hover:bg-slate-50 hover:text-rose-600 focus:outline-none focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-rose-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    <span class="hidden sm:inline">Atur Ulang</span>
                </a>
            @endif
        </div>

        {{-- ROW 2: Tabs, Branch, Export --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
            {{-- Tabs --}}
            <div class="flex rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 shrink-0">
                <button type="submit" name="period" value="daily" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $activePeriod === 'daily' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
                <button type="submit" name="period" value="weekly" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $activePeriod === 'weekly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</button>
                <button type="submit" name="period" value="monthly" class="flex-1 lg:flex-none min-w-[90px] rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all {{ $activePeriod === 'monthly' ? 'bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
            </div>

            {{-- Branch Select --}}
            @if(($branchOptions ?? collect())->isNotEmpty())
                <div class="w-full sm:flex-1 min-w-0">
                    <select name="branch_id" onchange="this.form.submit()"
                            class="h-[38px] w-full rounded-xl border border-slate-200 bg-white px-4 text-center text-[13px] font-semibold text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        <option value="">Semua Cabang</option>
                        @foreach($branchOptions as $branch)
                            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="w-full sm:flex-1 min-w-0"></div>
            @endif

            {{-- Export Button --}}
            <div class="flex items-center w-full sm:w-auto shrink-0 justify-end" x-data="{ exportOpen: false }">
                <div class="relative w-full sm:w-auto">
                    <button type="button" @click="exportOpen = !exportOpen" @click.away="exportOpen = false" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 h-[38px] bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Ekspor Laporan
                        <svg class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    
                    <div x-show="exportOpen" 
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 rounded-xl bg-white shadow-lg border border-slate-100 dark:bg-slate-800 dark:border-slate-700 py-1 z-50 overflow-hidden"
                         style="display: none;">
                        
                        <a href="{{ route($exportRouteName, array_merge(request()->query(), ['format' => 'html'])) }}" target="_blank" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-blue-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-blue-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                            Format HTML
                        </a>
                        
                        <a href="{{ route($exportRouteName, array_merge(request()->query(), ['format' => 'pdf'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-rose-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-rose-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Format PDF
                        </a>
                        
                        <a href="{{ route($exportRouteName, array_merge(request()->query(), ['format' => 'excel'])) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-emerald-600 dark:text-slate-300 dark:hover:bg-slate-700/50 dark:hover:text-emerald-400 font-medium transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Format Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
