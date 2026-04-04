@php
    $meta = $ingredient->stock_meta ?? [];
    $isOut = (bool) ($meta['is_out'] ?? false);
    $isLow = (bool) ($meta['is_low'] ?? false);
@endphp

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
    </td>

    <td class="px-6 py-5 align-top">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-baseline gap-1">
                <span class="text-lg font-black {{ $isOut ? 'text-red-600 dark:text-red-400' : ($isLow ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400') }}">
                    {{ $meta['stock_text'] ?? '0' }}
                </span>
                <span class="text-xs font-bold text-slate-400 uppercase">{{ $meta['unit'] ?? '-' }}</span>

                @if($meta['stock_pack_label'])
                    <span class="text-[11px] font-semibold text-slate-400 ml-1">({{ $meta['stock_pack_label'] }})</span>
                @endif
            </div>

            @if($isOut)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 uppercase tracking-widest border border-red-200 dark:border-red-800/50">Habis</span>
            @elseif($isLow)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 uppercase tracking-widest border border-amber-200 dark:border-amber-800/50">Rendah</span>
            @else
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 uppercase tracking-widest border border-emerald-200 dark:border-emerald-800/50">Aman</span>
            @endif
        </div>

        <div class="w-full bg-slate-100 dark:bg-slate-800/50 h-1.5 rounded-full overflow-hidden mb-2">
            <div class="h-full rounded-full transition-all duration-500 {{ $isOut ? 'bg-red-500' : ($isLow ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $meta['progress_percent'] ?? 0 }}%"></div>
        </div>

        <div class="text-[11px] font-medium text-slate-400">
            Batas Minimum: <span class="font-bold text-slate-500 dark:text-slate-300">{{ $meta['minimum_text'] ?? '0' }}</span>
            @if($meta['minimum_pack_label'])
                <span>({{ $meta['minimum_pack_label'] }})</span>
            @endif
        </div>
    </td>

    <td class="px-6 py-5 text-right align-top">
        <div class="flex items-center justify-end gap-3 text-[12px] font-bold mt-1">
            <a href="{{ route('admin.ingredients.edit', $ingredient->id) }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors uppercase tracking-widest">
                Edit
            </a>
            <span class="text-slate-200 dark:text-slate-700">|</span>
            <form action="{{ route('admin.ingredients.destroy', $ingredient->id) }}" method="POST" class="inline-block">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('Nonaktifkan bahan ini?')" class="text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors uppercase tracking-widest">
                    Nonaktifkan
                </button>
            </form>
        </div>
    </td>
</tr>
