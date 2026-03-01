<div class="bg-white dark:bg-slate-900
            border border-slate-200 dark:border-slate-800
            rounded-2xl shadow-sm p-8 lg:p-10">

    <form method="POST"
          enctype="multipart/form-data"
          action="{{ $action }}"
          class="space-y-10">

        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

        {{-- GRID --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- NAMA MENU --}}
            <div class="lg:col-span-2">
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-2">
                    Nama Menu
                </label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $menu->name ?? '') }}"
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

            {{-- HARGA --}}
            <div>
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-2">
                    Harga (Rp)
                </label>
                <input type="number"
                       name="price"
                       value="{{ old('price', $menu->price ?? 0) }}"
                       required
                       class="w-full px-4 py-3 rounded-xl
                              border border-slate-300 dark:border-slate-700
                              bg-white dark:bg-slate-800
                              text-slate-800 dark:text-white
                              focus:outline-none focus:ring-2
                              focus:ring-blue-500 focus:border-blue-500
                              transition">
                @error('price')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- FOTO --}}
            <div>
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-2">
                    Foto Menu
                </label>

                <input type="file"
                       name="image"
                       accept="image/*"
                       class="w-full text-sm
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-xl file:border-0
                              file:text-sm file:font-medium
                              file:bg-blue-50 file:text-blue-600
                              hover:file:bg-blue-100">

                @if(isset($menu) && $menu->image_path)
                    <div class="mt-4">
                        <img src="{{ asset('storage/'.$menu->image_path) }}"
                             class="w-28 h-28 object-cover rounded-xl border
                                    border-slate-200 dark:border-slate-700">
                    </div>
                @endif

                @error('image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- DESKRIPSI --}}
            <div class="lg:col-span-2">
                <label class="block text-sm text-slate-600 dark:text-slate-300 mb-2">
                    Deskripsi
                </label>
                <textarea name="description"
                          rows="4"
                          class="w-full px-4 py-3 rounded-xl
                                 border border-slate-300 dark:border-slate-700
                                 bg-white dark:bg-slate-800
                                 text-slate-800 dark:text-white
                                 focus:outline-none focus:ring-2
                                 focus:ring-blue-500 focus:border-blue-500
                                 transition">{{ old('description', $menu->description ?? '') }}</textarea>
            </div>

        </div>

        {{-- BUTTON --}}
        <div class="flex justify-end gap-4 pt-6 border-t
                    border-slate-200 dark:border-slate-800">

            <a href="{{ route('admin.menus.index') }}"
               class="px-6 py-3 rounded-xl
                      bg-slate-200 dark:bg-slate-700
                      text-slate-700 dark:text-slate-200
                      hover:bg-slate-300 dark:hover:bg-slate-600
                      transition">
                Batal
            </a>

            <button type="submit"
                    class="px-8 py-3 rounded-xl
                           bg-blue-600 text-white
                           hover:bg-blue-700
                           active:scale-[0.98]
                           transition">
                {{ $buttonText }}
            </button>

        </div>

    </form>
</div>