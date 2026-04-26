@php
    $unitGroups = [
        'Berat' => [
            'g' => 'Gram (g)',
            'kg' => 'Kilogram (kg)',
        ],
        'Volume' => [
            'ml' => 'Mililiter (ml)',
            'l' => 'Liter (l)',
        ],
        'Satuan Gudang' => [
            'pcs' => 'Pack / Pcs',
        ],
    ];

    $selectedUnit = old('display_unit', $ingredient->display_unit ?? '');
    $packSize = (int) old('pack_size', $ingredient->pack_size ?? 1);
    if ($packSize < 1) $packSize = 1;

    $displayStock = old('stock');
    $displayMinimum = old('minimum_stock');

    if (!isset($displayStock) && isset($ingredient)) {
        if (in_array($ingredient->display_unit, ['kg', 'l'], true)) {
            $displayStock = $ingredient->stock / 1000;
            $displayMinimum = $ingredient->minimum_stock / 1000;
        } elseif (($ingredient->display_unit ?? '') === 'pcs' && $packSize > 0) {
            $displayStock = $ingredient->stock / $packSize;
            $displayMinimum = $ingredient->minimum_stock / $packSize;
        } else {
            $displayStock = $ingredient->stock;
            $displayMinimum = $ingredient->minimum_stock;
        }
    }

    $selectedCategory = old('category_id', $ingredient->category_id ?? '');
@endphp

<div class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    
    <form method="POST" action="{{ $action }}" 
          x-data="{ 
              submitting: false,
              unit: '{{ $selectedUnit }}',
              initialUnit: '{{ $selectedUnit }}',
              packSize: {{ $packSize }},
              stock: '{{ $displayStock ?? '' }}',
              minStock: '{{ $displayMinimum ?? '' }}',
              sellingPrice: {{ old('selling_price', isset($ingredient) ? (int) $ingredient->selling_price : 0) }},
              unitChanged: false,
              get showPriceWarning() {
                  return this.unitChanged && this.sellingPrice > 0;
              }
          }" 
          @submit="submitting = true">
        
        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

        <div class="p-6 md:p-8 space-y-10">
            
            {{-- ================= 1. INFORMASI DASAR ================= --}}
            <div class="space-y-6">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Informasi Dasar</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            Nama Bahan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               value="{{ old('name', $ingredient->name ?? '') }}"
                               placeholder="Contoh: Saus Tomat Del Monte"
                               required
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                        @error('name')
                            <p class="text-rose-500 text-xs font-medium mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            Kategori Bahan <span class="text-rose-500">*</span>
                        </label>
                        <select name="category_id" required
                                class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                            <option value="">Pilih Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $selectedCategory == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="text-rose-500 text-xs font-medium mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            Harga Jual (Rp) <span class="text-slate-500 font-normal">(Opsional)</span>
                        </label>
                        <input type="number"
                               name="selling_price"
                               x-model="sellingPrice"
                               @input="unitChanged = false"
                               value="{{ old('selling_price', isset($ingredient) ? (int) $ingredient->selling_price : 0) }}"
                               placeholder="0"
                               min="0" step="1"
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400" x-text="
                            unit === 'kg'  ? 'Harga per Kilogram (kg). Contoh: 20000 = Rp 20.000 / kg.' :
                            unit === 'l'   ? 'Harga per Liter (l). Contoh: 15000 = Rp 15.000 / liter.' :
                            unit === 'g'   ? 'Harga per Gram (g). Contoh: 20 = Rp 20 / gram.' :
                            unit === 'ml'  ? 'Harga per Mililiter (ml). Contoh: 15 = Rp 15 / ml.' :
                            unit === 'pcs' ? 'Harga per Pack. Contoh: 6000 = Rp 6.000 / pack.' :
                            'Isi 0 jika bahan tidak dijual secara terpisah.'
                        "></p>
                        @error('selling_price')
                            <p class="text-rose-500 text-xs font-medium mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- ================= 2. INFORMASI STOK & SATUAN ================= --}}
            <div class="space-y-6">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h2 class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Parameter Stok & Satuan</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            Jenis Satuan <span class="text-rose-500">*</span>
                        </label>
                        <select name="display_unit"
                                x-model="unit"
                                required
                                @change="unitChanged = (unit !== initialUnit)"
                                class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                            <option value="" disabled>Pilih Satuan</option>
                            @foreach($unitGroups as $groupLabel => $groupUnits)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach($groupUnits as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400" x-text="unit === 'pcs' ? 'Mode gudang aktif. Input stok menggunakan satuan Pack.' : 'Pilih satuan sesuai kebiasaan input di dapur/gudang.'" x-show="!showPriceWarning"></p>

                        {{-- Warning saat satuan diubah dan harga sudah terisi --}}
                        <div x-show="showPriceWarning" x-transition
                             class="mt-2 flex items-start gap-2.5 rounded-lg border border-amber-300 bg-amber-50 px-3.5 py-2.5 dark:border-amber-700/50 dark:bg-amber-900/20">
                            <svg class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-[12px] font-bold text-amber-800 dark:text-amber-300">Satuan berubah — periksa Harga Jual!</p>
                                <p class="text-[11px] text-amber-700 dark:text-amber-400 mt-0.5">Harga jual yang sudah diisi mungkin tidak sesuai dengan satuan baru. Pastikan nilainya sudah benar sebelum menyimpan.</p>
                            </div>
                            <button type="button" @click="unitChanged = false" class="shrink-0 text-amber-500 hover:text-amber-700 dark:text-amber-400">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        @error('display_unit')
                            <p class="text-rose-500 text-xs font-medium mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="unit === 'pcs'" x-transition x-cloak>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            Isi per Pack (Pcs) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               name="pack_size"
                               x-model="packSize"
                               min="1" step="1"
                               :required="unit === 'pcs'"
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                        <p class="mt-1.5 text-xs font-medium text-blue-600 dark:text-blue-400">
                            1 Pack = <span x-text="packSize || 0"></span> pcs
                        </p>
                        @error('pack_size')
                            <p class="text-rose-500 text-xs font-medium mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            <span x-text="unit === 'pcs' ? 'Stok Saat Ini (Pack)' : 'Stok Saat Ini'"></span> <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               name="stock"
                               x-model="stock"
                               placeholder="0.00"
                               required
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                        
                        <template x-if="unit === 'pcs' && stock !== ''">
                            <p class="mt-1.5 text-[11px] font-semibold text-slate-500" x-transition>
                                Tersimpan di sistem: <span class="text-slate-700 dark:text-slate-300" x-text="(Number(stock) * Number(packSize)).toLocaleString('id-ID') + ' pcs'"></span>
                            </p>
                        </template>
                        @error('stock')
                            <p class="text-rose-500 text-xs font-medium mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                            <span x-text="unit === 'pcs' ? 'Batas Minimum Stok (Pack)' : 'Batas Minimum Stok'"></span> <span class="text-rose-500">*</span>
                        </label>
                        <input type="number"
                               step="0.01"
                               name="minimum_stock"
                               x-model="minStock"
                               placeholder="0.00"
                               required
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                        
                        <template x-if="unit === 'pcs' && minStock !== ''">
                            <p class="mt-1.5 text-[11px] font-semibold text-slate-500" x-transition>
                                Peringatan jika stok dibawah: <span class="text-slate-700 dark:text-slate-300" x-text="(Number(minStock) * Number(packSize)).toLocaleString('id-ID') + ' pcs'"></span>
                            </p>
                        </template>
                        @error('minimum_stock')
                            <p class="text-rose-500 text-xs font-medium mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- ================= ACTION BUTTONS ================= --}}
        <div class="flex flex-col-reverse gap-3 px-6 py-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 sm:flex-row sm:items-center sm:justify-end">
            
            <a href="{{ route('admin.ingredients.index') }}"
               class="inline-flex w-full items-center justify-center rounded-xl bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700">
                Batal
            </a>

            <button type="submit"
                    :disabled="submitting"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto shadow-blue-500/20">

                <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>

                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>

                <span x-text="submitting ? 'Menyimpan...' : '{{ $buttonText }}'"></span>
            </button>

        </div>
    </form>
</div>