<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    @php
        $stats = [
            ['label' => 'Total Log', 'value' => $summary['total'], 'color' => 'slate'],
            ['label' => 'Restok', 'value' => $summary['restock'], 'color' => 'emerald'],
            ['label' => 'Pemakaian', 'value' => $summary['usage'], 'color' => 'rose'],
            ['label' => 'Penyesuaian', 'value' => $summary['adjustment'], 'color' => 'amber'],
        ];
    @endphp

    @foreach($stats as $stat)
    <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">{{ $stat['label'] }}</p>
        <div class="flex items-baseline gap-1.5">
            <span class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($stat['value']) }}</span>
            <span class="text-xs font-medium text-slate-400">Entri</span>
        </div>
        <div class="absolute bottom-0 left-0 h-1 w-full bg-{{ $stat['color'] }}-500/20"></div>
    </div>
    @endforeach
</div>
