@php
    $units = [
        'g' => 'Gram (g)',
        'kg' => 'Kilogram (kg)',
        'ml' => 'Mililiter (ml)',
        'l' => 'Liter (l)',
        'pcs' => 'PCS',
    ];

    $selectedUnit = old('display_unit', $ingredient->display_unit ?? '');

    // Konversi stok dari base unit ke display unit saat edit
    $displayStock = old('stock');
    $displayMinimum = old('minimum_stock');

    if (!isset($displayStock) && isset($ingredient)) {
        if (in_array($ingredient->display_unit, ['kg', 'l'])) {
            $displayStock = $ingredient->stock / 1000;
            $displayMinimum = $ingredient->minimum_stock / 1000;
        } else {
            $displayStock = $ingredient->stock;
            $displayMinimum = $ingredient->minimum_stock;
        }
    }
@endphp


<div class="bg-white dark:bg-slate-900
            border border-slate-200 dark:border-slate-800
            rounded-2xl shadow-sm p-8">

    <form method="POST" action="{{ $action }}" class="space-y-10">
        @csrf

        @if($method === 'PUT')
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- NAMA --}}
            <div class="lg:col-span-3">
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-2">
                    Nama Bahan
                </label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $ingredient->name ?? '') }}"
                       required
                       class="w-full px-4 py-3 rounded-xl
                              border border-slate-300 dark:border-slate-700
                              bg-white dark:bg-slate-800
                              text-slate-800 dark:text-white
                              focus:outline-none focus:ring-2
                              focus:ring-blue-500 focus:border-blue-500
                              transition">

                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- SATUAN --}}
            <div>
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-2">
                    Satuan
                </label>

                <select name="display_unit"
                        required
                        class="w-full px-4 py-3 rounded-xl
                               border border-slate-300 dark:border-slate-700
                               bg-white dark:bg-slate-800
                               text-slate-800 dark:text-white
                               focus:outline-none focus:ring-2
                               focus:ring-blue-500 focus:border-blue-500
                               transition">

                    <option value="" disabled {{ $selectedUnit ? '' : 'selected' }}>
                        Pilih Satuan
                    </option>

                    @foreach($units as $value => $label)
                        <option value="{{ $value }}"
                            {{ $selectedUnit == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>

                @error('display_unit')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- STOK --}}
            <div>
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-2">
                    Stok Saat Ini
                </label>
                <input type="number"
                       step="0.01"
                       name="stock"
                       value="{{ $displayStock ?? 0 }}"
                       required
                       class="w-full px-4 py-3 rounded-xl
                              border border-slate-300 dark:border-slate-700
                              bg-white dark:bg-slate-800
                              text-slate-800 dark:text-white
                              focus:outline-none focus:ring-2
                              focus:ring-blue-500 focus:border-blue-500
                              transition">

                @error('stock')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- MINIMUM STOK --}}
            <div>
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-2">
                    Minimum Stok
                </label>
                <input type="number"
                       step="0.01"
                       name="minimum_stock"
                       value="{{ $displayMinimum ?? 0 }}"
                       required
                       class="w-full px-4 py-3 rounded-xl
                              border border-slate-300 dark:border-slate-700
                              bg-white dark:bg-slate-800
                              text-slate-800 dark:text-white
                              focus:outline-none focus:ring-2
                              focus:ring-blue-500 focus:border-blue-500
                              transition">

                @error('minimum_stock')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

        </div>

        {{-- BUTTON --}}
        <div class="flex justify-end gap-4 pt-4 border-t border-slate-200 dark:border-slate-800">

            <a href="{{ route('admin.ingredients.index') }}"
               class="px-6 py-3 rounded-xl
                      bg-slate-200 dark:bg-slate-700
                      text-slate-700 dark:text-slate-200
                      text-sm
                      hover:bg-slate-300 dark:hover:bg-slate-600
                      transition">
                Batal
            </a>

            <button type="submit"
                    class="px-8 py-3 rounded-xl
                           bg-blue-600 text-white text-sm
                           hover:bg-blue-700
                           active:scale-[0.98]
                           transition">
                {{ $buttonText }}
            </button>

        </div>

    </form>

</div>