<div class="mb-2 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0">
        <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto hide-scrollbar pb-1">
            <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">Menu & Resep</span>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            <a href="{{ route('admin.menus.index') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Manajemen Menu</a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">Arsip Menu</span>
        </nav>

        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Arsip Menu</h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
            Daftar menu yang telah dinonaktifkan. Menu di arsip tidak muncul di kasir, namun tetap bisa dipulihkan.
        </p>
    </div>

    <a href="{{ route('admin.menus.index') }}" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[13px] font-black text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto">
        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>
</div>
