<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
    <div class="flex-1">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
            <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-blue-600 dark:text-blue-400">Manajemen Bahan</span>
        </nav>

        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">Manajemen Bahan</h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
            Kelola stok bahan baku secara real-time. Pantau level stok dan pastikan ketersediaan selalu terjaga.
        </p>
    </div>

    <div class="shrink-0 w-full lg:w-auto mt-2 lg:mt-0">
        <a href="{{ route('admin.ingredients.create') }}"
           class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-[13px] font-semibold rounded-xl hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Tambah Bahan
        </a>
    </div>
</div>
