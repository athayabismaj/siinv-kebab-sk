<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Rincian Pemakaian Bahan Baku</h2>
        </div>
        <div class="text-xs font-medium text-slate-500 bg-slate-50 dark:bg-slate-800/50 px-3 py-1.5 rounded-full border border-slate-100 dark:border-slate-700">
            Menampilkan <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $usageItems->firstItem() ?? 0 }} - {{ $usageItems->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $usageItems->total() }}</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                <tr>
                    <th class="px-6 py-4">Bahan Baku</th>
                    <th class="px-6 py-4 text-center">Total Pemakaian</th>
                    <th class="px-6 py-4 text-center">Frekuensi</th>
                    <th class="px-6 py-4 text-right">Terakhir Dipakai</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($usageItems as $item)
                    <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-800 dark:text-white">{{ $item->ingredient_name }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex flex-col items-center">
                                <span class="text-[13px] font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                                    {{ $item->quantity_label }}
                                </span>
                                @if($item->pack_label)
                                    <span class="text-[10px] font-medium text-slate-400 mt-0.5">
                                        ({{ $item->pack_label }})
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{ number_format($item->usage_count) }}x
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-xs font-medium text-slate-500 dark:text-slate-400 tabular-nums">
                            {{ $item->last_used_date }}
                            <span class="text-slate-300 dark:text-slate-600 mx-1">•</span>
                            {{ $item->last_used_time }}
                        </td>
                    </tr>

                    <tr class="md:hidden">
                        <td colspan="4" class="p-0">
                            <div class="p-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <div class="flex justify-between items-start gap-3 mb-2">
                                    <p class="font-semibold text-slate-800 dark:text-white">{{ $item->ingredient_name }}</p>
                                    <div class="text-right">
                                        <span class="text-[13px] font-bold text-rose-600 dark:text-rose-400 tabular-nums">
                                            - {{ $item->quantity_label }}
                                        </span>
                                        @if($item->pack_label)
                                            <div class="text-[10px] font-medium text-slate-400 mt-0.5">({{ $item->pack_label }})</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold rounded-md">
                                        {{ number_format($item->usage_count) }}x Dipakai
                                    </span>
                                    <span class="font-medium text-slate-500 dark:text-slate-400">
                                        {{ $item->last_used_mobile }}
                                    </span>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Tidak ada data pemakaian bahan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.reports.usage.partials.pagination', ['usageItems' => $usageItems])
</div>
