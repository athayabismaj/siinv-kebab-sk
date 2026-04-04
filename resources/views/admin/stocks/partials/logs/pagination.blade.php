@if($logs->hasPages())
<div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900">
    <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
        <div class="text-[13px] text-slate-500 dark:text-slate-400 text-center sm:text-left font-medium">
            Halaman <span class="font-bold text-slate-700 dark:text-slate-300">{{ $logs->currentPage() }}</span>
            dari <span class="font-bold text-slate-700 dark:text-slate-300">{{ $logs->lastPage() }}</span>
            <span class="mx-1.5 text-slate-300 dark:text-slate-600">|</span>
            Total <span class="font-bold text-slate-700 dark:text-slate-300">{{ $logs->total() }}</span> data
        </div>

        <div class="flex items-center gap-6 text-[13px] font-semibold">
            @if ($logs->onFirstPage())
                <span class="text-slate-400 cursor-not-allowed dark:text-slate-600">&lt; Prev</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" class="text-blue-600 hover:text-blue-700 transition dark:text-blue-400 dark:hover:text-blue-300">&lt; Prev</a>
            @endif

            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" class="text-blue-600 hover:text-blue-700 transition dark:text-blue-400 dark:hover:text-blue-300">Next &gt;</a>
            @else
                <span class="text-slate-400 cursor-not-allowed dark:text-slate-600">Next &gt;</span>
            @endif
        </div>
    </div>
</div>
@endif
