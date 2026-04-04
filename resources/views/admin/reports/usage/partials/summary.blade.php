<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Item Terpakai</p>
        <div class="flex items-baseline gap-1.5">
            <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['ingredients_count']) }}</p>
            <span class="text-xs font-medium text-slate-400">Bahan</span>
        </div>
    </div>

    <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Frekuensi Pemakaian</p>
        <div class="flex items-baseline gap-1.5">
            <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['logs_count']) }}</p>
            <span class="text-xs font-medium text-slate-400">Log</span>
        </div>
    </div>

    <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Pemakaian (Satuan Dasar)</p>
        <div class="flex items-baseline gap-1.5">
            <p class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($summary['total_base_quantity'], 2) }}</p>
            <span class="text-xs font-medium text-slate-400">Volume</span>
        </div>
    </div>
</div>