@php
    $units = [
        'g' => 'Gram (g)',
        'kg' => 'Kilogram (kg)',
        'ml' => 'Mililiter (ml)',
        'l' => 'Liter (l)',
        'pcs' => 'PCS',
    ];

    $selectedUnit = old('display_unit', $ingredient->display_unit ?? '');

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

    $selectedCategory = old('category_id', $ingredient->category_id ?? '');
@endphp


<div class="bg-white dark:bg-slate-900
            border border-slate-200 dark:border-slate-800
            rounded-2xl shadow-sm">

<form method="POST" action="{{ $action }}">
@csrf
@if($method === 'PUT')
    @method('PUT')
@endif

<div class="p-8 space-y-10">

    {{-- ================= BASIC INFO SECTION ================= --}}
    <div class="space-y-6">

        <div class="border-b border-slate-200 dark:border-slate-800 pb-4">
            <h2 class="text-sm font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                Informasi Dasar
            </h2>
        </div>

        {{-- Nama --}}
        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
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
                          focus:ring-2 focus:ring-blue-500
                          focus:outline-none transition">

            @error('name')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- Kategori --}}
        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Kategori
            </label>

            <select name="category_id"
                    class="w-full px-4 py-3 rounded-xl
                           border border-slate-300 dark:border-slate-700
                           bg-white dark:bg-slate-800
                           text-slate-800 dark:text-white
                           focus:ring-2 focus:ring-blue-500">

                <option value="">Tanpa Kategori</option>

                @foreach($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ $selectedCategory == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach

            </select>

            @error('category_id')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

    </div>


    {{-- ================= STOCK SECTION ================= --}}
    <div class="space-y-6">

        <div class="border-b border-slate-200 dark:border-slate-800 pb-4">
            <h2 class="text-sm font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                Informasi Stok
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Satuan --}}
            <div>
                <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                    Satuan
                </label>

                <select name="display_unit"
                        required
                        class="w-full px-4 py-3 rounded-xl
                               border border-slate-300 dark:border-slate-700
                               bg-white dark:bg-slate-800
                               text-slate-800 dark:text-white
                               focus:ring-2 focus:ring-blue-500">

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
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- Stok --}}
            <div>
                <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
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
                              focus:ring-2 focus:ring-blue-500">

                @error('stock')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            {{-- Minimum --}}
            <div>
                <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
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
                              focus:ring-2 focus:ring-blue-500">

                @error('minimum_stock')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

        </div>

    </div>

</div>


{{-- ================= FOOTER ACTION ================= --}}
<div class="flex justify-between items-center
            px-8 py-6
            bg-slate-50 dark:bg-slate-800
            border-t border-slate-200 dark:border-slate-700
            rounded-b-2xl">

    <a href="{{ route('admin.ingredients.index') }}"
       class="text-sm text-slate-500 hover:text-blue-600 transition">
        ← Kembali
    </a>

    <button type="submit"
            class="px-6 py-2.5 rounded-xl
                   bg-blue-600 text-white
                   text-sm font-medium
                   hover:bg-blue-700
                   active:scale-[0.98]
                   transition">
        {{ $buttonText }}
    </button>

</div>

</form>
</div>