@php
    $sharedFilters = request()->only(['search', 'category', 'has_price']);
    $tabs = [
        'active' => ['label' => 'Aktif', 'count' => $activeCount],
        'archived' => ['label' => 'Diarsipkan', 'count' => $archivedCount],
        'all' => ['label' => 'Semua', 'count' => $allCount],
    ];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <nav aria-label="Status data bahan" class="grid grid-cols-3 gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-900 sm:inline-grid sm:min-w-[390px]">
        @foreach($tabs as $status => $tab)
            <a href="{{ route('admin.ingredients.index', array_merge($sharedFilters, ['record_status' => $status])) }}"
               @class([
                   'flex min-w-0 items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-bold transition',
                   'bg-white text-blue-600 shadow-sm dark:bg-slate-800 dark:text-blue-400' => $recordStatus === $status,
                   'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white' => $recordStatus !== $status,
               ])>
                <span>{{ $tab['label'] }}</span>
                <span @class([
                    'rounded-md px-1.5 py-0.5 text-[10px] tabular-nums',
                    'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300' => $recordStatus === $status,
                    'bg-slate-200/70 text-slate-500 dark:bg-slate-700 dark:text-slate-300' => $recordStatus !== $status,
                ])>{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </nav>

    <a href="{{ route('admin.ingredients.create') }}"
       class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-[13px] font-black text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/15 sm:w-auto shrink-0">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14m7-7H5" />
        </svg>
        Tambah Bahan
    </a>
</div>
