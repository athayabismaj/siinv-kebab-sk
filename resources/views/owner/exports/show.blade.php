@extends('layouts.app')

@section('title', 'Status Ekspor')

@section('content')
@php
    $routePrefix = request()->routeIs('admin.*') ? 'admin' : 'owner';
    $backRoute = match ($generatedExport->type) {
        'stock_log' => 'owner.stock-logs.index',
        'usage_report' => $routePrefix . '.reports.usage',
        'daily_stock_report' => 'admin.reports.daily-stock',
        default => 'owner.transactions.index',
    };
    $statusClasses = [
        \App\Models\GeneratedExport::STATUS_PENDING => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:border-amber-500/30',
        \App\Models\GeneratedExport::STATUS_PROCESSING => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:border-blue-500/30',
        \App\Models\GeneratedExport::STATUS_COMPLETED => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:border-emerald-500/30',
        \App\Models\GeneratedExport::STATUS_FAILED => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:border-rose-500/30',
    ];
    $statusLabels = [
        \App\Models\GeneratedExport::STATUS_PENDING => 'Menunggu antrean',
        \App\Models\GeneratedExport::STATUS_PROCESSING => 'Sedang diproses',
        \App\Models\GeneratedExport::STATUS_COMPLETED => 'Siap diunduh',
        \App\Models\GeneratedExport::STATUS_FAILED => 'Gagal diproses',
    ];
@endphp

<div class="mx-auto max-w-3xl space-y-6" data-page-root="true">
    <div data-page-header class="flex items-start justify-between gap-4">
        <div>
            <nav class="uppercase tracking-[0.14em]">Beranda <span>/</span> <span>Status Ekspor</span></nav>
            <h1>Status Ekspor</h1>
            <p>File dibuat di penyimpanan privat dan hanya tersedia untuk akun yang memintanya.</p>
        </div>
        <a href="{{ route($backRoute) }}" class="inline-flex shrink-0 items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">Kembali</a>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-400">Riwayat Transaksi</p>
                <h2 class="mt-1 break-all text-lg font-bold text-slate-900 dark:text-white">{{ $generatedExport->original_filename }}</h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Diajukan {{ $generatedExport->created_at->translatedFormat('d M Y H:i') }}.</p>
            </div>
            <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses[$generatedExport->status] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">{{ $statusLabels[$generatedExport->status] ?? $generatedExport->status }}</span>
        </div>

        @if($generatedExport->status === \App\Models\GeneratedExport::STATUS_COMPLETED)
            <a href="{{ route($routePrefix . '.generated-exports.download', $generatedExport) }}" class="mt-6 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">Unduh Excel</a>
        @elseif($generatedExport->status === \App\Models\GeneratedExport::STATUS_FAILED)
            <p class="mt-6 text-sm text-rose-600 dark:text-rose-300">{{ $generatedExport->error_message }}</p>
            <form method="POST" action="{{ route($routePrefix . '.generated-exports.retry', $generatedExport) }}" class="mt-4">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Coba Lagi</button>
            </form>
        @else
            <p class="mt-6 text-sm text-slate-500 dark:text-slate-400">Halaman ini dapat dimuat ulang untuk melihat status terbaru.</p>
        @endif

        @if($generatedExport->expires_at)
            <p class="mt-6 text-xs text-slate-400">File kedaluwarsa {{ $generatedExport->expires_at->translatedFormat('d M Y H:i') }}.</p>
        @endif
    </section>
</div>
@endsection
