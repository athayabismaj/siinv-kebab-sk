<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="border-b border-slate-200 overflow-x-auto hide-scrollbar dark:border-slate-800 px-2 sm:px-6">
        <nav class="flex flex-nowrap space-x-6" aria-label="Tabs">
            <a href="{{ request()->fullUrlWithQuery(['type' => null]) }}"
               class="{{ !request('type') ? 'border-blue-500 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }} whitespace-nowrap border-b-2 py-4 px-1 text-[13px] font-bold transition-colors">
                Semua Log
            </a>

            <a href="{{ request()->fullUrlWithQuery(['type' => 'in']) }}"
               class="{{ request('type') === 'in' ? 'border-emerald-500 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }} whitespace-nowrap border-b-2 py-4 px-1 text-[13px] font-bold transition-colors flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Restok
            </a>

            <a href="{{ request()->fullUrlWithQuery(['type' => 'out']) }}"
               class="{{ request('type') === 'out' ? 'border-rose-500 text-rose-600 dark:border-rose-400 dark:text-rose-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }} whitespace-nowrap border-b-2 py-4 px-1 text-[13px] font-bold transition-colors flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-rose-500"></span> Pemakaian
            </a>

            <a href="{{ request()->fullUrlWithQuery(['type' => 'adjustment']) }}"
               class="{{ request('type') === 'adjustment' ? 'border-amber-500 text-amber-600 dark:border-amber-400 dark:text-amber-400' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }} whitespace-nowrap border-b-2 py-4 px-1 text-[13px] font-bold transition-colors flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span> Penyesuaian
            </a>
        </nav>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider bg-slate-50/50 dark:bg-slate-800/30">
                <tr>
                    <th class="px-6 py-4">Waktu</th>
                    <th class="px-6 py-4">Bahan Baku</th>
                    <th class="px-6 py-4 text-center">Tipe</th>
                    <th class="px-6 py-4 text-right">Jumlah</th>
                    <th class="px-6 py-4">Sumber / Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @php $currentGroupDate = null; @endphp

                @forelse($logs as $log)
                    @php
                        $logDate = $log->created_at->toDateString();
                        $rawQty = (float) $log->quantity;
                        $displayUnit = strtolower(trim((string) ($log->ingredient->display_unit ?? $log->ingredient->base_unit ?? '')));
                        $qtyDisplay = in_array($displayUnit, ['kg', 'l'], true) ? $rawQty / 1000 : $rawQty;
                        $formattedQty = rtrim(rtrim(number_format(abs($qtyDisplay), 2, '.', ''), '0'), '.');
                        if ($formattedQty === '') $formattedQty = '0';

                        $packText = null;
                        if ($displayUnit === 'pcs') {
                            $packSize = max(1, (int) ($log->ingredient->pack_size ?? 1));
                            if ($packSize > 1) {
                                $packQty = abs($qtyDisplay) / $packSize;
                                $packFormatted = rtrim(rtrim(number_format($packQty, 2, '.', ''), '0'), '.');
                                if ($packFormatted === '') $packFormatted = '0';
                                $packText = $packFormatted . ' pack';
                            }
                        }

                        $config = [
                            'in' => ['label' => 'Restok', 'icon' => 'bg-emerald-500', 'text' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'border' => 'border-emerald-200 dark:border-emerald-500/20', 'prefix' => '+', 'source' => 'Manual Restok'],
                            'adjustment' => ['label' => 'Penyesuaian', 'icon' => 'bg-amber-500', 'text' => 'text-amber-600 dark:text-amber-400', 'bg' => 'bg-amber-50 dark:bg-amber-500/10', 'border' => 'border-amber-200 dark:border-amber-500/20', 'prefix' => ($rawQty >= 0 ? '+' : '-'), 'source' => 'Manual Adjust'],
                            'out' => ['label' => 'Pemakaian', 'icon' => 'bg-rose-500', 'text' => 'text-rose-600 dark:text-rose-400', 'bg' => 'bg-rose-50 dark:bg-rose-500/10', 'border' => 'border-rose-200 dark:border-rose-500/20', 'prefix' => '-', 'source' => $log->reference_id ? 'TRX-' . $log->reference_id : 'Transaksi']
                        ][$log->type] ?? ['label' => 'Unknown', 'icon' => 'bg-slate-500', 'text' => 'text-slate-600', 'bg' => 'bg-slate-50', 'border' => 'border-slate-200', 'prefix' => '', 'source' => '-'];
                    @endphp

                    @if($currentGroupDate !== $logDate)
                        <tr class="bg-slate-50/70 dark:bg-slate-800/30">
                            @php
                                $groupLabel = $log->created_at->translatedFormat('d F Y');
                                if ($log->created_at->isToday()) $groupLabel = 'Hari ini - ' . $groupLabel;
                                elseif ($log->created_at->isYesterday()) $groupLabel = 'Kemarin - ' . $groupLabel;
                            @endphp
                            <td colspan="5" class="px-6 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                                {{ $groupLabel }}
                            </td>
                        </tr>
                        @php $currentGroupDate = $logDate; @endphp
                    @endif

                    <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap text-xs font-semibold text-slate-500 dark:text-slate-400">
                            {{ $log->created_at->format('H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-900 dark:text-white text-[13px]">{{ $log->ingredient->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider {{ $config['bg'] }} {{ $config['text'] }} border {{ $config['border'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $config['icon'] }}"></span>
                                {{ $config['label'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="font-black text-sm tabular-nums leading-none {{ $config['text'] }}">
                                {{ $config['prefix'] }}{{ $formattedQty }} <span class="text-[10px] font-bold uppercase ml-0.5">{{ $displayUnit }}</span>
                            </div>
                            @if($packText)
                                <div class="text-[10px] font-semibold text-slate-400 mt-1">({{ $packText }})</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $config['source'] }}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">{{ $log->note ?: '-' }}</div>
                        </td>
                    </tr>

                    <tr class="md:hidden">
                        <td colspan="5" class="p-0">
                            <div class="p-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <div class="flex justify-between items-start gap-3 mb-2">
                                    <div>
                                        <p class="font-bold text-slate-900 dark:text-white text-[14px]">{{ $log->ingredient->name ?? '-' }}</p>
                                        <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-500 mt-1">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ $log->created_at->format('H:i') }}
                                            <span class="mx-0.5">&bull;</span>
                                            {{ $config['source'] }}
                                        </div>
                                    </div>
                                    <div class="text-right flex flex-col items-end">
                                        <span class="font-black text-sm tabular-nums leading-none {{ $config['text'] }}">
                                            {{ $config['prefix'] }}{{ $formattedQty }} <span class="text-[10px] font-bold uppercase ml-0.5">{{ $displayUnit }}</span>
                                        </span>
                                        @if($packText)
                                            <div class="text-[10px] font-semibold text-slate-400 mt-1">({{ $packText }})</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100 dark:border-slate-800/50">
                                    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $config['bg'] }} {{ $config['text'] }} border {{ $config['border'] }}">
                                        {{ $config['label'] }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 italic line-clamp-1 text-right">{{ $log->note ?: 'Tanpa catatan' }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Belum ada riwayat stok yang tercatat pada periode ini.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.stocks.partials.logs.pagination', ['logs' => $logs])
</div>
