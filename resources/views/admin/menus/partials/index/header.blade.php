<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
    <div class="flex-1 w-full overflow-hidden">
        <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto hide-scrollbar pb-1">
            <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">Menu & Resep</span>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">Manajemen Menu</span>
        </nav>

        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">Manajemen Menu</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
            Kelola daftar menu utama restoran. Harga dan resep diatur di dalam masing-masing variant menu.
        </p>
    </div>

    <div class="flex flex-col sm:flex-row items-center gap-3 shrink-0 w-full lg:w-auto mt-2 lg:mt-0">
        <a href="{{ route('admin.menus.archive') }}" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-[13px] font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all shadow-sm">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
            Arsip Menu
        </a>

        <a href="{{ route('admin.menus.create') }}" class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-[13px] font-semibold rounded-xl hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Menu
        </a>
    </div>
</div>
