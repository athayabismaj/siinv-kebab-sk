<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <h2 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Daftar Menu Aktif</h2>
            @if(method_exists($menus, 'total'))
            <span class="px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500">
                {{ $menus->total() }} menu
            </span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.menus.archive') }}" class="inline-flex items-center justify-center gap-1.5 px-3 h-8 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-[12px] font-semibold rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
                <svg class="h-3.5 w-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                Arsip
            </a>
            <a href="{{ route('admin.menus.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 h-8 bg-blue-600 text-white text-[12px] font-semibold rounded-lg hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Menu
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                <tr>
                    <th class="px-6 py-4">Menu & Kategori</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Variant</th>
                    <th class="px-6 py-4 text-center">Urutan</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($menus as $menu)
                    <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white text-[15px]">{{ $menu->name }}</p>
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mt-0.5">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-center">
                            @if($menu->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 uppercase tracking-widest">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:border-rose-800/50 dark:text-rose-400 uppercase tracking-widest">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Nonaktif
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-md text-xs">{{ $menu->variants_count }} Varian</span>
                        </td>

                        <td class="px-6 py-4 text-center"><span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $menu->sort_order }}</span></td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3 text-[12px] font-bold">
                                <a href="{{ route('admin.menu-variants.index', $menu->id) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-indigo-600 transition hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20 tracking-wider uppercase">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    Varian
                                </a>
                                <a href="{{ route('admin.menus.edit', $menu->id) }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors uppercase tracking-widest">Edit</a>
                                <span class="text-slate-200 dark:text-slate-700">|</span>
                                <form action="{{ route('admin.menus.destroy', $menu->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini ke Arsip?')" class="text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 transition-colors uppercase tracking-widest">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr class="md:hidden">
                        <td colspan="5" class="p-0">
                            <div class="p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <div class="flex justify-between items-start gap-3 mb-4">
                                    <div class="flex gap-3">
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-[15px] leading-tight">{{ $menu->name }}</p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                        </div>
                                    </div>
                                    @if($menu->is_active)
                                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 uppercase tracking-widest">Aktif</span>
                                    @else
                                        <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:border-rose-800/50 dark:text-rose-400 uppercase tracking-widest">Nonaktif</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-4 mb-4">
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Jumlah Varian: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $menu->variants_count }}</span></div>
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Urutan: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $menu->sort_order }}</span></div>
                                </div>

                                <div class="flex items-center justify-between gap-3 text-[11px] font-bold uppercase tracking-widest pt-4 border-t border-slate-100 dark:border-slate-800/50">
                                    <a href="{{ route('admin.menu-variants.index', $menu->id) }}" class="flex items-center gap-1.5 text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors bg-indigo-50 dark:bg-indigo-500/10 px-3 py-1.5 rounded-lg border border-indigo-100 dark:border-indigo-500/20">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                        Kelola Varian
                                    </a>

                                    <div class="flex gap-4">
                                        <a href="{{ route('admin.menus.edit', $menu->id) }}" class="flex items-center gap-1.5 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">Edit</a>
                                        <form action="{{ route('admin.menus.destroy', $menu->id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini ke Arsip?')" class="flex items-center gap-1.5 text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 transition-colors">Hapus</button>
                                        </form>
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
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Belum ada data menu aktif.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@include('admin.menus.partials.pagination', ['items' => $menus])
