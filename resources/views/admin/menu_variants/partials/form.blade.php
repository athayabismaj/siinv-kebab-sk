<div class="bg-white dark:bg-slate-900
            border border-slate-200 dark:border-slate-800
            rounded-2xl shadow-sm">

<form method="POST" action="{{ $action }}">
@csrf
@if($method === 'PUT')
    @method('PUT')
@endif

<div class="p-8 space-y-8">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        {{-- Nama --}}
        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Nama Variant
            </label>

            <input type="text"
                   name="name"
                   value="{{ old('name', $menuVariant->name ?? '') }}"
                   required
                   class="w-full px-4 py-3 rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-800
                          focus:ring-2 focus:ring-blue-500">

            @error('name')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- Harga --}}
        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Harga
            </label>

            <input type="number"
                   name="price"
                   min="0"
                   required
                   value="{{ old('price', $menuVariant->price ?? 0) }}"
                   class="w-full px-4 py-3 rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-800
                          focus:ring-2 focus:ring-blue-500">

            @error('price')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        {{-- Urutan --}}
        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Urutan
            </label>

            <input type="number"
                   name="sort_order"
                   min="0"
                   value="{{ old('sort_order', $menuVariant->sort_order ?? 0) }}"
                   class="w-full px-4 py-3 rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-800
                          focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- Status --}}
        <div class="flex items-center gap-3 mt-8">
            <input type="checkbox"
                   name="is_available"
                   value="1"
                   {{ old('is_available', $menuVariant->is_available ?? true) ? 'checked' : '' }}
                   class="w-4 h-4">

            <label class="text-sm text-slate-600 dark:text-slate-300">
                Tersedia
            </label>
        </div>

    </div>

</div>

<div class="flex justify-between items-center
            px-8 py-6
            bg-slate-50 dark:bg-slate-800
            border-t border-slate-200 dark:border-slate-700
            rounded-b-2xl">

    <a href="{{ route('admin.menu-variants.index', $menu->id) }}"
       class="text-sm text-slate-500 hover:text-blue-600 transition">
        ← Kembali
    </a>

    <button type="submit"
            class="px-6 py-2.5 rounded-xl
                   bg-blue-600 text-white
                   text-sm font-medium
                   hover:bg-blue-700 transition">
        {{ $buttonText }}
    </button>

</div>

</form>
</div>