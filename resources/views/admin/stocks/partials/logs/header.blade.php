<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
    <div class="flex-1 w-full overflow-hidden">
        <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto hide-scrollbar pb-1">
            <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Beranda
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>

            <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                Inventori
            </span>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>

            <a href="{{ route('admin.stocks.index') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Restok & Penyesuaian
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>

            <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                Riwayat Stok
            </span>
        </nav>

        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
            Riwayat Perubahan Stok
        </h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
            Jejak riwayat penambahan stok, pemakaian dari transaksi kasir, dan penyesuaian (adjustment) manual.
        </p>
    </div>

    <div class="shrink-0 mt-1 lg:mt-8">
        <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 dark:bg-blue-500/10 border border-blue-100/50 dark:border-blue-800/30 shadow-sm">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            <span class="text-[11px] font-bold tracking-wide text-blue-700 dark:text-blue-400 uppercase">
                PERIODE DATA:
                <span class="font-medium text-slate-700 dark:text-slate-300 ml-1">{{ $dateDisplay }}</span>
            </span>
        </div>
    </div>
</div>
