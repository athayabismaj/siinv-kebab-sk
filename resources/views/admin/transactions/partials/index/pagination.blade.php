<div class="mt-2 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div class="text-sm text-slate-500 dark:text-slate-400 text-center md:text-left">
        Halaman
        <span class="font-semibold text-slate-800 dark:text-white">{{ $transactions->currentPage() }}</span>
        dari
        <span class="font-semibold text-slate-800 dark:text-white">{{ $transactions->lastPage() }}</span>
        | Total
        <span class="font-semibold text-slate-800 dark:text-white">{{ $transactions->total() }}</span>
        transaksi
    </div>

    <div class="grid grid-cols-2 gap-2 w-full md:flex md:w-auto md:justify-end">
        @if ($transactions->onFirstPage())
            <span class="px-4 py-2 text-sm rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed text-center">&lt; Previous</span>
        @else
            <a href="{{ $transactions->previousPageUrl() }}" class="px-4 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition text-center">&lt; Previous</a>
        @endif

        @if ($transactions->hasMorePages())
            <a href="{{ $transactions->nextPageUrl() }}" class="px-4 py-2 text-sm rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition text-center">Next &gt;</a>
        @else
            <span class="px-4 py-2 text-sm rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed text-center">Next &gt;</span>
        @endif
    </div>
</div>
