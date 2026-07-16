<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-3">
                <h2 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide">Daftar Menu Nonaktif</h2>
                @if(method_exists($menus, 'total'))
                    <span class="px-2 py-0.5 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[10px] font-bold text-slate-400 dark:text-slate-500 shadow-sm">
                        {{ $menus->total() }} menu
                    </span>
                @endif
            </div>
            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Pulihkan menu jika ingin ditampilkan kembali.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="hidden border-b border-slate-100 bg-white text-[11px] font-black uppercase tracking-widest text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500 md:table-header-group">
                <tr>
                    <th class="px-6 py-4">Menu</th>
                    <th class="px-6 py-4 text-center">Varian</th>
                    <th class="px-6 py-4">Dinonaktifkan Pada</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                @forelse($menus as $menu)
                    <tr class="hidden transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40 md:table-row">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-900/60">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h10" />
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-black text-[14px] text-slate-900 dark:text-white">{{ $menu->name }}</p>
                                    <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex h-7 items-center rounded-full bg-slate-50 px-3 text-[11px] font-bold tracking-widest uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ number_format($menu->variants_count) }} varian
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-[13px] font-bold text-slate-700 dark:text-slate-200">{{ optional($menu->deleted_at)->format('d F Y') }}</p>
                            <p class="mt-0.5 text-[11px] font-medium text-slate-400">Pukul {{ optional($menu->deleted_at)->format('H:i') }} WIB</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button type="button"
                                    title="Pulihkan Menu"
                                    @click="openMenuRestore('{{ route('admin.menus.restore', $menu->id) }}', '{{ addslashes($menu->name) }}')"
                                    class="inline-flex items-center justify-center rounded-xl bg-blue-50 p-2 text-blue-700 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 shadow-sm">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h5M20 20v-5h-5M5.64 15A7 7 0 0018 17.66M18.36 9A7 7 0 006 6.34" />
                                </svg>
                            </button>
                        </td>
                    </tr>

                    <tr class="md:hidden">
                        <td colspan="4" class="p-0">
                            <div class="flex items-start justify-between gap-3 p-4 transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-900/60">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h10" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="break-words font-black text-[14px] text-slate-900 dark:text-white">{{ $menu->name }}</p>
                                        <p class="mt-0.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex h-6 items-center rounded-full bg-slate-50 px-2.5 text-[10px] font-bold tracking-widest uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                {{ number_format($menu->variants_count) }} varian
                                            </span>
                                            <span class="text-[11px] font-medium text-slate-400">
                                                {{ optional($menu->deleted_at)->format('d M Y, H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        title="Pulihkan Menu"
                                        @click="openMenuRestore('{{ route('admin.menus.restore', $menu->id) }}', '{{ addslashes($menu->name) }}')"
                                        class="inline-flex items-center justify-center rounded-xl bg-blue-50 p-2.5 text-blue-700 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 shadow-sm shrink-0">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h5M20 20v-5h-5M5.64 15A7 7 0 0018 17.66M18.36 9A7 7 0 006 6.34" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-20 text-center">
                            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 ring-4 ring-white shadow-sm dark:bg-slate-800 dark:ring-slate-900 text-slate-400 dark:text-slate-500 mb-4">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            </div>
                            <h3 class="text-[14px] font-bold text-slate-900 dark:text-white">Arsip menu masih kosong</h3>
                            <p class="mt-1 text-[13px] font-medium text-slate-500 dark:text-slate-400">Belum ada menu yang dinonaktifkan saat ini.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</section>

@include('partials.pagination_simple', [
    'paginator' => $menus,
    'label' => 'data',
])
