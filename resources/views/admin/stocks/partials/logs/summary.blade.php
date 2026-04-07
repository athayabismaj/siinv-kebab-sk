<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach($summaryCards as $card)
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">{{ $card['label'] }}</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($card['value']) }}</span>
                <span class="text-xs font-medium text-slate-400">Entri</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full {{ $card['bar_class'] }}"></div>
        </div>
    @endforeach
</div>
