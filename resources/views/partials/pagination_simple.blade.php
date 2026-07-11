@php
    $paginator = $paginator ?? $items ?? null;
    $label = $label ?? 'data';
    $showTotal = $showTotal ?? true;
@endphp

@if($paginator && method_exists($paginator, 'currentPage') && method_exists($paginator, 'lastPage'))
    <nav
        role="navigation"
        aria-label="Navigasi pagination"
        class="transaction-monitor-pagination mt-3 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/75 px-4 py-3 shadow-sm shadow-slate-200/50 dark:border-slate-800 dark:bg-slate-900/70 dark:shadow-none sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-center text-xs text-slate-400 dark:text-slate-500 sm:text-left">
            Halaman <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->currentPage() }}</span>
            dari <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->lastPage() }}</span>
            @if($showTotal && method_exists($paginator, 'total'))
                | Total <span class="font-bold text-slate-700 dark:text-slate-200">{{ $paginator->total() }}</span> {{ $label }}
            @endif
        </p>

        <div class="flex items-center justify-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="cursor-not-allowed rounded-lg px-3 py-1.5 text-xs font-bold text-slate-300 dark:text-slate-700">&lt; Prev</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded-lg px-3 py-1.5 text-xs font-bold text-slate-500 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">&lt; Prev</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700">Next &gt;</a>
            @else
                <span class="cursor-not-allowed rounded-lg px-3 py-1.5 text-xs font-bold text-slate-300 dark:text-slate-700">Next &gt;</span>
            @endif
        </div>
    </nav>
@endif
