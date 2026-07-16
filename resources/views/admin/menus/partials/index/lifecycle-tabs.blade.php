@php
    $sharedFilters = request()->only(['search', 'category']);
    $tabs = [
        'active' => ['label' => 'Aktif', 'count' => $activeCount],
        'archived' => ['label' => 'Diarsipkan', 'count' => $archivedCount],
        'all' => ['label' => 'Semua', 'count' => $allCount],
    ];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <nav aria-label="Status data menu" class="grid grid-cols-3 gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-900 sm:inline-grid sm:min-w-[390px]">
        @foreach($tabs as $status => $tab)
            <a href="{{ route('admin.menus.index', array_merge($sharedFilters, ['record_status' => $status])) }}"
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

    <a href="{{ route('admin.menus.create') }}" class="inline-flex items-center justify-center gap-1.5 px-5 h-10 bg-slate-900 dark:bg-blue-600 text-white text-[12px] font-bold rounded-xl hover:bg-slate-800 dark:hover:bg-blue-500 transition-all shadow-sm w-full sm:w-auto shrink-0">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
        Tambah Menu
    </a>
</div>
