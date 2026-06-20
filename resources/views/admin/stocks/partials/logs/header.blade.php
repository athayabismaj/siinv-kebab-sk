@php
    $homeRoute = $homeRoute ?? route('admin.panel');
    $homeLabel = $homeLabel ?? 'Beranda';
    $sectionLabel = $sectionLabel ?? 'Inventori';
    $parentRoute = $parentRoute ?? route('admin.stocks.index');
    $parentLabel = $parentLabel ?? 'Restok & Penyesuaian';
    $currentLabel = $currentLabel ?? 'Riwayat Stok';
    $title = $title ?? 'Riwayat Perubahan Stok';
    $description = $description ?? 'Jejak riwayat penambahan stok, pemakaian dari transaksi kasir, dan penyesuaian (adjustment) manual.';
@endphp

<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
    <div class="flex-1 w-full overflow-hidden">
        <nav class="mb-3 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 sm:text-[11px] hide-scrollbar">
            <a href="{{ $homeRoute }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                {{ $homeLabel }}
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>

            <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                {{ $sectionLabel }}
            </span>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>

            <a href="{{ $parentRoute }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                {{ $parentLabel }}
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>

            <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                {{ $currentLabel }}
            </span>
        </nav>

        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white mb-2">
            {{ $title }}
        </h1>

        <p class="text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400 max-w-3xl">
            {{ $description }}
        </p>
    </div>

    <div class="shrink-0 mt-1 lg:mt-8">
        <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 dark:bg-blue-500/10 border border-blue-100/50 dark:border-blue-800/30 shadow-sm">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            <span class="text-[11px] font-bold tracking-wide text-blue-700 dark:text-blue-400 uppercase">
                PERIODE DATA:
                <span class="font-medium text-slate-700 dark:text-slate-300 ml-1">{{ $dateDisplay }}</span>
            </span>
        </div>
    </div>
</div>
