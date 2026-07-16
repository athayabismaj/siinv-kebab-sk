@php
    $meta = $ingredient->stock_meta ?? [];
    $isOut = (bool) ($meta['is_out'] ?? false);
    $isLow = (bool) ($meta['is_low'] ?? false);
@endphp

@if($ingredient->trashed())
<tr class="hidden md:table-row bg-slate-50/60 transition-colors hover:bg-slate-100/70 dark:bg-slate-900 dark:hover:bg-slate-800/50">
    <td class="px-6 py-5 align-middle">
        <p class="text-[15px] font-bold text-slate-700 dark:text-slate-200">{{ $ingredient->name }}</p>
        <p class="mt-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>
    </td>
    <td class="px-6 py-5 align-middle">
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-200/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-600 dark:bg-slate-800 dark:text-slate-300">
            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Diarsipkan
        </span>
        <p class="mt-1.5 text-[11px] text-slate-400">{{ $ingredient->deleted_at?->translatedFormat('d M Y, H:i') }}</p>
    </td>
    <td class="px-6 py-5 text-right align-middle">
        <div class="flex justify-end">
            <div class="group/tooltip relative flex items-center justify-center">
                <button type="button"
                        @click="openIngredientRestore('{{ route('admin.ingredients.restore', $ingredient->id) }}', '{{ addslashes($ingredient->name) }}')"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-emerald-600 dark:hover:bg-slate-800 dark:hover:text-emerald-400">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
                <div class="pointer-events-none absolute bottom-full right-0 mb-1.5 opacity-0 transition-opacity group-hover/tooltip:opacity-100 px-2.5 py-1 bg-slate-800 dark:bg-slate-700 text-white text-[10px] font-bold rounded-md shadow-sm whitespace-nowrap z-50">
                    Pulihkan
                </div>
            </div>
        </div>
    </td>
</tr>
@else
<tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
    <td class="px-6 py-5 w-1/3 align-top">
        <p class="font-bold text-slate-900 dark:text-white text-[15px]">{{ $ingredient->name }}</p>
        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mt-1">{{ $ingredient->category->name ?? 'Tanpa Kategori' }}</p>

        @if($meta['pack_info_label'])
            <div class="mt-2.5 inline-flex items-center gap-1.5 rounded-md bg-slate-100 dark:bg-slate-800 px-2 py-1 text-[10px] font-bold text-slate-500 dark:text-slate-400">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ $meta['pack_info_label'] }}
            </div>
        @endif
        @if ($ingredient->cost_price > 0 || $ingredient->selling_price > 0)
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
            <div class="mt-2.5 flex items-center gap-2">
                @if ($ingredient->selling_price > 0)
                    <span class="text-[11px] font-bold text-emerald-500 dark:text-emerald-400">
                        Rp {{ number_format($ingredient->selling_price, 0, ',', '.') }}<span class="font-normal opacity-70 text-[10px]">{{ $priceUnit }}</span>
                    </span>
                @endif
                @if ($ingredient->selling_price > 0 && $ingredient->cost_price > 0)
                    <span class="text-slate-300 dark:text-slate-700 text-[10px]">·</span>
                @endif
                @if ($ingredient->cost_price > 0)
                    <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500">
                        Modal Rp {{ number_format($ingredient->cost_price, 0, ',', '.') }}<span class="opacity-70">{{ $priceUnit }}</span>
                    </span>
                @endif
            </div>
        @endif
    </td>

    <td class="px-6 py-5 align-top">
        <div class="w-full max-w-[240px] flex flex-col gap-2">
            <div class="flex items-end justify-between">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-xl font-black {{ $isOut ? 'text-rose-600 dark:text-rose-400' : ($isLow ? 'text-amber-500 dark:text-amber-400' : 'text-slate-800 dark:text-white') }}">
                        {{ $meta['stock_text'] ?? '0' }}
                    </span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $meta['unit'] ?? '-' }}</span>
                    @if($meta['stock_pack_label'])
                        <span class="text-[10px] font-semibold text-slate-400 ml-0.5">({{ $meta['stock_pack_label'] }})</span>
                    @endif
                </div>

                @if($isOut)
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-rose-50 px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Habis
                    </span>
                @elseif($isLow)
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span> Rendah
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aman
                    </span>
                @endif
            </div>

            <!-- Modern Progress Track -->
            <div class="relative w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div class="absolute top-0 left-0 h-full transition-all duration-700 ease-out rounded-full {{ $isOut ? 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.5)]' : ($isLow ? 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]' : 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.4)]') }}" style="width: {{ $meta['progress_percent'] ?? 0 }}%"></div>
            </div>

            <div class="flex items-center justify-between text-[10px] font-medium text-slate-400">
                <span>Min: <span class="font-bold text-slate-500 dark:text-slate-300">{{ $meta['minimum_text'] ?? '0' }}</span> @if($meta['minimum_pack_label'])({{ $meta['minimum_pack_label'] }})@endif</span>
            </div>
        </div>
    </td>

    <td class="px-6 py-5 text-right align-top">
        <div class="flex items-center justify-end gap-1 mt-1">
            <div class="group/tooltip relative flex items-center justify-center">
                <a href="{{ route('admin.ingredients.edit', $ingredient->id) }}"
                   class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-800 dark:hover:text-blue-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                </a>
                <div class="pointer-events-none absolute bottom-full mb-1.5 opacity-0 transition-opacity group-hover/tooltip:opacity-100 px-2.5 py-1 bg-slate-800 dark:bg-slate-700 text-white text-[10px] font-bold rounded-md shadow-sm whitespace-nowrap z-50">
                    Edit
                </div>
            </div>

            <div class="group/tooltip relative flex items-center justify-center">
                <button type="button"
                        @click="openIngredientDestroy('{{ route('admin.ingredients.destroy', $ingredient->id) }}', '{{ addslashes($ingredient->name) }}')"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-rose-600 dark:hover:bg-slate-800 dark:hover:text-rose-400 outline-none">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
                <div class="pointer-events-none absolute bottom-full right-0 mb-1.5 opacity-0 transition-opacity group-hover/tooltip:opacity-100 px-2.5 py-1 bg-slate-800 dark:bg-slate-700 text-white text-[10px] font-bold rounded-md shadow-sm whitespace-nowrap z-50">
                    Nonaktifkan
                </div>
            </div>
        </div>
    </td>
</tr>
@endif
