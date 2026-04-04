<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
    <div class="flex-1">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
            <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-slate-500 dark:text-slate-400">Inventori</span>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-blue-600 dark:text-blue-400">Restok & Penyesuaian</span>
        </nav>

        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">Restok dan Penyesuaian</h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
            Kelola penambahan stok baru dan lakukan penyesuaian (adjustment) stok fisik bahan baku secara manual.
        </p>
    </div>

    <div class="shrink-0 mt-2 lg:mt-0">
        <a href="{{ route('admin.stocks.logs') }}"
           class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Riwayat Stok
        </a>
    </div>
</div>
