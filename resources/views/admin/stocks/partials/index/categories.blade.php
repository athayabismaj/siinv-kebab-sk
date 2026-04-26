<div x-data="{ openCategory: null }" class="space-y-4">
    @forelse($categories as $category)
        @if($category->ingredients->count())
            @php
                $summary = $category->stock_summary ?? ['out' => 0, 'low' => 0];
                $outCount = (int) ($summary['out'] ?? 0);
                $lowCount = (int) ($summary['low'] ?? 0);
            @endphp
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-all duration-200">
                <button @click="openCategory === {{ $category->id }} ? openCategory = null : openCategory = {{ $category->id }}"
                        class="w-full px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-left bg-slate-50/50 hover:bg-slate-100 dark:bg-slate-800/30 dark:hover:bg-slate-800/50 transition-colors">

                    <div class="flex items-center gap-3">
                        <span class="font-bold text-slate-800 dark:text-white text-[15px]">{{ $category->name }}</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-white border border-slate-200 dark:bg-slate-800 dark:border-slate-700 text-[10px] font-bold text-slate-500 dark:text-slate-400 shadow-sm">
                            {{ $category->ingredients->count() }} Item
                        </span>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                        <div class="flex gap-1.5">
                            @if($outCount > 0)
                                <span class="rounded-md bg-red-50 border border-red-100 px-2 py-1 text-[10px] font-bold text-red-600 dark:bg-red-900/20 dark:border-red-800/50 dark:text-red-400 tracking-wider uppercase shadow-sm">Habis: {{ $outCount }}</span>
                            @endif
                            @if($lowCount > 0)
                                <span class="rounded-md bg-amber-50 border border-amber-100 px-2 py-1 text-[10px] font-bold text-amber-600 dark:bg-amber-900/20 dark:border-amber-800/50 dark:text-amber-400 tracking-wider uppercase shadow-sm">Rendah: {{ $lowCount }}</span>
                            @endif
                        </div>

                        <div class="h-6 w-6 flex items-center justify-center rounded-full bg-white border border-slate-200 shadow-sm dark:bg-slate-800 dark:border-slate-700 text-slate-500">
                            <svg :class="openCategory === {{ $category->id }} ? 'rotate-180' : ''" class="w-3.5 h-3.5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </button>

                <div x-show="openCategory === {{ $category->id }}" x-collapse x-cloak class="border-t border-slate-200 dark:border-slate-800">
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 bg-slate-50/20 dark:bg-slate-900/20">
                        @foreach($category->ingredients as $item)
                            @php
                                $meta = $item->stock_meta ?? [];
                                $progress = (float) ($meta['progress_percent'] ?? 0);
                                $isOut = (bool) ($meta['is_out'] ?? false);
                                $isLow = (bool) ($meta['is_low'] ?? false);
                            @endphp

                            <div class="flex flex-col bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 relative overflow-hidden group">
                                <div class="absolute top-0 left-0 w-full h-1 {{ $isOut ? 'bg-red-500' : ($isLow ? 'bg-amber-500' : 'bg-emerald-500') }}"></div>

                                <div class="flex justify-between items-start mb-4 mt-1">
                                    <div class="pr-3">
                                        <h3 class="font-bold text-slate-800 dark:text-white text-[15px] group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight">{{ $item->name }}</h3>
                                        <p class="text-[11px] font-semibold text-slate-400 mt-1">Min: {{ $meta['minimum_text'] ?? '0' }} {{ $meta['unit'] ?? '-' }}</p>
                                        
                                        @if ($item->selling_price > 0)
                                            @php
                                                $priceUnit = match($item->display_unit ?? '') {
                                                    'kg'  => '/kg',
                                                    'l'   => '/liter',
                                                    'g'   => '/gram',
                                                    'ml'  => '/ml',
                                                    'pcs' => '/pack',
                                                    default => '',
                                                };
                                            @endphp
                                            <div class="mt-1.5 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                                Rp {{ number_format($item->selling_price, 0, ',', '.') }}<span class="text-[9px] font-normal text-emerald-500/80">{{ $priceUnit }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-right flex flex-col items-end">
                                        <span class="block text-[22px] font-black {{ $isOut ? 'text-red-600 dark:text-red-400' : ($isLow ? 'text-amber-600 dark:text-amber-400' : 'text-slate-800 dark:text-white') }} tabular-nums leading-none">{{ $meta['stock_text'] ?? '0' }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $meta['unit'] ?? '-' }}</span>
                                    </div>
                                </div>

                                <div class="w-full h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 mb-5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $isOut ? 'bg-red-500' : ($isLow ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $progress }}%"></div>
                                </div>

                                <div class="mt-auto flex gap-2.5 pt-1">
                                    <a href="{{ route('admin.stocks.restock.form', $item->id) }}" class="flex-1 flex justify-center items-center gap-1.5 px-3 py-2.5 text-xs font-bold rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60 transition-all dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20 dark:hover:bg-emerald-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                                        Restok
                                    </a>

                                    <a href="{{ route('admin.stocks.adjust.form', $item->id) }}" class="flex-1 flex justify-center items-center gap-1.5 px-3 py-2.5 text-xs font-bold rounded-xl bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200/60 transition-all dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20 dark:hover:bg-amber-500/20">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Sesuaikan
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @empty
        <div class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-16 text-center shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-4 border border-slate-100 dark:border-slate-700">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-[13px] font-medium">Tidak ada bahan baku yang ditemukan.</p>
        </div>
    @endforelse
</div>
