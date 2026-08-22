@php
    $unit = strtolower((string) $ingredient->display_unit);
    $unitLabel = strtoupper($unit);
    $packSize = max(1, (int) ($ingredient->pack_size ?? 1));
    $warehouse = (float) $ingredient->transfer_stock_value;
    $carry = rtrim(rtrim(number_format((float) $ingredient->carry_forward_value, 2, '.', ''), '0'), '.');
    $opening = rtrim(rtrim(number_format((float) $ingredient->session_opening_value, 2, '.', ''), '0'), '.');
    $transferred = rtrim(rtrim(number_format((float) $ingredient->transferred_to_session_value, 2, '.', ''), '0'), '.');
    $initial = (string) old("transfers.{$ingredient->id}.opening_quantity", $opening);
@endphp

<article x-data="{
        id: {{ $ingredient->id }}, openingQty: @js($initial), initialQty: @js($initial),
        carry: @js((float) $ingredient->carry_forward_value), unit: @js($unit),
        value() { return Math.max(0, parseFloat(this.openingQty) || 0) },
        difference() { return Number((this.value() - this.carry).toFixed(2)) },
        report() { registerState(this.id, this.difference(), Math.abs(this.value() - (parseFloat(this.initialQty) || 0)) > .0001) },
        change(direction) {
            const step = ['kg', 'l'].includes(this.unit) ? .01 : 1;
            this.openingQty = Number(Math.max(0, this.value() + (direction * step)).toFixed(3));
            this.report();
        }
    }" x-init="report()" x-show="visibleFor({{ $ingredient->id }}, {{ $loop->index }})" x-cloak
    data-stock-ingredient="{{ $ingredient->id }}"
    class="border-b border-slate-100 bg-white last:border-0 dark:border-slate-800 dark:bg-slate-900">
    <div class="grid gap-3 p-3.5 md:grid-cols-[minmax(220px,1.35fr)_130px_minmax(220px,1fr)_170px] md:items-center md:px-5 md:py-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h3 class="truncate text-sm font-extrabold text-slate-900 dark:text-white">{{ $ingredient->name }}</h3>
                @if($unit === 'pcs' && $packSize > 1)
                    <span class="group/unit relative inline-flex h-5 w-5 shrink-0 cursor-help items-center justify-center rounded-full text-slate-400 outline-none hover:text-blue-600 focus:text-blue-600" tabindex="0" aria-label="Informasi konversi satuan">
                        <i class="fa-solid fa-circle-info text-xs"></i>
                        <span role="tooltip" class="pointer-events-none invisible absolute left-0 top-7 z-40 w-56 rounded-xl bg-slate-950 px-3 py-2 text-[11px] font-semibold leading-relaxed text-white opacity-0 shadow-xl transition group-hover/unit:visible group-hover/unit:opacity-100 group-focus/unit:visible group-focus/unit:opacity-100">1 PACK = {{ $packSize }} PCS. Input saldo awal tetap menggunakan PCS.</span>
                    </span>
                @endif
            </div>
            <div class="mt-1 flex flex-wrap gap-x-2 text-[11px] text-slate-500 dark:text-slate-400">
                <span>Gudang <strong class="text-slate-700 dark:text-slate-200">{{ number_format($warehouse, 2, ',', '.') }} {{ strtoupper($ingredient->transfer_stock_unit) }}</strong></span><span>•</span>
                <span>Saldo Sesi: <strong class="text-emerald-600 dark:text-emerald-400">{{ $opening }} {{ $unitLabel }}</strong></span>
                @if((float) $ingredient->transferred_to_session_value > 0)
                    <span>•</span><span>Sudah tambah: <strong class="text-blue-600 dark:text-blue-400">{{ $transferred }} {{ $unitLabel }}</strong></span>
                @endif
            </div>
        </div>
        <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/50 md:block md:bg-transparent md:p-0 md:dark:bg-transparent">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Sisa Kemarin</span>
            <strong class="text-sm font-black tabular-nums text-slate-700 dark:text-slate-200 md:mt-1 md:block">{{ $carry }} <small class="text-[10px] text-slate-400">{{ $unitLabel }}</small></strong>
        </div>
        <div>
            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400 md:sr-only">Stok Awal Hari Ini</label>
            <div class="flex h-11 items-center rounded-xl border border-slate-200 bg-slate-50 p-1 shadow-inner focus-within:border-blue-400 dark:border-slate-700 dark:bg-slate-950">
                <button type="button" @click="change(-1)" aria-label="Kurangi {{ $ingredient->name }}" class="flex h-9 w-10 items-center justify-center rounded-lg bg-white text-lg font-black text-slate-600 shadow-sm active:scale-95 dark:bg-slate-800 dark:text-slate-300">&minus;</button>
                <div class="flex min-w-0 flex-1 items-center px-2">
                    <input type="number" x-model="openingQty" @input.debounce.100ms="report()" name="transfers[{{ $ingredient->id }}][opening_quantity]" min="0" step="{{ in_array($unit, ['kg', 'l'], true) ? '0.01' : '1' }}" class="w-full border-0 bg-transparent p-0 text-center text-base font-black tabular-nums text-slate-900 outline-none focus:ring-0 dark:text-white">
                    <span class="text-[10px] font-extrabold text-slate-400">{{ $unitLabel }}</span>
                </div>
                <button type="button" @click="change(1)" aria-label="Tambah {{ $ingredient->name }}" class="flex h-9 w-10 items-center justify-center rounded-lg bg-white text-lg font-black text-blue-600 shadow-sm active:scale-95 dark:bg-slate-800 dark:text-blue-400">+</button>
            </div>
        </div>
        <div class="flex items-center justify-between gap-2 md:justify-end">
            <div class="text-[11px] font-bold">
                <span x-show="difference() > 0" class="text-blue-600 dark:text-blue-400"><i class="fa-solid fa-arrow-up mr-1 text-[9px]"></i>Tambahan Gudang <b x-text="difference() + ' {{ $unitLabel }}'"></b></span>
                <span x-show="difference() === 0" class="text-emerald-600 dark:text-emerald-400"><i class="fa-solid fa-check mr-1 text-[9px]"></i>Sesuai</span>
                <span x-show="difference() < 0" class="text-rose-600 dark:text-rose-400"><i class="fa-solid fa-triangle-exclamation mr-1 text-[9px]"></i>Kurang <b x-text="Math.abs(difference()) + ' {{ $unitLabel }}'"></b></span>
            </div>
            <button type="button" @click="activeIngredient = activeIngredient === id ? null : id" :aria-expanded="activeIngredient === id" class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 px-3 text-[11px] font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300">Detail <i class="fa-solid fa-chevron-down text-[9px] transition" :class="activeIngredient === id ? 'rotate-180' : ''"></i></button>
        </div>
    </div>
    <div x-show="activeIngredient === id" x-transition class="border-t border-slate-100 bg-slate-50/70 p-3.5 dark:border-slate-800 dark:bg-slate-950/40 md:px-5">
        <div class="grid gap-3 md:grid-cols-[repeat(3,1fr)_2fr] md:items-end">
            @foreach([['Gudang', number_format($warehouse, 2, ',', '.').' '.strtoupper($ingredient->transfer_stock_unit)], ['Saldo Sesi', $opening.' '.$unitLabel], ['Sudah Tambah', $transferred.' '.$unitLabel]] as [$label, $value])
                <div class="rounded-lg border border-slate-200 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-900"><small class="block text-[9px] font-bold uppercase text-slate-400">{{ $label }}</small><strong class="text-xs text-slate-700 dark:text-slate-200">{{ $value }}</strong></div>
            @endforeach
            <div>
                <label for="transfer-note-{{ $ingredient->id }}" class="mb-1 block text-[10px] font-bold uppercase text-slate-500">Catatan <span class="font-medium normal-case text-slate-400">(opsional)</span></label>
                <input id="transfer-note-{{ $ingredient->id }}" type="text" name="transfers[{{ $ingredient->id }}][note]" value="{{ old("transfers.{$ingredient->id}.note") }}" placeholder="Contoh: rusak atau selisih fisik" class="h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs text-slate-700 outline-none focus:border-blue-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            </div>
        </div>
    </div>
</article>
