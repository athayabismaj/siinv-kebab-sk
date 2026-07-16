<div x-data="{ openMenu: null, openVariant: null }" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden divide-y divide-slate-100 dark:divide-slate-800/80">
    @forelse($menus as $menu)
        <div class="transition-colors duration-200" :class="openMenu === {{ $menu->id }} ? 'bg-slate-50/30 dark:bg-slate-800/20' : ''">
            <button @click="openMenu === {{ $menu->id }} ? openMenu = null : openMenu = {{ $menu->id }}" class="w-full px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-left hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                
                <div class="flex items-center gap-3">
                    <div class="h-8 w-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <div>
                        <span class="font-bold text-slate-800 dark:text-white text-[15px] block">{{ $menu->name }}</span>
                        <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mt-0.5 block">{{ $menu->category?->name ?? 'Tanpa Kategori' }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end mt-2 sm:mt-0">
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 text-[10px] font-bold shadow-sm">
                        {{ $menu->variants->count() }} Varian
                    </span>

                    <div class="h-6 w-6 flex items-center justify-center rounded-full bg-white border border-slate-200 shadow-sm dark:bg-slate-800 dark:border-slate-700 text-slate-500">
                        <svg :class="openMenu === {{ $menu->id }} ? 'rotate-180' : ''" class="w-3.5 h-3.5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
            </button>

            <div x-show="openMenu === {{ $menu->id }}" x-collapse x-cloak class="border-t border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-900/50 shadow-[inset_0_4px_6px_-4px_rgba(0,0,0,0.03)]">
                <div class="p-6 flex flex-wrap gap-5">
                    @forelse($menu->variants as $variant)
                        <div class="flex-1 basis-[calc(100%)] md:basis-[calc(50%-1rem)] xl:basis-[calc(33.333%-1rem)] min-w-[280px] flex flex-col bg-white dark:bg-slate-900 p-0 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-200 relative overflow-hidden group">
                            <div class="p-5 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-bold text-slate-800 dark:text-white text-[15px]">{{ $variant->name }}</h3>
                                    <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-1.5 flex items-center gap-1.5">
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                        Terdiri dari {{ $variant->ingredients->count() }} Bahan
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                                    <button @click="openVariant === {{ $variant->id }} ? openVariant = null : openVariant = {{ $variant->id }}" class="flex-1 sm:flex-none flex justify-center items-center gap-1.5 px-3 py-2 text-[11px] font-bold rounded-xl bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200/60 transition-all dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600/50 dark:hover:bg-slate-600 tracking-wider uppercase shadow-sm">
                                        <span x-text="openVariant === {{ $variant->id }} ? 'Tutup' : 'Lihat Resep'"></span>
                                    </button>

                                    <a href="{{ route('admin.recipes.edit', $variant->id) }}" class="flex-1 sm:flex-none flex justify-center items-center gap-1.5 px-3 py-2 text-[11px] font-bold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200/60 transition-all dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/20 dark:hover:bg-indigo-500/20 tracking-wider uppercase shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        Edit
                                    </a>
                                </div>
                            </div>

                            <div x-show="openVariant === {{ $variant->id }}" x-collapse x-cloak>
                                <div class="p-5 pt-0 mt-2">
                                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-2.5">
                                        @forelse($variant->ingredients as $ingredient)
                                            <div class="flex flex-col justify-center bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl p-3">
                                                <p class="font-bold text-[13px] text-slate-700 dark:text-slate-200 leading-tight line-clamp-1" title="{{ $ingredient->name }}">{{ $ingredient->name }}</p>
                                                <p class="text-[12px] font-black text-blue-600 dark:text-blue-400 mt-1 tabular-nums">
                                                    {{ (float) $ingredient->pivot->quantity }} <span class="uppercase tracking-widest text-[9px] font-bold text-slate-400 dark:text-slate-500 ml-0.5">{{ $ingredient->base_unit }}</span>
                                                </p>
                                            </div>
                                        @empty
                                            <div class="col-span-full flex items-center gap-2 text-xs font-semibold text-rose-500 dark:text-rose-400 bg-rose-50 dark:bg-rose-500/10 p-4 rounded-xl border border-rose-100 dark:border-rose-500/20">
                                                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                                Varian ini belum memiliki resep. Sistem tidak akan memotong stok bahan jika varian ini terjual.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="w-full col-span-full text-[13px] text-slate-500 dark:text-slate-400 italic text-center py-8 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700">
                            Belum ada varian yang ditambahkan untuk menu ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @empty
        <div class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-16 text-center shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-4 border border-slate-100 dark:border-slate-700">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-[13px] font-medium">Tidak ada data menu/resep yang ditemukan.</p>
        </div>
    @endforelse
</div>
