<div class="mb-2">
    <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
        <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <span class="text-slate-600 dark:text-slate-300">Kasir</span>
        <span class="text-slate-200 dark:text-slate-700">/</span>
        <span class="text-slate-600 dark:text-slate-300">Monitoring Transaksi</span>
    </nav>

    <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1">Monitoring Transaksi</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400">Data transaksi kasir per hari - {{ $activeDate->translatedFormat('d M Y') }}</p>
</div>
