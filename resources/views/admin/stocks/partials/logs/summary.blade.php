<style>
    .stock-log-summary-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
    }

    @media (min-width: 640px) {
        .stock-log-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .stock-log-summary-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
    }
</style>

<div class="stock-log-summary-grid">
    @foreach($summaryCards as $card)
        <div class="relative min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
            <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-[10px] font-bold uppercase tracking-widest text-slate-500">{{ $card['label'] }}</p>
                    <div class="mt-3 flex items-end gap-1.5">
                        <span class="text-2xl font-black leading-none tabular-nums text-slate-950 dark:text-white">{{ number_format($card['value'], 0, ',', '.') }}</span>
                        <span class="pb-0.5 text-xs font-semibold text-slate-400">Entri</span>
                    </div>
                </div>
                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $card['bar_class'] }}"></span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full {{ $card['bar_class'] }}"></div>
        </div>
    @endforeach
</div>
