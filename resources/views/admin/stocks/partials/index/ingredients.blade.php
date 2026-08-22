<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900" data-stock-list>
    <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5 dark:border-slate-800 sm:px-5">
        <div class="min-w-0">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Daftar Bahan</h2>
            <p class="mt-0.5 text-[11px] font-medium text-slate-400">Pilih tindakan untuk memperbarui stok.</p>
        </div>
        <span class="inline-flex shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
            {{ $ingredients->total() }} bahan
        </span>
    </header>

    <div class="divide-y divide-slate-100 dark:divide-slate-800">
        @forelse($ingredients as $item)
            @php
                $meta = $item->stock_meta ?? [];
                $isOut = (bool) ($meta['is_out'] ?? false);
                $isLow = (bool) ($meta['is_low'] ?? false);
                $priceUnit = match($item->display_unit ?? '') {
                    'kg' => '/kg',
                    'l' => '/liter',
                    'g' => '/gram',
                    'ml' => '/ml',
                    'pcs' => '/pack',
                    default => '',
                };
            @endphp

            <div
                class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-3 px-4 py-3.5 transition hover:bg-slate-50/70 dark:hover:bg-slate-800/35 sm:grid-cols-[minmax(0,1fr)_210px_190px] sm:px-5"
                data-stock-ingredient="{{ $item->id }}"
            >
                <div class="min-w-0">
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100" title="{{ $item->name }}">{{ $item->name }}</p>
                        <span class="inline-flex max-w-full truncate rounded-md bg-slate-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                            {{ $item->category?->name ?? 'Tanpa kategori' }}
                        </span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[10px] font-semibold text-slate-400">
                        <span>Minimum {{ $meta['minimum_text'] ?? '0' }} {{ $meta['unit'] ?? '-' }}</span>
                        @if($item->selling_price > 0)
                            <span class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($item->selling_price, 0, ',', '.') }}{{ $priceUnit }}</span>
                        @endif
                    </div>
                </div>

                <div class="shrink-0 text-right sm:text-left">
                    <p class="mb-1 text-[9px] font-black uppercase tracking-widest text-slate-400">Stok saat ini</p>
                    <div class="flex flex-wrap items-center justify-end gap-2 sm:justify-start">
                        <p class="text-lg font-black tabular-nums {{ $isOut ? 'text-rose-600 dark:text-rose-400' : ($isLow ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white') }}">
                            {{ $meta['stock_text'] ?? '0' }}
                            <span class="ml-0.5 text-[9px] font-black uppercase tracking-wider text-slate-400">{{ $meta['unit'] ?? '-' }}</span>
                        </p>
                        <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[9px] font-black uppercase tracking-wider {{ $isOut ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400' : ($isLow ? 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400') }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $isOut ? 'bg-rose-500' : ($isLow ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                            {{ $isOut ? 'Habis' : ($isLow ? 'Rendah' : 'Aman') }}
                        </span>
                    </div>
                    @if($meta['stock_pack_label'] ?? null)
                        <p class="mt-0.5 text-[9px] font-semibold text-slate-400">{{ $meta['stock_pack_label'] }}</p>
                    @endif
                </div>

                <div class="col-span-2 flex items-center justify-end gap-2 sm:col-span-1">
                    <a href="{{ route('admin.stocks.restock.form', $item->id) }}" class="inline-flex h-9 flex-1 items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 text-[11px] font-bold text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 sm:flex-none">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                        Restok
                    </a>
                    <a href="{{ route('admin.stocks.adjust.form', $item->id) }}" class="inline-flex h-9 flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-[11px] font-bold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 sm:flex-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                        Sesuaikan
                    </a>
                </div>
            </div>
        @empty
            <div class="px-5 py-14 text-center">
                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
                <p class="mt-3 text-sm font-bold text-slate-700 dark:text-slate-200">Bahan tidak ditemukan</p>
                <p class="mt-1 text-xs text-slate-400">Ubah pencarian atau filter yang dipilih.</p>
            </div>
        @endforelse
    </div>
</section>
