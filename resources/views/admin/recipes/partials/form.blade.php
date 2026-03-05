<div class="bg-white dark:bg-slate-900
            border border-slate-200 dark:border-slate-800
            rounded-2xl shadow-sm p-6 md:p-8">

<form method="POST"
      action="{{ route('admin.recipes.update', $variant->id) }}"
      class="space-y-8">
    @csrf
    @method('PUT')

    @if($errors->has('ingredients'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('ingredients') }}
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

    <div x-data="{ openCategory: {{ $firstActiveCategory?->id ?? 'null' }} }"
         class="space-y-6">

        @foreach($ingredientCategories as $category)

            @php
                $activeCount = $category->ingredients->filter(function($ingredient) use ($variant){
                    $existing = $variant->ingredients->firstWhere('id',$ingredient->id);
                    return $existing && $existing->pivot->quantity > 0;
                })->count();
            @endphp

            <div class="border border-slate-200 dark:border-slate-800
                        rounded-xl overflow-hidden">

                {{-- CATEGORY HEADER --}}
                <button type="button"
                    @click="openCategory === {{ $category->id }}
                            ? openCategory = null
                            : openCategory = {{ $category->id }}"
                    class="w-full px-5 py-4 flex justify-between items-center
                           bg-slate-50 dark:bg-slate-800
                           hover:bg-slate-100 dark:hover:bg-slate-700
                           transition">

                    <div class="flex items-center gap-3">
                        <span class="font-medium text-slate-700 dark:text-white">
                            {{ $category->name }}
                        </span>

                        @if($activeCount > 0)
                            <span
                                class="text-xs bg-blue-100 text-blue-600
                                       dark:bg-blue-900/30 dark:text-blue-300
                                       px-2 py-0.5 rounded-full
                                       transition-all duration-300"
                                :class="openCategory === {{ $category->id }}
                                         ? 'scale-110 shadow-md'
                                         : ''">
                                {{ $activeCount }}
                            </span>
                        @endif
                    </div>

                    <span :class="openCategory === {{ $category->id }} ? 'rotate-180' : ''"
                          class="transition-transform text-slate-400 text-lg">
                        v
                    </span>
                </button>


                {{-- INGREDIENT LIST --}}
                <div x-show="openCategory === {{ $category->id }}"
                     x-collapse
                     x-cloak
                     class="p-5 space-y-4 bg-white dark:bg-slate-900">

                    @foreach($category->ingredients as $ingredient)

                        @php
                            $existing = $variant->ingredients->firstWhere('id', $ingredient->id);
                            $quantity = $existing ? $existing->pivot->quantity : 0;
                        @endphp

                        <div class="p-4 rounded-xl border
                                    {{ $quantity > 0
                                        ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                                        : 'border-slate-200 dark:border-slate-700' }}">

                            {{-- MOBILE & DESKTOP FLEX LAYOUT --}}
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

                                {{-- NAME --}}
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ $ingredient->name }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ $ingredient->base_unit }}
                                    </p>
                                </div>

                                {{-- INPUT + UNIT --}}
                                <div class="flex items-center gap-2 w-full md:w-auto">

                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="ingredients[{{ $ingredient->id }}]"
                                           value="{{ old('ingredients.' . $ingredient->id, $quantity) }}"
                                           class="w-full md:w-32 px-4 py-2.5 rounded-xl
                                                  border {{ $errors->has('ingredients.' . $ingredient->id) ? 'border-red-400' : 'border-slate-300 dark:border-slate-700' }}
                                                  bg-white dark:bg-slate-800
                                                  text-sm
                                                  focus:ring-2 focus:ring-blue-500
                                                  focus:outline-none transition">

                                    <span class="text-sm text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        {{ $ingredient->base_unit }}
                                    </span>

                                </div>
                                @if($errors->has('ingredients.' . $ingredient->id))
                                    <p class="text-xs text-red-600">
                                        {{ $errors->first('ingredients.' . $ingredient->id) }}
                                    </p>
                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        @endforeach

    </div>


    {{-- STICKY ACTION --}}
    <div class="sticky bottom-0 bg-white dark:bg-slate-900
                pt-6 border-t border-slate-200 dark:border-slate-800
                flex justify-end gap-3">

        <a href="{{ route('admin.recipes.index') }}"
           class="px-6 py-2.5 rounded-xl
                  bg-slate-200 dark:bg-slate-700
                  text-sm hover:bg-slate-300 dark:hover:bg-slate-600 transition">
            Batal
        </a>

        <button type="submit"
                class="px-8 py-2.5 rounded-xl
                       bg-blue-600 text-white text-sm font-medium
                       hover:bg-blue-700 transition shadow-sm">
            Simpan Resep
        </button>

    </div>

</form>

</div>
