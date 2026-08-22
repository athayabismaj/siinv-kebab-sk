<div class="space-y-3">
    <form
        method="POST"
        action="{{ route('admin.recipes.update', $variant) }}"
        class="space-y-4"
        x-data="{
            submitting: false,
            draftKey: @js('recipe-draft-'.$variant->id),
            draft: {},
            init() {
                @if(session('success'))
                    sessionStorage.removeItem(this.draftKey);
                @endif
                try {
                    this.draft = JSON.parse(sessionStorage.getItem(this.draftKey) || '{}');
                } catch (error) {
                    this.draft = {};
                }
                this.$nextTick(() => {
                    Object.entries(this.draft).forEach(([id, value]) => {
                        const input = this.$el.querySelector(`[name='ingredients[${id}]']`);
                        if (input) input.value = value;
                    });
                });
            },
            remember(id, value) {
                this.draft[String(id)] = value;
                sessionStorage.setItem(this.draftKey, JSON.stringify(this.draft));
            },
            prepareSubmit() {
                this.submitting = true;
                Object.entries(this.draft).forEach(([id, value]) => {
                    const existingInput = this.$el.querySelector(`[name='ingredients[${id}]']`);
                    if (existingInput) {
                        existingInput.value = value;
                        return;
                    }

                    const quantityInput = document.createElement('input');
                    quantityInput.type = 'hidden';
                    quantityInput.name = `ingredients[${id}]`;
                    quantityInput.value = value;
                    this.$el.appendChild(quantityInput);

                    const visibleInput = document.createElement('input');
                    visibleInput.type = 'hidden';
                    visibleInput.name = 'visible_ingredients[]';
                    visibleInput.value = id;
                    this.$el.appendChild(visibleInput);
                });
            },
            clearDraft() {
                sessionStorage.removeItem(this.draftKey);
                this.draft = {};
            },
            get pendingCount() {
                return Object.keys(this.draft).length;
            },
        }"
        @submit="prepareSubmit()"
    >
        @csrf
        @method('PUT')
        <input type="hidden" name="return_to" value="edit">
        <input type="hidden" name="return_search" value="{{ request('search') }}">
        <input type="hidden" name="return_category" value="{{ request('category') }}">
        <input type="hidden" name="return_page" value="{{ $ingredients->currentPage() }}">

        @if($errors->has('ingredients') || $errors->has('ingredients.*'))
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M12 9v3m0 4h.01M10.3 3.8L2.4 17.5A2 2 0 004.1 20h15.8a2 2 0 001.7-2.5L13.7 3.8a2 2 0 00-3.4 0z" /></svg>
                <div>
                    <p class="font-bold">Komposisi belum dapat disimpan</p>
                    <p class="mt-0.5 text-xs font-medium text-rose-600/80 dark:text-rose-300/80">{{ $errors->first() }}</p>
                </div>
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900" data-recipe-edit-list>
            <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5 dark:border-slate-800 sm:px-5">
                <div class="min-w-0">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Daftar Bahan</h2>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-400">Jumlah untuk satu porsi menu.</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span x-show="pendingCount > 0" x-cloak class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                        <span x-text="pendingCount"></span>&nbsp;belum disimpan
                    </span>
                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                        {{ $recipeIngredientCount }} digunakan
                    </span>
                </div>
            </header>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($ingredients as $ingredient)
                    @php
                        $quantity = (float) ($quantities->get($ingredient->id, 0));
                        $hasError = $errors->has('ingredients.'.$ingredient->id);
                        $oldQuantity = old('ingredients.'.$ingredient->id, $quantity > 0 ? $quantity : '');
                    @endphp

                    <input type="hidden" name="visible_ingredients[]" value="{{ $ingredient->id }}">

                    <div @class([
                        'grid gap-3 px-4 py-3.5 transition sm:grid-cols-[minmax(0,1fr)_190px] sm:items-center sm:px-5',
                        'bg-blue-50/35 dark:bg-blue-500/[0.04]' => $quantity > 0 && ! $hasError,
                        'bg-rose-50/60 dark:bg-rose-500/[0.06]' => $hasError,
                    ]) data-recipe-ingredient="{{ $ingredient->id }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100" title="{{ $ingredient->name }}">{{ $ingredient->name }}</p>
                                <span class="inline-flex max-w-full truncate rounded-md bg-slate-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $ingredient->category?->name ?? 'Tanpa kategori' }}</span>
                            </div>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">Satuan {{ strtoupper((string) $ingredient->base_unit) }}</p>
                        </div>

                        <div>
                            <label class="relative block">
                                <span class="sr-only">Jumlah {{ $ingredient->name }}</span>
                                <input
                                    type="number"
                                    step="{{ ($ingredient->base_unit ?? '') === 'pcs' ? '1' : '0.01' }}"
                                    min="0"
                                    name="ingredients[{{ $ingredient->id }}]"
                                    value="{{ $oldQuantity }}"
                                    placeholder="0"
                                    @input="remember({{ $ingredient->id }}, $event.target.value)"
                                    @class([
                                        'h-10 w-full rounded-xl border bg-white pl-3 pr-14 text-right text-sm font-black tabular-nums text-slate-900 outline-none transition focus:ring-2 dark:bg-slate-950 dark:text-white',
                                        'border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 dark:border-slate-700' => ! $hasError,
                                        'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20 dark:border-rose-500/50' => $hasError,
                                    ])
                                >
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[9px] font-black uppercase tracking-wider text-slate-400">{{ $ingredient->base_unit }}</span>
                            </label>
                            @if($hasError)
                                <p class="mt-1.5 text-[11px] font-semibold text-rose-600 dark:text-rose-400">{{ $errors->first('ingredients.'.$ingredient->id) }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-14 text-center">
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Bahan tidak ditemukan</p>
                        <p class="mt-1 text-xs text-slate-400">Ubah pencarian atau kategori yang dipilih.</p>
                    </div>
                @endforelse
            </div>

            @if($ingredients->isNotEmpty())
                <footer class="grid grid-cols-2 gap-2 border-t border-slate-100 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/30 sm:flex sm:justify-end sm:px-5" data-recipe-actions>
                    <a href="{{ route('admin.recipes.index') }}" @click="clearDraft()" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-xs font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                        Batal
                    </a>
                    <button type="submit" :disabled="submitting" class="inline-flex h-10 min-w-36 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <svg x-show="!submitting" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan Semua'"></span>
                    </button>
                </footer>
            @endif
        </section>

        @if($ingredients->isNotEmpty())
            <div data-recipe-pagination>
                @include('partials.pagination_simple', [
                    'paginator' => $ingredients,
                    'label' => 'bahan',
                ])
            </div>
        @endif
    </form>
</div>
