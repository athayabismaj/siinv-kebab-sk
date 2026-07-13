@php
    $homeRoute = $homeRoute ?? route('admin.panel');
    $homeLabel = $homeLabel ?? 'Beranda';
    $sectionLabel = $sectionLabel ?? 'Inventori';
    $parentRoute = $parentRoute ?? route('admin.stocks.index');
    $parentLabel = $parentLabel ?? 'Restok & Penyesuaian';
    $currentLabel = $currentLabel ?? 'Riwayat Stok';
    $title = $title ?? 'Riwayat Perubahan Stok';
    $description = $description ?? 'Jejak perubahan stok dari restok, pemakaian bahan, pengembalian stok, dan penyesuaian manual.';
@endphp

<x-page-header 
    title="{{ $title }}" 
    subtitle="{{ $description }}" 
    breadcrumb-parent="{{ $parentLabel }}" 
    breadcrumb-child="{{ $currentLabel }}">
    
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
</x-page-header>
