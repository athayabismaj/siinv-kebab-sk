<div class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    
    {{-- Card Header --}}
    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
        <h2 class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Informasi Varian</h2>
    </div>

    <form method="POST"
          action="{{ $action }}"
          enctype="multipart/form-data"
          x-data="{
              submitting: false,
              originalImage: @js(isset($menuVariant) ? $menuVariant->image_url : null),
              imagePreview: @js(old('remove_image') ? null : (isset($menuVariant) ? $menuVariant->image_url : null)),
              imageName: '',
              removeImage: @js((bool) old('remove_image', false)),
              selectImage(event) {
                  const file = event.target.files[0];
                  if (!file) return;
                  if (this.imagePreview && this.imagePreview.startsWith('blob:')) URL.revokeObjectURL(this.imagePreview);
                  this.imagePreview = URL.createObjectURL(file);
                  this.imageName = file.name;
                  this.removeImage = false;
              },
              toggleRemoveImage() {
                  this.removeImage = !this.removeImage;
                  if (this.removeImage) {
                      if (this.imagePreview && this.imagePreview.startsWith('blob:')) URL.revokeObjectURL(this.imagePreview);
                      this.imagePreview = null;
                      this.imageName = '';
                      this.$refs.imageInput.value = '';
                  } else {
                      this.imagePreview = this.originalImage;
                  }
              }
          }"
          @submit="submitting = true">
        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

        {{-- Global Validation Error --}}
        @if ($errors->any())
            <div class="mx-6 md:mx-8 mt-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300 shadow-sm">
                <svg class="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <div>
                    <p class="font-bold mb-1">Terdapat kesalahan pada input Anda:</p>
                    <ul class="list-disc list-inside space-y-1 ml-1 text-xs">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Body Form --}}
        <div class="p-6 md:p-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                
                {{-- Nama Varian --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                        Nama Varian <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $menuVariant->name ?? '') }}"
                           placeholder="Contoh: Reguler, Medium, Large, Pedas..."
                           required
                           class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">
                    @error('name')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Gambar Varian --}}
                <div class="md:col-span-2 space-y-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <label for="variant-image" class="text-sm font-semibold text-slate-800 dark:text-slate-200">Gambar Varian</label>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">Opsional</span>
                            </div>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">Gambar ini ditampilkan pada kartu varian di menu POS.</p>
                        </div>
                        <p class="text-[11px] font-medium text-slate-400 sm:pt-1">Disarankan rasio 1:1</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/30 sm:p-5">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div class="relative mx-auto h-36 w-36 shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:mx-0 sm:h-32 sm:w-32">
                                <img x-show="imagePreview" x-cloak :src="imagePreview" alt="Pratinjau gambar varian" class="h-full w-full object-cover">
                                <div x-show="!imagePreview" class="flex h-full w-full flex-col items-center justify-center gap-2 text-slate-400 dark:text-slate-500">
                                    <svg class="h-9 w-9 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[11px] font-semibold">Belum ada gambar</span>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1 text-center sm:text-left">
                                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200">Unggah foto produk</h3>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">Gunakan gambar yang terang dan fokus agar menu mudah dikenali oleh kasir.</p>

                                <input id="variant-image"
                                       x-ref="imageInput"
                                       type="file"
                                       name="image"
                                       accept="image/jpeg,image/png,image/webp"
                                       @change="selectImage($event)"
                                       class="sr-only">

                                <div class="mt-4 flex flex-col items-stretch gap-2 sm:flex-row sm:items-center">
                                    <button type="button"
                                            @click="$refs.imageInput.click()"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-indigo-500/20 transition hover:bg-indigo-700">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                        <span x-text="imagePreview ? 'Ganti gambar' : 'Pilih gambar'">Pilih gambar</span>
                                    </button>

                                    @if(isset($menuVariant) && $menuVariant->image_path)
                                        <input type="checkbox" name="remove_image" value="1" x-model="removeImage" class="sr-only">
                                        <button type="button"
                                                @click="toggleRemoveImage()"
                                                class="inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-xs font-bold transition"
                                                :class="removeImage ? 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200' : 'border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-500/10 dark:text-rose-400'"
                                                x-text="removeImage ? 'Batalkan hapus' : 'Hapus gambar'">Hapus gambar</button>
                                    @endif
                                </div>

                                <p x-show="imageName" x-cloak class="mt-2 truncate text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">
                                    File dipilih: <span x-text="imageName"></span>
                                </p>
                                <p class="mt-2 text-[11px] leading-relaxed text-slate-400">Format JPG, PNG, atau WebP · Ukuran maksimal 2 MB</p>
                            </div>
                        </div>
                    </div>

                    @error('image')
                        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Harga Modal (HPP) --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                        Harga Modal (HPP) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                            <span class="text-sm font-bold text-slate-400 dark:text-slate-500">Rp</span>
                        </div>
                        <input type="number"
                               name="cost_price"
                               min="0"
                               required
                               value="{{ old('cost_price', isset($menuVariant) ? $menuVariant->cost_price : '') == 0 ? '' : old('cost_price', isset($menuVariant) ? $menuVariant->cost_price : '') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-slate-900 font-medium shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm tabular-nums">
                    </div>
                    @error('cost_price')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Harga Jual --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                        Harga Jual <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                            <span class="text-sm font-bold text-slate-400 dark:text-slate-500">Rp</span>
                        </div>
                        <input type="number"
                               name="sell_price"
                               min="0"
                               required
                               value="{{ old('sell_price', isset($menuVariant) ? ($menuVariant->sell_price ?? $menuVariant->price) : '') == 0 ? '' : old('sell_price', isset($menuVariant) ? ($menuVariant->sell_price ?? $menuVariant->price) : '') }}"
                               class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-11 pr-4 text-slate-900 font-medium shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm tabular-nums">
                    </div>
                    @error('sell_price')
                        <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Urutan Tampil --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                        Urutan Tampil (Sort Order)
                    </label>
                    <input type="number"
                           name="sort_order"
                           min="0"
                           value="{{ old('sort_order', isset($menuVariant) ? $menuVariant->sort_order : '') == 0 ? '' : old('sort_order', isset($menuVariant) ? $menuVariant->sort_order : '') }}"
                           class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm tabular-nums">
                    <p class="mt-1.5 text-[11px] font-medium text-slate-500">Angka lebih kecil akan muncul lebih dulu di kasir.</p>
                </div>

                {{-- Status Aktif --}}
                <div class="flex flex-col justify-center">
                    <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-2">
                        Status Ketersediaan
                    </label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               name="is_available" 
                               value="1" 
                               class="sr-only peer"
                               {{ old('is_available', $menuVariant->is_available ?? true) ? 'checked' : '' }}>
                        
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-medium text-slate-700 dark:text-slate-300">Varian dapat dibeli</span>
                    </label>
                </div>

            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="flex flex-col-reverse gap-3 px-6 py-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 sm:flex-row sm:items-center sm:justify-end">
            
            <a href="{{ route('admin.menu-variants.index', $menu->id) }}"
               class="inline-flex w-full items-center justify-center rounded-xl bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700">
                Batal
            </a>

            <button type="submit"
                    :disabled="submitting"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-8 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto shadow-indigo-500/20">

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
