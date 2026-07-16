@php
    $meta = $ingredient->stock_meta ?? [];
    $isOut = (bool) ($meta['is_out'] ?? false);
    $isLow = (bool) ($meta['is_low'] ?? false);
@endphp

@if($ingredient->trashed())
<tr class="md:hidden">
    <td colspan="3" class="p-0">
        <div class="bg-slate-50/70 p-5 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate font-bold text-slate-800 dark:text-white">{{ $ingredient->name }}</p>
                    <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
                </div>
                <span class="shrink-0 rounded-lg bg-slate-200/70 px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-slate-600 dark:bg-slate-800 dark:text-slate-300">Diarsipkan</span>
            </div>
            <p class="mt-3 text-[11px] text-slate-400">Diarsipkan {{ $ingredient->deleted_at?->translatedFormat('d M Y, H:i') }}</p>
            <button type="button" @click="openIngredientRestore('{{ route('admin.ingredients.restore', $ingredient->id) }}', '{{ addslashes($ingredient->name) }}')" class="mt-4 inline-flex w-full h-10 items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 text-[12px] font-bold text-emerald-600 transition hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-400">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                Pulihkan Bahan
            </button>
        </div>
    </td>
</tr>
@else
<tr class="md:hidden">
    <td colspan="3" class="p-0">
        <div class="p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
            <div class="flex justify-between items-start gap-3 mb-4">
                <div>
                    <p class="font-bold text-slate-900 dark:text-white">{{ $ingredient->name }}</p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
                    @if ($ingredient->selling_price > 0 || $ingredient->cost_price > 0)
                        @php
                            $priceUnit = match($ingredient->display_unit ?? '') {
                                'kg'  => '/kg',
                                'l'   => '/liter',
                                'g'   => '/gram',
                                'ml'  => '/ml',
                                'pcs' => '/pack',
                                default => '',
                            };
                        @endphp
                        <div class="mt-1.5 flex items-center gap-1.5">
                            @if ($ingredient->selling_price > 0)
                                <span class="text-[10px] font-bold text-emerald-500 dark:text-emerald-400">Rp {{ number_format($ingredient->selling_price, 0, ',', '.') }}<span class="font-normal opacity-70">{{ $priceUnit }}</span></span>
                            @endif
                            @if ($ingredient->selling_price > 0 && $ingredient->cost_price > 0)
                                <span class="text-slate-300 dark:text-slate-700">·</span>
                            @endif
                            @if ($ingredient->cost_price > 0)
                                <span class="text-[9px] font-medium text-slate-400 dark:text-slate-500">Modal Rp {{ number_format($ingredient->cost_price, 0, ',', '.') }}<span class="opacity-70">{{ $priceUnit }}</span></span>
                            @endif
                        </div>
                    @endif
                </div>
                @if($isOut)
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 uppercase tracking-widest border border-red-200 dark:border-red-800/50">Habis</span>
                @elseif($isLow)
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 uppercase tracking-widest border border-amber-200 dark:border-amber-800/50">Rendah</span>
                @else
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 uppercase tracking-widest border border-emerald-200 dark:border-emerald-800/50">Aman</span>
                @endif
            </div>

            <div class="mb-3">
                <div class="flex items-baseline gap-1 mb-1.5">
                    <span class="text-xl font-black {{ $isOut ? 'text-red-600 dark:text-red-400' : ($isLow ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400') }}">
                        {{ $meta['stock_text'] ?? '0' }}
                    </span>
                    <span class="text-xs font-bold text-slate-400 uppercase">{{ $meta['unit'] ?? '-' }}</span>
                    @if($meta['stock_pack_label'])
                        <span class="text-[11px] font-semibold text-slate-400 ml-1">({{ $meta['stock_pack_label'] }})</span>
                    @endif
                </div>

                <div class="w-full bg-slate-100 dark:bg-slate-800/50 h-1.5 rounded-full overflow-hidden mb-1.5">
                    <div class="h-full rounded-full transition-all duration-500 {{ $isOut ? 'bg-red-500' : ($isLow ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $meta['progress_percent'] ?? 0 }}%"></div>
                </div>

                <div class="text-[10px] font-medium text-slate-400 flex justify-between">
                    <span>Min: <span class="font-bold text-slate-500 dark:text-slate-300">{{ $meta['minimum_text'] ?? '0' }}</span> @if($meta['minimum_pack_label'])({{ $meta['minimum_pack_label'] }})@endif</span>
                    @if($meta['pack_info_label'])
                        <span>{{ $meta['pack_info_label'] }}</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/50 text-[11px] font-bold uppercase tracking-widest">
                <a href="{{ route('admin.ingredients.edit', $ingredient->id) }}" class="flex items-center gap-1.5 text-slate-400 transition-colors hover:text-blue-600 dark:hover:text-blue-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    Edit
                </a>
                <span class="text-slate-200 dark:text-slate-800">|</span>
                <button type="button" 
                        @click="openIngredientDestroy('{{ route('admin.ingredients.destroy', $ingredient->id) }}', '{{ addslashes($ingredient->name) }}')"
                        class="flex items-center gap-1.5 text-slate-400 transition-colors hover:text-rose-600 dark:hover:text-rose-400 outline-none">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    Nonaktifkan
                </button>
            </div>
        </div>
    </td>
</tr>
@endif
