{{-- Tailwind safelist: md:grid-cols-1 md:grid-cols-2 md:grid-cols-3 md:grid-cols-4 md:grid-cols-5 md:grid-cols-6 --}}
@php
    $unitColors = ['emerald', 'amber', 'violet', 'cyan', 'rose'];
    $totalCards = count($summary['by_unit']) + 2;
    $cols = min(max($totalCards, 1), 6);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-{{ $cols }} gap-4">
    {{-- Item Terpakai --}}
    <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-3">
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Item Terpakai</p>
            <div class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 group-hover:bg-slate-200 dark:group-hover:bg-slate-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
        </div>
        <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($summary['ingredients_count'], 0, ',', '.') }}</p>
        <p class="text-[10px] text-slate-400 mt-1">bahan baku terpakai</p>
        <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-200 dark:bg-slate-700"></div>
    </div>

    {{-- Frekuensi Pemakaian --}}
    <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
        <div class="flex items-start justify-between mb-3">
            <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Frekuensi</p>
            <div class="p-1.5 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-500/20 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
        </div>
        <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($summary['logs_count'], 0, ',', '.') }}</p>
        <p class="text-[10px] text-slate-400 mt-1">jumlah pemakaian</p>
        <div class="absolute bottom-0 left-0 h-0.5 w-full bg-blue-500/30"></div>
    </div>

    {{-- Per-Unit Cards (Dinamis) --}}
    @foreach($summary['by_unit'] as $idx => $unitData)
        @php $color = $unitColors[$idx % count($unitColors)]; @endphp
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-3">
                <p class="text-[10px] font-bold text-{{ $color }}-500 uppercase tracking-widest">Total {{ $unitData['unit'] }}</p>
                <div class="p-1.5 rounded-lg bg-{{ $color }}-50 dark:bg-{{ $color }}-500/10 text-{{ $color }}-400 group-hover:bg-{{ $color }}-100 dark:group-hover:bg-{{ $color }}-500/20 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">
                {{ rtrim(rtrim(number_format($unitData['total'], 2, ',', '.'), '0'), ',') }}
            </p>
            <p class="text-[10px] text-slate-400 mt-1">total pemakaian</p>
            <div class="absolute bottom-0 left-0 h-0.5 w-full bg-{{ $color }}-500/30"></div>
        </div>
    @endforeach
</div>
