{{-- Tailwind safelist: md:grid-cols-1 md:grid-cols-2 md:grid-cols-3 md:grid-cols-4 md:grid-cols-5 md:grid-cols-6 --}}
@php
    $unitColors = ['emerald', 'amber', 'violet', 'cyan', 'rose'];
    $totalCards = count($summary['by_unit']) + 2;
    $cols = min(max($totalCards, 1), 6);
@endphp

<div class="grid gap-4 items-start" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
    {{-- Item Terpakai --}}
    <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Item Terpakai</p>
                <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">{{ number_format($summary['ingredients_count'], 0, ',', '.') }}</p>
            </div>
            <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </span>
        </div>
        <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
            <span>bahan baku terpakai</span>
        </div>
    </div>

    {{-- Jumlah Pemakaian --}}
    <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Jumlah Pemakaian</p>
                <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">{{ number_format($summary['logs_count'], 0, ',', '.') }}</p>
            </div>
            <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-blue-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </span>
        </div>
        <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
            <span>jumlah pemakaian</span>
        </div>
    </div>

    {{-- Per-Unit Cards (Dinamis) --}}
    @foreach($summary['by_unit'] as $idx => $unitData)
        @php $color = $unitColors[$idx % count($unitColors)]; @endphp
        <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Total {{ $unitData['unit'] }}</p>
                    <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">
                        {{ rtrim(rtrim(number_format($unitData['total'], 2, ',', '.'), '0'), ',') }}
                    </p>
                </div>
                <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-{{ $color }}-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                    @php
                        $unitLower = strtolower($unitData['unit']);
                    @endphp
                    @if($unitLower === 'g' || $unitLower === 'gram' || $unitLower === 'kg')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                    @elseif($unitLower === 'ml' || $unitLower === 'liter' || $unitLower === 'l')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    @elseif($unitLower === 'pcs' || $unitLower === 'pcs.')
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    @else
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    @endif
                </span>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                <span>total pemakaian</span>
            </div>
        </div>
    @endforeach
</div>
