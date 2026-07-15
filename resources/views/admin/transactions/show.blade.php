@extends('layouts.app')

@section('title', 'Detail Transaksi')

@push('styles')
@vite('resources/css/pages/admin-transaction-detail.css')
@endpush

@section('content')
@php
    $routePrefix = 'admin.transactions';
    $statusRaw = strtolower(trim((string) ($transaction->status ?? 'success')));
    $isVoid = $statusRaw === 'void';
    $isSuccess = $statusRaw === 'success';
    $statusLabel = $isVoid ? 'Dibatalkan' : ($isSuccess ? 'Berhasil' : ucwords(str_replace('_', ' ', strtolower((string) $transaction->status))));
    $paymentMethodLabel = in_array(strtolower(trim((string) ($transaction->paymentMethod->name ?? ''))), ['cash', 'tunai'], true)
        ? 'Tunai'
        : (($transaction->paymentMethod->name ?? null) ?: '-');
    $voidReasonLabel = match (strtolower(trim((string) $transaction->void_reason))) {
        'restock', 'kembali_stok', 'kembali stok' => 'Kembali ke Stok',
        'waste' => 'Bahan Terbuang',
        'input_error' => 'Kesalahan Input',
        'customer_cancel' => 'Pembatalan Pesanan',
        'other', 'lainnya' => 'Lainnya',
        default => null,
    };
    $totalQty = (int) $transaction->details->sum('quantity');
@endphp

<div class="transaction-detail-shell space-y-5 pb-10">
    <x-page-header 
        title="Detail Transaksi" 
        breadcrumb-parent="Riwayat Transaksi" 
        breadcrumb-child="Detail Transaksi">
        
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                <span class="h-1.5 w-1.5 rounded-full {{ $isVoid ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                {{ $statusLabel }}
            </span>
            <span class="font-mono text-sm font-bold text-slate-500 dark:text-slate-400 break-all mr-2">{{ $transaction->transaction_code }}</span>
            <button type="button" data-print-page
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4H9v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Cetak
            </button>
            <a href="{{ route($routePrefix.'.index') }}"
               class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali
            </a>
        </div>
    </x-page-header>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article class="transaction-detail-card tone-blue">
            <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="transaction-detail-label">Kasir</p>
                    <p class="transaction-detail-value">{{ $transaction->user->name ?? '-' }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">{{ '@' . ($transaction->user->username ?? '-') }}</p>
                </div>
                <span class="transaction-detail-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </span>
            </div>
        </article>

        <article class="transaction-detail-card tone-violet">
            <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="transaction-detail-label">Pembayaran</p>
                    <p class="transaction-detail-value">{{ $paymentMethodLabel }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">pembayaran transaksi</p>
                </div>
                <span class="transaction-detail-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </span>
            </div>
        </article>

        <article class="transaction-detail-card tone-sky">
            <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="transaction-detail-label">Waktu</p>
                    <p class="transaction-detail-value">{{ $transaction->created_at->translatedFormat('d M Y') }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">{{ $transaction->created_at->format('H:i:s') }}</p>
                </div>
                <span class="transaction-detail-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </span>
            </div>
        </article>

        <article class="transaction-detail-card tone-emerald">
            <div class="relative z-10 flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="transaction-detail-label">Total</p>
                    <p class="transaction-detail-value text-lg">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-400">{{ $totalQty }} jumlah dari {{ $transaction->details->count() }} item</p>
                </div>
                <span class="transaction-detail-icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v-1m9-4a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
        </article>
    </section>

    @if($isVoid)
        <section class="transaction-detail-void p-4 sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m0 3.75h.008M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>
                    </span>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-amber-700 dark:text-amber-300">Status Pembatalan</p>
                        <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Transaksi Dibatalkan</h2>
                        <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">
                            {{ $voidReasonLabel ? 'Alasan: '.$voidReasonLabel : 'Alasan pembatalan belum tercatat.' }}
                        </p>
                        <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-300">Transaksi ini telah dibatalkan dan tidak dihitung dalam omzet penjualan.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:min-w-[360px]">
                    <div class="rounded-xl border border-amber-200 bg-white/75 px-3 py-2.5 dark:border-amber-500/25 dark:bg-slate-950/35">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Dibatalkan Oleh</p>
                        <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-100">{{ $transaction->voidedBy->name ?? '-' }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-white/75 px-3 py-2.5 dark:border-amber-500/25 dark:bg-slate-950/35">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Waktu Pembatalan</p>
                        <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-100">{{ $transaction->voided_at?->format('d M Y H:i') ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="transaction-detail-info-grid">
        <article class="transaction-detail-panel">
            <div class="transaction-detail-panel-header">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Ringkasan Pembayaran</h2>
                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                    Pembayaran
                </span>
            </div>
            <div class="p-4 sm:p-5">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">Total</span>
                        <span class="text-sm font-black text-slate-800 dark:text-slate-100">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">Dibayar</span>
                        <span class="text-sm font-black text-blue-600 dark:text-blue-400">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-slate-100 pt-3 dark:border-slate-800">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-black uppercase tracking-wide text-slate-700 dark:text-slate-200">Kembalian</span>
                            <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </article>

        <article class="transaction-detail-panel">
            <div class="transaction-detail-panel-header">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Informasi Transaksi</h2>
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                    Kode
                </span>
            </div>
            <div class="p-4 sm:p-5">
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-center dark:border-slate-700 dark:bg-slate-950/40">
                    <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Kode Transaksi</p>
                    <p class="mt-2 break-all font-mono text-base font-black text-slate-900 dark:text-white">{{ $transaction->transaction_code }}</p>
                </div>
            </div>
        </article>
    </section>

    <section class="transaction-detail-panel">
        <div class="transaction-detail-panel-header">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Item Transaksi</h2>
                <p class="mt-0.5 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $transaction->details->count() }} item pembelian</p>
            </div>
            <span class="rounded-full border border-slate-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:text-slate-400">
                {{ $totalQty }} jumlah
            </span>
        </div>

        <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($transaction->details as $item)
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-black text-slate-900 dark:text-white">{{ $item->menu->name ?? '-' }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Rp {{ number_format($item->price, 0, ',', '.') }} x {{ $item->quantity }}</p>
                        </div>
                        <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ $item->quantity }}x</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Harga</span>
                        <span class="font-black text-slate-900 dark:text-white">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </article>
            @empty
                <div class="px-4 py-10 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">Item transaksi tidak tersedia</div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[620px] text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 dark:border-slate-800 dark:bg-slate-950/40">
                    <tr>
                        <th class="px-5 py-3 text-left font-black">Menu</th>
                        <th class="px-5 py-3 text-center font-black">Jumlah</th>
                        <th class="px-5 py-3 text-right font-black">Harga</th>
                        <th class="px-5 py-3 text-right font-black">Total Harga</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($transaction->details as $item)
                        <tr class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/35">
                            <td class="px-5 py-4 font-black text-slate-800 dark:text-white">{{ $item->menu->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex min-w-10 items-center justify-center rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ $item->quantity }}</span>
                            </td>
                            <td class="px-5 py-4 text-right font-semibold text-slate-600 dark:text-slate-300">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="px-5 py-4 text-right font-black text-slate-900 dark:text-white">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">Item transaksi tidak tersedia</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
