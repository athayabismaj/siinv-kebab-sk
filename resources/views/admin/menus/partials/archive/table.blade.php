<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-5 py-4 dark:border-slate-800 dark:bg-slate-800/30 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Daftar Menu Nonaktif</h2>
                @if(method_exists($menus, 'total'))
                    <span class="inline-flex h-6 items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 text-[10px] font-black uppercase tracking-wider text-blue-700 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300">
                        {{ $menus->total() }} data
                    </span>
                @endif
            </div>
            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                Pulihkan menu jika ingin ditampilkan kembali di daftar menu kasir.
            </p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="hidden border-b border-slate-100 bg-white text-[10px] font-black uppercase tracking-widest text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-500 md:table-header-group">
                <tr>
                    <th class="px-6 py-4">Menu</th>
                    <th class="px-6 py-4 text-center">Varian</th>
                    <th class="px-6 py-4">Dinonaktifkan</th>
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
                                    <p class="truncate font-black text-slate-900 dark:text-white">{{ $menu->name }}</p>
                                    <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex h-7 items-center rounded-full border border-slate-200 bg-slate-50 px-3 text-xs font-black text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ number_format($menu->variants_count) }} varian
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-700 dark:text-slate-200">{{ optional($menu->deleted_at)->format('d F Y') }}</p>
                            <p class="mt-0.5 text-xs font-medium text-slate-400">Pukul {{ optional($menu->deleted_at)->format('H:i') }} WIB</p>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form action="{{ route('admin.menus.restore', $menu->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        onclick="return confirm('Apakah Anda yakin ingin mengaktifkan kembali menu ini?')"
                                        class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 text-xs font-black text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-4 focus:ring-blue-500/10 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/15">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 4v5h5M20 20v-5h-5M5.64 15A7 7 0 0018 17.66M18.36 9A7 7 0 006 6.34" />
                                    </svg>
                                    Pulihkan
                                </button>
                            </form>
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
                                        <p class="break-words font-black text-slate-900 dark:text-white">{{ $menu->name }}</p>
                                        <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $menu->category->name ?? 'Tanpa Kategori' }}</p>
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex h-7 items-center rounded-full border border-slate-200 bg-slate-50 px-3 text-[11px] font-black text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                                {{ number_format($menu->variants_count) }} varian
                                            </span>
                                            <span class="text-xs font-medium text-slate-400">
                                                {{ optional($menu->deleted_at)->format('d M Y, H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('admin.menus.restore', $menu->id) }}" method="POST" class="shrink-0">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            title="Pulihkan menu"
                                            onclick="return confirm('Aktifkan kembali menu ini?')"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 4v5h5M20 20v-5h-5M5.64 15A7 7 0 0018 17.66M18.36 9A7 7 0 006 6.34" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h10" />
                                </svg>
                            </div>
                            <p class="mt-4 text-sm font-black text-slate-900 dark:text-white">Arsip menu masih kosong.</p>
                            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Menu yang dinonaktifkan akan muncul di halaman ini.</p>
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
