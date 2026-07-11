<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">

    {{-- Table Header --}}
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex items-center justify-between gap-3">
        <h3 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Rincian Pemakaian Bahan Baku</h3>
        <span class="px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500">
            {{ $usageItems->firstItem() ?? 0 }}-{{ $usageItems->lastItem() ?? 0 }} dari {{ $usageItems->total() }}
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="hidden md:table-header-group text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20">
                <tr>
                    <th class="px-6 py-3.5">Bahan Baku</th>
                    <th class="px-6 py-3.5 text-right">Total Pemakaian</th>
                    <th class="px-6 py-3.5 text-center">Frekuensi</th>
                    <th class="px-6 py-3.5 text-right">Terakhir Dipakai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                @forelse($usageItems as $item)
                    <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-900 dark:text-white">{{ $item->ingredient_name }}</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="font-bold text-rose-600 dark:text-rose-500 tabular-nums">
                                    {{ $item->quantity_label }}
                                </span>
                                @if($item->pack_label)
                                    <span class="text-[10px] text-slate-400 mt-0.5">
                                        ({{ $item->pack_label }})
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500">
                                {{ number_format($item->usage_count) }}x
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ $item->last_used_date }}</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">{{ $item->last_used_time }}</span>
                            </div>
                        </td>
                    </tr>

                    {{-- Mobile View --}}
                    <tr class="md:hidden">
                        <td colspan="4" class="p-0">
                            <div class="p-4 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition">
                                <div class="flex justify-between items-start gap-3 mb-1.5">
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-sm">{{ $item->ingredient_name }}</p>
                                        <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-500 mt-0.5">
                                            <span>{{ number_format($item->usage_count) }}x dipakai</span>
                                            <span class="text-slate-300 dark:text-slate-600">•</span>
                                            <span>{{ $item->last_used_mobile }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-rose-600 text-sm whitespace-nowrap">{{ $item->quantity_label }}</p>
                                        @if($item->pack_label)
                                            <p class="text-[10px] text-slate-400 mt-0.5">({{ $item->pack_label }})</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-0">
                            <div class="flex flex-col items-center justify-center p-16 text-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-4 border border-slate-100 dark:border-slate-700">
                                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-[13px] font-medium">Belum ada data pemakaian bahan pada periode ini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

@include('admin.reports.usage.partials.pagination', ['usageItems' => $usageItems])
