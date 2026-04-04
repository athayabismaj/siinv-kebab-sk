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
              packSize: {{ $packSize }},
              stock: '{{ $displayStock ?? '' }}',
              minStock: '{{ $displayMinimum ?? '' }}'
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
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400" x-text="unit === 'pcs' ? 'Mode gudang aktif. Input stok menggunakan satuan Pack.' : 'Pilih satuan sesuai kebiasaan input di dapur/gudang.'"></p>
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