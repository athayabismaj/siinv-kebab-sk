<div x-data="{ openCategory: null }" class="max-w-6xl mx-auto bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800/80">
    @forelse($categories as $category)
        @if($category->ingredients->count())
            @php
                $summary = $category->stock_summary ?? ['out' => 0, 'low' => 0];
                $outCount = (int) ($summary['out'] ?? 0);
                $lowCount = (int) ($summary['low'] ?? 0);
            @endphp
            <div class="transition-colors duration-200" :class="openCategory === {{ $category->id }} ? 'bg-slate-50/30 dark:bg-slate-800/20' : ''">
                <button @click="openCategory === {{ $category->id }} ? openCategory = null : openCategory = {{ $category->id }}"
                        class="w-full px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                    
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        </div>
                        <span class="font-bold text-slate-800 dark:text-white text-[15px]">{{ $category->name }}</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 text-[10px] font-bold shadow-sm">
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

                <div x-show="openCategory === {{ $category->id }}" x-collapse x-cloak class="border-t border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-900/50 shadow-[inset_0_4px_6px_-4px_rgba(0,0,0,0.03)]">
                    <div class="p-6 flex flex-wrap gap-5">
                        @foreach($category->ingredients as $item)
                            @php
                                $meta = $item->stock_meta ?? [];
                                $isOut = (bool) ($meta['is_out'] ?? false);
                                $isLow = (bool) ($meta['is_low'] ?? false);
                            @endphp

                            <div class="flex-1 basis-[calc(100%)] md:basis-[calc(50%-1rem)] xl:basis-[calc(33.333%-1rem)] min-w-[280px] flex flex-col bg-white dark:bg-slate-900 p-0 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-200 relative overflow-hidden group">
                                <div class="p-5 flex justify-between items-start">
                                    {{-- Left Info --}}
                                    <div class="pr-3 flex-1 min-w-0">
                                        <h3 class="font-bold text-slate-800 dark:text-white text-[15px] group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors leading-tight truncate">{{ $item->name }}</h3>
                                        
                                        <div class="flex items-center flex-wrap gap-2 mt-2">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                Min: {{ $meta['minimum_text'] ?? '0' }} {{ $meta['unit'] ?? '-' }}
                                            </span>
                                            
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
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                                    Rp {{ number_format($item->selling_price, 0, ',', '.') }}{{ $priceUnit }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        {{-- Status indicator dot --}}
                                        <div class="flex items-center gap-1.5 mt-3">
                                            <span class="h-2 w-2 rounded-full {{ $isOut ? 'bg-red-500' : ($isLow ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                                            <span class="text-[10px] font-bold {{ $isOut ? 'text-red-500' : ($isLow ? 'text-amber-500' : 'text-emerald-500') }} uppercase tracking-wider">
                                                {{ $isOut ? 'Habis' : ($isLow ? 'Menipis' : 'Aman') }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Right Stock --}}
                                    <div class="text-right flex flex-col items-end shrink-0 pl-3">
                                        <span class="block text-[32px] font-black {{ $isOut ? 'text-red-600 dark:text-red-400' : ($isLow ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white') }} tabular-nums leading-none tracking-tight">{{ $meta['stock_text'] ?? '0' }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">{{ $meta['unit'] ?? '-' }}</span>
                                    </div>
                                </div>

                                {{-- Footer Actions --}}
                                <div class="mt-auto border-t border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 p-3 flex gap-2">
                                    <a href="{{ route('admin.stocks.restock.form', $item->id) }}" class="flex-1 flex justify-center items-center gap-1.5 px-3 py-2 text-[11px] font-bold rounded-xl bg-slate-900 text-white hover:bg-slate-800 shadow-sm transition-all dark:bg-blue-600 dark:hover:bg-blue-500 dark:text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                                        Restok
                                    </a>

                                    <a href="{{ route('admin.stocks.adjust.form', $item->id) }}" class="flex-1 flex justify-center items-center gap-1.5 px-3 py-2 text-[11px] font-bold rounded-xl bg-white text-slate-700 hover:bg-slate-50 border border-slate-200 shadow-sm transition-all dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dark:hover:bg-slate-700">
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
