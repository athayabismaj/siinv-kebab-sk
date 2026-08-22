<div
    x-data="{
        detailOpen: false,
        detailLoading: false,
        detailError: '',
        detail: null,
        async openDetail(url) {
            this.detailOpen = true;
            this.detailLoading = true;
            this.detailError = '';
            this.detail = null;

            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!(response.headers.get('content-type') || '').includes('application/json')) {
                    throw new Error('Sesi login telah berakhir. Muat ulang halaman untuk masuk kembali.');
                }
                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Detail resep gagal dimuat.');
                }
                this.detail = payload.data;
            } catch (error) {
                this.detailError = error.message || 'Detail resep gagal dimuat.';
            } finally {
                this.detailLoading = false;
            }
        },
        closeDetail() {
            this.detailOpen = false;
        },
    }"
    @keydown.escape.window="closeDetail()"
    class="space-y-3"
>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="hidden grid-cols-[minmax(240px,1.6fr)_minmax(140px,.8fr)_150px_120px_160px] items-center gap-4 border-b border-slate-200 bg-slate-50/80 px-5 py-3 text-[10px] font-black uppercase tracking-[0.12em] text-slate-400 dark:border-slate-800 dark:bg-slate-800/40 lg:grid">
            <span>Menu & Varian</span>
            <span>Kategori</span>
            <span>Komposisi</span>
            <span>Status Menu</span>
            <span class="text-right">Aksi</span>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800" data-recipe-variant-list>
            @forelse($variants as $variant)
                @php
                    $hasRecipe = (int) $variant->ingredients_count > 0;
                @endphp
                <article class="grid gap-4 p-4 transition hover:bg-slate-50/70 dark:hover:bg-slate-800/25 lg:grid-cols-[minmax(240px,1.6fr)_minmax(140px,.8fr)_150px_120px_160px] lg:items-center lg:px-5 lg:py-4" data-recipe-variant="{{ $variant->id }}">
                    <div class="min-w-0">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            </span>
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-bold text-slate-800 dark:text-white" title="{{ $variant->menu?->name }}">{{ $variant->menu?->name ?? 'Menu tidak tersedia' }}</h2>
                                <p class="mt-0.5 truncate text-xs font-medium text-slate-500 dark:text-slate-400" title="{{ $variant->name }}">{{ $variant->name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 lg:block">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 lg:hidden">Kategori</span>
                        <span class="inline-flex max-w-full truncate rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $variant->menu?->category?->name ?? 'Tanpa Kategori' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3 lg:block">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 lg:hidden">Komposisi</span>
                        @if($hasRecipe)
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                {{ $variant->ingredients_count }} bahan
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600 dark:text-amber-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Belum diatur
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-3 lg:block">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 lg:hidden">Status Menu</span>
                        <span @class([
                            'inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider',
                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' => $variant->is_available,
                            'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => ! $variant->is_available,
                        ])>
                            {{ $variant->is_available ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 lg:flex lg:justify-end">
                        <button
                            type="button"
                            @click="openDetail(@js(route('admin.recipes.details', $variant)))"
                            class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 px-3 text-[11px] font-bold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-blue-500/30 dark:hover:bg-blue-500/10 dark:hover:text-blue-400"
                        >
                            Detail
                        </button>
                        <a href="{{ route('admin.recipes.edit', $variant) }}" class="inline-flex h-9 items-center justify-center rounded-xl bg-blue-600 px-3 text-[11px] font-bold text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700">
                            Edit Resep
                        </a>
                    </div>
                </article>
            @empty
                <div class="flex flex-col items-center justify-center px-5 py-16 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-6 0a2 2 0 002 2h2a2 2 0 002-2m-6 0a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </span>
                    <p class="mt-4 text-sm font-bold text-slate-700 dark:text-slate-200">Tidak ada varian ditemukan</p>
                    <p class="mt-1 text-xs text-slate-400">Ubah kata pencarian atau kategori yang dipilih.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div x-show="detailOpen" x-cloak class="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/45 p-0 backdrop-blur-[2px] sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-label="Detail resep">
        <button type="button" class="absolute inset-0 cursor-default" @click="closeDetail()" aria-label="Tutup detail"></button>

        <section
            x-show="detailOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full opacity-0 sm:translate-y-4"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0 sm:translate-y-4"
            class="relative flex max-h-[88vh] w-full flex-col overflow-hidden rounded-t-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:max-w-lg sm:rounded-3xl"
        >
            <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-[0.14em] text-blue-600 dark:text-blue-400">Detail Komposisi</p>
                    <template x-if="detail">
                        <div class="mt-1">
                            <h3 class="truncate text-base font-bold text-slate-900 dark:text-white" x-text="detail.menu_name"></h3>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400" x-text="detail.variant_name"></p>
                        </div>
                    </template>
                    <template x-if="!detail">
                        <h3 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Memuat resep</h3>
                    </template>
                </div>
                <button type="button" @click="closeDetail()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700" aria-label="Tutup">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </header>

            <div class="overflow-y-auto px-5 py-4">
                <div x-show="detailLoading" class="space-y-3" aria-label="Memuat detail resep">
                    <template x-for="index in 4" :key="index">
                        <div class="flex animate-pulse items-center justify-between rounded-xl bg-slate-100 px-4 py-3 dark:bg-slate-800">
                            <span class="h-3 w-32 rounded bg-slate-200 dark:bg-slate-700"></span>
                            <span class="h-3 w-14 rounded bg-slate-200 dark:bg-slate-700"></span>
                        </div>
                    </template>
                </div>

                <div x-show="!detailLoading && detailError" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" x-text="detailError"></div>

                <template x-if="!detailLoading && detail && detail.ingredients.length > 0">
                    <div class="space-y-2">
                        <template x-for="ingredient in detail.ingredients" :key="ingredient.id">
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                                <span class="min-w-0 truncate text-sm font-semibold text-slate-700 dark:text-slate-200" x-text="ingredient.name"></span>
                                <span class="shrink-0 tabular-nums text-sm font-black text-blue-600 dark:text-blue-400">
                                    <span x-text="Number(ingredient.quantity).toLocaleString('id-ID', { maximumFractionDigits: 3 })"></span>
                                    <span class="ml-0.5 text-[9px] uppercase tracking-wider text-slate-400" x-text="ingredient.unit"></span>
                                </span>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!detailLoading && detail && detail.ingredients.length === 0">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-center dark:border-amber-500/20 dark:bg-amber-500/10">
                        <p class="text-sm font-bold text-amber-800 dark:text-amber-300">Resep belum diatur</p>
                        <p class="mt-1 text-xs text-amber-700/70 dark:text-amber-300/70">Tambahkan minimal satu bahan agar pemotongan stok dapat berjalan.</p>
                    </div>
                </template>
            </div>

            <footer x-show="detail" class="border-t border-slate-100 px-5 py-4 dark:border-slate-800">
                <a :href="detail ? detail.edit_url : '#'" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-blue-600 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700">
                    Edit Komposisi Resep
                </a>
            </footer>
        </section>
    </div>
</div>
