@extends('layouts.app')

@section('title', 'Detail Transaksi')

@push('styles')
<style>
    .transaction-detail-shell {
        width: 100%;
        max-width: none;
        margin: 0;
    }

    .transaction-detail-card,
    .transaction-detail-panel,
    .transaction-detail-void {
        border: 1px solid rgb(226 232 240);
        background: rgb(255 255 255);
        box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
    }

    .dark .transaction-detail-card,
    .dark .transaction-detail-panel {
        border-color: rgb(30 41 59);
        background: rgb(15 23 42);
        box-shadow: none;
    }

    .transaction-detail-card {
        position: relative;
        overflow: hidden;
        min-height: 96px;
        border-radius: 14px;
        padding: 16px;
    }

    .transaction-detail-card::after {
        content: "";
        position: absolute;
        right: -34px;
        top: -40px;
        width: 92px;
        height: 92px;
        border-radius: 999px;
        background: rgb(var(--tone-rgb, 37 99 235) / .08);
        pointer-events: none;
    }

    .transaction-detail-icon {
        display: inline-flex;
        width: 38px;
        height: 38px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: rgb(var(--tone-rgb, 37 99 235) / .10);
        color: rgb(var(--tone-rgb, 37 99 235));
        box-shadow: inset 0 0 0 1px rgb(var(--tone-rgb, 37 99 235) / .16);
    }

    .transaction-detail-label {
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: rgb(148 163 184);
    }

    .transaction-detail-value {
        margin-top: 8px;
        font-size: 15px;
        font-weight: 900;
        color: rgb(15 23 42);
        overflow-wrap: anywhere;
    }

    .dark .transaction-detail-value {
        color: rgb(248 250 252);
    }

    .transaction-detail-panel {
        overflow: hidden;
        border-radius: 14px;
    }

    .transaction-detail-info-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
    }

    .transaction-detail-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid rgb(226 232 240);
        padding: 14px 16px;
    }

    .dark .transaction-detail-panel-header {
        border-color: rgb(30 41 59);
    }

    .transaction-detail-void {
        overflow: hidden;
        border-color: rgb(252 211 77);
        border-radius: 14px;
        background:
            radial-gradient(circle at right top, rgb(245 158 11 / .12), transparent 34%),
            rgb(255 251 235);
    }

    .dark .transaction-detail-void {
        border-color: rgb(245 158 11 / .35);
        background:
            radial-gradient(circle at right top, rgb(245 158 11 / .16), transparent 36%),
            rgb(15 23 42);
    }

    .tone-blue { --tone-rgb: 37 99 235; }
    .tone-emerald { --tone-rgb: 5 150 105; }
    .tone-sky { --tone-rgb: 2 132 199; }
    .tone-violet { --tone-rgb: 124 58 237; }
    .tone-slate { --tone-rgb: 71 85 105; }

    .dark .tone-blue { --tone-rgb: 96 165 250; }
    .dark .tone-emerald { --tone-rgb: 52 211 153; }
    .dark .tone-sky { --tone-rgb: 56 189 248; }
    .dark .tone-violet { --tone-rgb: 167 139 250; }
    .dark .tone-slate { --tone-rgb: 148 163 184; }

    @media (min-width: 1024px) {
        .transaction-detail-info-grid {
            grid-template-columns: minmax(0, .82fr) minmax(0, 1.18fr);
        }
    }
</style>
@endpush

@section('content')
@php
    $routePrefix = 'admin.transactions';
    $isVoid = strtolower($transaction->status ?? '') === 'void';
    $voidReasonLabel = match (strtolower((string) $transaction->void_reason)) {
        'restock' => 'Kembali ke Stok (Restock)',
        'waste' => 'Buang sebagai Sampah (Waste)',
        default => null,
    };
    $totalQty = (int) $transaction->details->sum('quantity');
@endphp

<div class="transaction-detail-shell space-y-5 pb-10">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <nav class="mb-2 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Beranda</a>
                <span>/</span>
                <a href="{{ route($routePrefix.'.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Riwayat Transaksi</a>
                <span>/</span>
                <span class="text-blue-600 dark:text-blue-400">Detail</span>
            </nav>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Detail Transaksi</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    <span class="h-1.5 w-1.5 rounded-full {{ $isVoid ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                    {{ $isVoid ? 'VOID' : 'Selesai' }}
                </span>
                <span class="font-mono text-sm font-bold text-slate-500 dark:text-slate-400 break-all">{{ $transaction->transaction_code }}</span>
            </div>
        </div>

        <a href="{{ route($routePrefix.'.index') }}"
           class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </header>

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
                    <p class="transaction-detail-label">Metode Bayar</p>
                    <p class="transaction-detail-value">{{ $transaction->paymentMethod->name ?? '-' }}</p>
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
                    <p class="mt-1 text-xs font-semibold text-slate-400">{{ $totalQty }} qty dari {{ $transaction->details->count() }} item</p>
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
                        <h2 class="mt-1 text-xl font-black text-slate-900 dark:text-white">Transaksi VOID</h2>
                        <p class="mt-1 text-sm font-medium text-slate-600 dark:text-slate-300">
                            {{ $voidReasonLabel ? 'Alasan: '.$voidReasonLabel : 'Alasan void belum tercatat.' }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:min-w-[360px]">
                    <div class="rounded-xl border border-amber-200 bg-white/75 px-3 py-2.5 dark:border-amber-500/25 dark:bg-slate-950/35">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Dibatalkan Oleh</p>
                        <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-100">{{ $transaction->voidedBy->name ?? '-' }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-white/75 px-3 py-2.5 dark:border-amber-500/25 dark:bg-slate-950/35">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Waktu Void</p>
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
                    Cashflow
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
                {{ $totalQty }} Qty
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
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Subtotal</span>
                        <span class="font-black text-slate-900 dark:text-white">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                </article>
            @empty
                <div class="px-4 py-10 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">Detail item tidak tersedia</div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[620px] text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 dark:border-slate-800 dark:bg-slate-950/40">
                    <tr>
                        <th class="px-5 py-3 text-left font-black">Menu</th>
                        <th class="px-5 py-3 text-center font-black">Qty</th>
                        <th class="px-5 py-3 text-right font-black">Harga</th>
                        <th class="px-5 py-3 text-right font-black">Subtotal</th>
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
                            <td colspan="4" class="px-5 py-12 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">Detail item tidak tersedia</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
