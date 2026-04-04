<div class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    
    {{-- Card Header --}}
    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
        <h2 class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Informasi Kategori</h2>
    </div>

    <form method="POST" action="{{ $action }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

        {{-- Body Form --}}
        <div class="p-6 md:p-8 space-y-6">
            <div>
                <label class="block text-sm font-semibold text-slate-800 dark:text-slate-200 mb-1.5">
                    Nama Kategori <span class="text-rose-500">*</span>
                </label>

                <input type="text"
                       name="name"
                       value="{{ old('name', $ingredientCategory->name ?? '') }}"
                       placeholder="Contoh: Sayuran, Daging, Saus..."
                       required
                       class="w-full rounded-xl border border-slate-300 bg-white py-3 px-4 text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-blue-500 sm:text-sm">

                @error('name')
                    <p class="mt-1.5 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="flex flex-col-reverse gap-3 px-6 py-5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 sm:flex-row sm:items-center sm:justify-end">
            
            <a href="{{ route('admin.ingredient-categories.index') }}"
               class="inline-flex w-full items-center justify-center rounded-xl bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700">
                Batal
            </a>

            <button type="submit"
                    :disabled="submitting"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto shadow-blue-500/20">

                {{-- Ikon Tampil Saat Normal --}}
                <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>

                {{-- Ikon Spinner Tampil Saat Loading --}}
                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>

                <span x-text="submitting ? 'Menyimpan...' : '{{ $buttonText }}'"></span>
            </button>

        </div>
    </form>
</div>