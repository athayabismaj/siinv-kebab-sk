@if($lowStockCount > 0)
    <div class="flex items-center gap-2.5 rounded-xl border border-amber-200/80 bg-amber-50/70 px-3.5 py-2.5 text-xs font-semibold text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300" data-stock-warning>
        <svg class="h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        <span><strong>{{ $lowStockCount }} bahan</strong> perlu diperiksa karena stok rendah atau habis.</span>
    </div>
@endif
