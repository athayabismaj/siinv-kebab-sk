<footer class="relative border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
    <div class="px-6 py-3 flex items-center justify-between gap-4">

        {{-- Left: Branding --}}
        <div class="flex items-center gap-2.5">
            <div class="w-5 h-5 rounded bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm shadow-blue-500/30 shrink-0">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="text-xs text-slate-400 dark:text-slate-500">
                © {{ date('Y') }}
                <span class="font-semibold text-slate-600 dark:text-slate-300">SiInv Kebab SK</span>
                <span class="hidden sm:inline">&mdash; Sistem Manajemen Inventory</span>
            </span>
        </div>

        {{-- Right: Version + Role badge --}}
        <div class="flex items-center gap-3">
            <span class="text-[10px] text-slate-300 dark:text-slate-700 hidden sm:block">v1.0.0</span>
            @auth
                <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 border border-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                    {{ auth()->user()->role->name }} Panel
                </span>
            @endauth
        </div>
    </div>
</footer>