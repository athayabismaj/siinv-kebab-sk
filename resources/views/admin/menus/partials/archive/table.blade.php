<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Daftar Menu Nonaktif</h2>
        </div>
        @if(method_exists($menus, 'total'))
        <div class="text-xs font-medium text-slate-500 bg-slate-50 dark:bg-slate-800/50 px-3 py-1.5 rounded-full border border-slate-100 dark:border-slate-700">
            Menampilkan <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $menus->firstItem() ?? 0 }} - {{ $menus->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $menus->total() }}</span> data
        </div>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                <tr>
                    <th class="px-6 py-4">Menu & Kategori</th>
                    <th class="px-6 py-4 text-center">Variant</th>
                    <th class="px-6 py-4">Dinonaktifkan Pada</th>
                    <th class="px-6 py-4 text-right">Aksi Pemulihan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($menus as $menu)
                    <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                        <td class="px-6 py-5 w-2/5 align-middle">
                            <div class="flex items-center gap-4">
                                @if($menu->image_path)
                                    <img src="{{ asset('storage/'.$menu->image_path) }}" alt="{{ $menu->name }}" class="h-12 w-12 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-sm shrink-0 grayscale opacity-70 group-hover:grayscale-0 group-hover:opacity-100 transition-all duration-300">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm shrink-0 opacity-70">
                                        <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white text-[15px]">{{ $menu->name }}</p>
                                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mt-0.5">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-5 text-center align-middle">
                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-md text-xs">{{ $menu->variants_count }} Varian</span>
                        </td>

                        <td class="px-6 py-5 align-middle">
                            <div class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ optional($menu->deleted_at)->format('d F Y') }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">Pukul {{ optional($menu->deleted_at)->format('H:i') }} WIB</div>
                        </td>

                        <td class="px-6 py-5 text-right align-middle">
                            <form action="{{ route('admin.menus.restore', $menu->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('PATCH')
                                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin mengaktifkan kembali menu ini?')" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-4 py-2 text-[12px] font-bold text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    Pulihkan Menu
                                </button>
                            </form>
                        </td>
                    </tr>

                    <tr class="md:hidden">
                        <td colspan="4" class="p-0">
                            <div class="p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <div class="flex justify-between items-start gap-3 mb-4">
                                    <div class="flex gap-3">
                                        @if($menu->image_path)
                                            <img src="{{ asset('storage/'.$menu->image_path) }}" alt="{{ $menu->name }}" class="h-12 w-12 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-sm shrink-0 grayscale opacity-80">
                                        @else
                                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm shrink-0 opacity-70">
                                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-[15px] leading-tight">{{ $menu->name }}</p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold rounded text-[10px] uppercase tracking-wider border border-slate-200 dark:border-slate-700">Nonaktif</span>
                                </div>

                                <div class="flex items-center gap-4 mb-4">
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Varian: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $menu->variants_count }}</span></div>
                                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">Dihapus: <span class="font-bold text-slate-700 dark:text-slate-300">{{ optional($menu->deleted_at)->format('d/m/Y') }}</span></div>
                                </div>

                                <div class="pt-4 border-t border-slate-100 dark:border-slate-800/50">
                                    <form action="{{ route('admin.menus.restore', $menu->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" onclick="return confirm('Aktifkan kembali menu ini?')" class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-50 px-4 py-2.5 text-[13px] font-bold text-blue-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">Pulihkan Menu</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Tidak ada data menu di dalam arsip saat ini.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.menus.partials.pagination', ['items' => $menus])
</div>
