<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex flex-col gap-2 border-b border-slate-100 bg-slate-50/30 px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-[13px] font-bold uppercase tracking-wide text-slate-800 dark:text-slate-200">{{ match($recordStatus) { 'archived' => 'Menu Diarsipkan', 'all' => 'Semua Menu', default => 'Menu Aktif' } }}</h2>
                <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-bold text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500">{{ $menus->total() }} menu</span>
            </div>
            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Status tersedia mengatur penjualan; status diarsipkan mengatur lifecycle data.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="hidden border-b border-slate-100 bg-white text-[11px] font-bold uppercase tracking-widest text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500 md:table-header-group">
                <tr>
                    <th class="px-6 py-4">Menu & Kategori</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Varian</th>
                    <th class="px-6 py-4 text-center">Urutan / Diarsipkan</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                @forelse($menus as $menu)
                    <tr class="hidden transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40 md:table-row">
                        <td class="px-6 py-4">
                            <p class="text-[15px] font-bold text-slate-800 dark:text-white">{{ $menu->name }}</p>
                            <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($menu->trashed())
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-200/70 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-slate-600 dark:bg-slate-800 dark:text-slate-300"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Diarsipkan</span>
                            @elseif($menu->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Tersedia</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Tidak Tersedia</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="rounded-lg bg-slate-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $menu->variants_count }} Varian</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($menu->trashed())
                                <p class="text-[12px] font-semibold text-slate-600 dark:text-slate-300">{{ $menu->deleted_at?->translatedFormat('d M Y') }}</p>
                                <p class="mt-0.5 text-[10px] text-slate-400">{{ $menu->deleted_at?->format('H:i') }} WIB</p>
                            @else
                                <span class="text-[15px] font-black tabular-nums text-slate-700 dark:text-slate-300">{{ $menu->sort_order }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($menu->trashed())
                                <button type="button" @click="openMenuRestore('{{ route('admin.menus.restore', $menu->id) }}', '{{ addslashes($menu->name) }}')" class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    Pulihkan
                                </button>
                            @else
                                <div class="flex items-center justify-end gap-2.5">
                                    <a href="{{ route('admin.menu-variants.index', $menu->id) }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-50 p-2 text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400" title="Kelola Varian"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg></a>
                                    <a href="{{ route('admin.menus.edit', $menu->id) }}" class="inline-flex items-center justify-center rounded-xl bg-blue-50 p-2 text-blue-700 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400" title="Edit Menu"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></a>
                                    <button type="button" @click="openMenuDestroy('{{ route('admin.menus.destroy', $menu->id) }}', '{{ addslashes($menu->name) }}')" class="inline-flex items-center justify-center rounded-xl bg-rose-50 p-2 text-rose-700 transition hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400" title="Arsipkan Menu"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                </div>
                            @endif
                        </td>
                    </tr>

                    <tr class="md:hidden">
                        <td colspan="5" class="p-0">
                            <article class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-[15px] font-bold text-slate-800 dark:text-white">{{ $menu->name }}</p>
                                        <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                    </div>
                                    @if($menu->trashed())
                                        <span class="shrink-0 rounded-lg bg-slate-200/70 px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-slate-600 dark:bg-slate-800 dark:text-slate-300">Diarsipkan</span>
                                    @elseif($menu->is_active)
                                        <span class="shrink-0 rounded-lg bg-emerald-50 px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">Tersedia</span>
                                    @else
                                        <span class="shrink-0 rounded-lg bg-amber-50 px-2 py-1 text-[9px] font-bold uppercase tracking-widest text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">Tidak Tersedia</span>
                                    @endif
                                </div>

                                <div class="mt-4 flex items-center gap-4 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                    <span><strong class="text-slate-700 dark:text-slate-200">{{ $menu->variants_count }}</strong> varian</span>
                                    @if($menu->trashed())
                                        <span>{{ $menu->deleted_at?->translatedFormat('d M Y, H:i') }}</span>
                                    @else
                                        <span>Urutan <strong class="text-slate-700 dark:text-slate-200">{{ $menu->sort_order }}</strong></span>
                                    @endif
                                </div>

                                <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                                    @if($menu->trashed())
                                        <button type="button" @click="openMenuRestore('{{ route('admin.menus.restore', $menu->id) }}', '{{ addslashes($menu->name) }}')" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-[11px] font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400">Pulihkan Menu</button>
                                    @else
                                        <a href="{{ route('admin.menu-variants.index', $menu->id) }}" class="rounded-xl bg-indigo-50 p-2 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400" title="Kelola Varian">Varian</a>
                                        <a href="{{ route('admin.menus.edit', $menu->id) }}" class="rounded-xl bg-blue-50 p-2 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">Edit</a>
                                        <button type="button" @click="openMenuDestroy('{{ route('admin.menus.destroy', $menu->id) }}', '{{ addslashes($menu->name) }}')" class="rounded-xl bg-rose-50 p-2 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">Arsipkan</button>
                                    @endif
                                </div>
                            </article>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full border border-slate-100 bg-slate-50 dark:border-slate-700 dark:bg-slate-800"><svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h10" /></svg></div>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                @if($hasNonLifecycleFilters)
                                    Tidak ada menu yang cocok dengan filter.
                                @elseif($recordStatus === 'archived')
                                    Belum ada menu yang diarsipkan.
                                @else
                                    Belum ada data menu.
                                @endif
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@include('admin.menus.partials.pagination', ['items' => $menus])
