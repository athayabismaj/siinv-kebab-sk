<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Daftar Bahan Baku</h2>
        </div>
        @if(method_exists($ingredients, 'total'))
            <div class="text-xs font-medium text-slate-500 bg-slate-50 dark:bg-slate-800/50 px-3 py-1.5 rounded-full border border-slate-100 dark:border-slate-700">
                Menampilkan <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $ingredients->firstItem() ?? 0 }} - {{ $ingredients->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $ingredients->total() }}</span> data
            </div>
        @endif
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                <tr>
                    <th class="px-6 py-4">Bahan & Kategori</th>
                    <th class="px-6 py-4">Status & Progres Stok</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                @forelse($ingredients as $ingredient)
                    @include('admin.ingredients.partials.index.row-desktop', ['ingredient' => $ingredient])
                    @include('admin.ingredients.partials.index.row-mobile', ['ingredient' => $ingredient])
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Belum ada data bahan baku.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
