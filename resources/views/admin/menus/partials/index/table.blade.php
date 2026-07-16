<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h2 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Daftar Menu Aktif</h2>
            @if(method_exists($menus, 'total'))
            <span class="px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500 shadow-sm">
                {{ $menus->total() }} menu
            </span>
            @endif
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800">
                <tr>
                    <th class="px-6 py-4 font-black">Menu & Kategori</th>
                    <th class="px-6 py-4 font-black text-center">Status</th>
                    <th class="px-6 py-4 font-black text-center">Variant</th>
                    <th class="px-6 py-4 font-black text-center">Urutan</th>
                    <th class="px-6 py-4 font-black text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($menus as $menu)
                    <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div>
                                    <p class="font-bold text-slate-800 dark:text-white text-[15px]">{{ $menu->name }}</p>
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest mt-0.5">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($menu->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 uppercase tracking-widest">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 uppercase tracking-widest">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Nonaktif
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-lg text-[11px] uppercase tracking-wider">{{ $menu->variants_count }} Varian</span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="text-[15px] tabular-nums font-black text-slate-700 dark:text-slate-300">{{ $menu->sort_order }}</span>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2.5">
                                <a href="{{ route('admin.menu-variants.index', $menu->id) }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-50 p-2 text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20 shadow-sm" title="Kelola Varian">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                </a>
                                <a href="{{ route('admin.menus.edit', $menu->id) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-50 p-2 text-blue-700 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 shadow-sm" title="Edit Menu">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </a>
                                <button type="button"
                                        @click="openMenuDestroy('{{ route('admin.menus.destroy', $menu->id) }}', '{{ addslashes($menu->name) }}')"
                                        class="inline-flex items-center justify-center rounded-xl bg-rose-50 p-2 text-rose-700 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20 shadow-sm" title="Nonaktifkan Menu">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr class="md:hidden">
                        <td colspan="5" class="p-0">
                            <div class="p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <div class="flex justify-between items-start gap-3 mb-4">
                                    <div class="flex gap-3">
                                        <div>
                                            <p class="font-bold text-slate-800 dark:text-white text-[15px] leading-tight">{{ $menu->name }}</p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                        </div>
                                    </div>
                                    @if($menu->is_active)
                                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 uppercase tracking-widest">Aktif</span>
                                    @else
                                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 uppercase tracking-widest">Nonaktif</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-4 mb-4">
                                    <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Varian: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $menu->variants_count }}</span></div>
                                    <div class="text-[11px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Urutan: <span class="font-bold tabular-nums text-slate-700 dark:text-slate-300">{{ $menu->sort_order }}</span></div>
                                </div>

                                <div class="flex items-center justify-between gap-3 pt-4 border-t border-slate-100 dark:border-slate-800/50">
                                    <a href="{{ route('admin.menu-variants.index', $menu->id) }}" class="inline-flex items-center justify-center text-indigo-700 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors bg-indigo-50 dark:bg-indigo-500/10 p-2 rounded-xl shadow-sm" title="Kelola Varian">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    </a>

                                    <div class="flex gap-2.5">
                                        <a href="{{ route('admin.menus.edit', $menu->id) }}" class="inline-flex items-center justify-center text-blue-700 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors bg-blue-50 dark:bg-blue-500/10 p-2 rounded-xl shadow-sm" title="Edit Menu">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                        </a>
                                        <button type="button"
                                            @click="openMenuDestroy('{{ route('admin.menus.destroy', $menu->id) }}', '{{ addslashes($menu->name) }}')"
                                            class="inline-flex items-center justify-center text-rose-700 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 transition-colors bg-rose-50 dark:bg-rose-500/10 p-2 rounded-xl shadow-sm" title="Nonaktifkan Menu">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 text-[13px] font-medium">Belum ada data menu aktif.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('admin.menus.partials.pagination', ['items' => $menus])
