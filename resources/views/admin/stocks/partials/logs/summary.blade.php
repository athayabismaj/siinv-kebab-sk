<div class="app-auto-card-grid grid gap-4 items-start">
    @foreach($summaryCards as $card)
        @php
            $colorClass = 'slate';
            $iconName = 'info';
            
            if (str_contains(strtolower($card['label']), 'restok')) {
                $colorClass = 'emerald';
                $iconName = 'plus';
            } elseif (str_contains(strtolower($card['label']), 'pemakaian')) {
                $colorClass = 'rose';
                $iconName = 'minus';
            } elseif (str_contains(strtolower($card['label']), 'pengembalian')) {
                $colorClass = 'cyan';
                $iconName = 'return';
            } elseif (str_contains(strtolower($card['label']), 'penyesuaian')) {
                $colorClass = 'amber';
                $iconName = 'edit';
            } elseif (str_contains(strtolower($card['label']), 'total')) {
                $colorClass = 'blue';
                $iconName = 'clipboard';
            }
        @endphp
        
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">{{ number_format($card['value'], 0, ',', '.') }}</p>
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-{{ $colorClass }}-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    <x-icon :name="$iconName" class="h-4 w-4" />
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                <span>entri tercatat</span>
            </div>
        </div>
    @endforeach
</div>
