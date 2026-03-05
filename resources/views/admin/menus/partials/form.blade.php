<div class="bg-white dark:bg-slate-900
            border border-slate-200 dark:border-slate-800
            rounded-2xl shadow-sm">

<form method="POST"
      enctype="multipart/form-data"
      action="{{ $action }}">
@csrf
@if($method === 'PUT')
    @method('PUT')
@endif

<div class="p-8 space-y-10">

    {{-- VALIDATION ERROR --}}
    @if ($errors->any())
        <div class="p-4 rounded-xl
                    bg-red-50 border border-red-200
                    text-red-700 text-sm">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- SECTION TITLE --}}
    <div class="border-b border-slate-200 dark:border-slate-800 pb-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            Informasi Menu
        </h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- KATEGORI --}}
        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Kategori
            </label>

            <select name="category_id"
                    required
                    class="w-full px-4 py-3 rounded-xl
                           border border-slate-300 dark:border-slate-700
                           bg-white dark:bg-slate-800
                           focus:ring-2 focus:ring-blue-500">

                <option value="">Pilih Kategori</option>

                @foreach($categories as $category)
                    <option value="{{ $category->id }}"
                        {{ old('category_id', $menu->category_id ?? '') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- NAMA --}}
        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Nama Menu
            </label>

            <input type="text"
                   name="name"
                   value="{{ old('name', $menu->name ?? '') }}"
                   required
                   class="w-full px-4 py-3 rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-800
                          focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- URUTAN --}}
        <div>
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Urutan
            </label>

            <input type="number"
                   name="sort_order"
                   min="0"
                   value="{{ old('sort_order', $menu->sort_order ?? 0) }}"
                   class="w-full px-4 py-3 rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-800
                          focus:ring-2 focus:ring-blue-500">
        </div>

        {{-- STATUS --}}
        <div class="flex items-center gap-3 mt-8">
            <input type="checkbox"
                   name="is_active"
                   value="1"
                   {{ old('is_active', $menu->is_active ?? true) ? 'checked' : '' }}
                   class="w-4 h-4">

            <label class="text-sm text-slate-600 dark:text-slate-300">
                Aktif
            </label>
        </div>

        {{-- FOTO --}}
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Foto
            </label>

            <input type="file"
                   name="image"
                   accept="image/*"
                   class="text-sm">

            {{-- PREVIEW --}}
            @if(isset($menu) && $menu->image_path)
                <div class="mt-4">
                    <img src="{{ asset('storage/'.$menu->image_path) }}"
                         class="w-32 h-32 object-cover rounded-xl border">
                </div>
            @endif
        </div>

        {{-- DESKRIPSI --}}
        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
                Deskripsi
            </label>

            <textarea name="description"
                      rows="4"
                      class="w-full px-4 py-3 rounded-xl
                             border border-slate-300 dark:border-slate-700
                             bg-white dark:bg-slate-800
                             focus:ring-2 focus:ring-blue-500">{{ old('description', $menu->description ?? '') }}</textarea>
        </div>

    </div>

</div>

{{-- FOOTER ACTION --}}
<div class="flex justify-between items-center
            px-8 py-6
            bg-slate-50 dark:bg-slate-800
            border-t border-slate-200 dark:border-slate-700
            rounded-b-2xl">

    <a href="{{ route('admin.menus.index') }}"
       class="text-sm text-slate-500 hover:text-blue-600 transition">
        ← Kembali
    </a>

    <button type="submit"
            class="px-8 py-3 rounded-xl
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