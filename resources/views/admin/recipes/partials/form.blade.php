<form method="POST" action="{{ route('admin.recipes.update', $variant->id) }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    @method('PUT')

    {{-- Global Error --}}
    @if($errors->has('ingredients'))
        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800 dark:border-rose-900/50 dark:bg-rose-900/20 dark:text-rose-300 shadow-sm">
            <svg class="h-5 w-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <div>{{ $errors->first('ingredients') }}</div>
        </div>
    @endif

    @php
        $firstActiveCategory = $ingredientCategories->first(function($cat) use ($variant){
            return $cat->ingredients->contains(function($ingredient) use ($variant){
                $existing = $variant->ingredients->firstWhere('id', $ingredient->id);
                return $existing && $existing->pivot->quantity > 0;
            });
        });
    @endphp

    {{-- ACCORDION KATEGORI BAHAN --}}
    <div x-data="{ openCategory: {{ $firstActiveCategory?->id ?? 'null' }} }" class="space-y-4">
        
        @foreach($ingredientCategories as $category)
            @php
                $activeCount = $category->ingredients->filter(function($ingredient) use ($variant){
                    $existing = $variant->ingredients->firstWhere('id',$ingredient->id);
                    return $existing && $existing->pivot->quantity > 0;
                })->count();
            @endphp

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden transition-all duration-200">
                
                {{-- HEADER KATEGORI --}}
                <button type="button"
                        @click="openCategory === {{ $category->id }} ? openCategory = null : openCategory = {{ $category->id }}"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left bg-slate-50/50 hover:bg-slate-100 dark:bg-slate-800/30 dark:hover:bg-slate-800/50 transition-colors group">
                    
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-slate-800 dark:text-white text-[15px]">
                            {{ $category->name }}
                        </span>
                        
                        @if($activeCount > 0)
                            <span class="px-2.5 py-0.5 rounded-md bg-blue-50 border border-blue-100 dark:bg-blue-900/30 dark:border-blue-800/50 text-[10px] font-bold text-blue-600 dark:text-blue-400 tracking-wider shadow-sm transition-all duration-300"
                                  :class="openCategory === {{ $category->id }} ? 'scale-105 shadow-md' : ''">
                                Terpilih: {{ $activeCount }}
                            </span>
                        @endif
                    </div>

                    <div class="h-6 w-6 flex items-center justify-center rounded-full bg-white border border-slate-200 shadow-sm dark:bg-slate-800 dark:border-slate-700 text-slate-500 shrink-0 group-hover:bg-slate-50 dark:group-hover:bg-slate-700 transition-colors">
                        <svg :class="openCategory === {{ $category->id }} ? 'rotate-180' : ''"
                             class="w-3.5 h-3.5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                {{-- INPUT BAHAN (GRID) --}}
                <div x-show="openCategory === {{ $category->id }}" x-collapse x-cloak class="border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <div class="p-5 sm:p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                        @foreach($category->ingredients as $ingredient)
                            @php
                                $existing = $variant->ingredients->firstWhere('id', $ingredient->id);
                                $quantity = $existing ? $existing->pivot->quantity : 0;
                                $hasError = $errors->has('ingredients.' . $ingredient->id);
                            @endphp

                            <div class="relative p-4 rounded-xl border transition-all duration-200
                                        {{ $quantity > 0 
                                            ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800/50 shadow-sm' 
                                            : ($hasError ? 'bg-rose-50/50 dark:bg-rose-900/10 border-rose-300 dark:border-rose-800/50' : 'bg-white dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600') }}">
                                
                                <div class="flex flex-col gap-3">
                                    {{-- Nama Bahan --}}
                                    <div>
                                        <p class="text-[13px] font-bold text-slate-800 dark:text-slate-200 leading-tight">
                                            {{ $ingredient->name }}
                                        </p>
                                    </div>

                                    {{-- Input Kuantitas --}}
                                    <div class="flex items-center gap-2">
                                        <div class="relative flex-1">
                                            <input type="number"
                                                   step="0.01"
                                                   min="0"
                                                   name="ingredients[{{ $ingredient->id }}]"
                                                   value="{{ old('ingredients.' . $ingredient->id, $quantity > 0 ? (float) $quantity : '') }}"
                                                   placeholder="0.00"
                                                   class="w-full rounded-lg border {{ $hasError ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/10' : 'border-slate-300 dark:border-slate-600 focus:border-blue-500 focus:ring-blue-500/10' }} bg-white py-2 pl-3 pr-10 text-slate-900 shadow-sm outline-none transition dark:bg-slate-900 dark:text-white sm:text-sm tabular-nums">
                                            
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $ingredient->base_unit }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @if($hasError)
                                        <p class="text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                                            {{ $errors->first('ingredients.' . $ingredient->id) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        @endforeach

    </div>

    {{-- ================= NATURAL FOOTER ACTION BAR (DI BAWAH KONTEN) ================= --}}
    <div class="mt-8 flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-800">
        
        <a href="{{ route('admin.recipes.index') }}"
           class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 border border-slate-300 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700 shadow-sm">
            Batal
        </a>

        <button type="submit"
                :disabled="submitting"
                class="inline-flex w-full sm:w-auto min-w-[140px] items-center justify-center gap-2 rounded-xl bg-blue-600 px-8 py-3 text-sm font-bold text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70">
            
            <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>

            <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            <span x-text="submitting ? 'Menyimpan...' : 'Simpan Resep'"></span>
        </button>
    </div>

</form>