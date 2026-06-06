@extends('layouts.app')

@section('title', 'Detail Transaksi')

@section('content')
@php
    $routePrefix = 'owner.transactions';
    $isPaid = (float) $transaction->paid_amount >= (float) $transaction->total_amount;
@endphp

<div class="space-y-8 max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
                <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 transition-colors">Beranda</a>
                <span class="text-slate-200 dark:text-slate-800">/</span>
                <a href="{{ route($routePrefix.'.index') }}" class="hover:text-blue-600 transition-colors">Riwayat</a>
                <span class="text-slate-200 dark:text-slate-800">/</span>
                <span class="text-slate-600 dark:text-slate-400">Detail</span>
            </nav>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Detail Transaksi</h1>
            <p class="text-slate-400 dark:text-slate-500 text-sm mt-3 flex items-center gap-2 flex-wrap">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                <span class="font-mono font-bold text-slate-700 dark:text-slate-300 break-all">{{ $transaction->transaction_code }}</span>
                <span class="text-slate-300 dark:text-slate-700">|||||||||||||||</span>
                <span>{{ $transaction->created_at->translatedFormat('d F Y, H:i') }}</span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800 text-xs font-black uppercase tracking-widest rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print
            </button>
            <a href="{{ route($routePrefix.'.index') }}"
               class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase tracking-widest rounded-xl hover:scale-105 active:scale-95 transition-all shadow-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Status Pembayaran --}}
        <div class="relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 overflow-hidden group">
            <div class="absolute -top-10 -right-10 w-32 h-32 {{ $isPaid ? 'bg-emerald-500/5 dark:bg-emerald-400/5' : 'bg-red-500/5 dark:bg-red-400/5' }} rounded-full blur-3xl"></div>
            <div class="relative flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-2xl {{ $isPaid ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-red-50 dark:bg-red-900/20' }} flex items-center justify-center mb-4">
                    @if($isPaid)
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    @else
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    @endif
                </div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-2">Status</p>
                @if($isPaid)
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Lunas
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                        Kurang Bayar
                    </span>
                @endif
            </div>
        </div>

        {{-- Kasir --}}
        <div class="relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 overflow-hidden group hover:border-blue-500/30 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/5 dark:bg-blue-400/5 rounded-full blur-3xl"></div>
            <div class="relative flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">Kasir</p>
                <p class="text-base font-black text-slate-900 dark:text-white tracking-tight truncate max-w-full">{{ $transaction->user->name ?? '-' }}</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ '@' . ($transaction->user->username ?? '-') }}</p>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div class="relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 overflow-hidden group hover:border-violet-500/30 hover:shadow-2xl hover:shadow-violet-500/10 transition-all duration-500">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-violet-500/5 dark:bg-violet-400/5 rounded-full blur-3xl"></div>
            <div class="relative flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-2xl bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">Pembayaran</p>
                <p class="text-base font-black text-slate-900 dark:text-white tracking-tight">{{ $transaction->paymentMethod->name ?? '-' }}</p>
            </div>
        </div>

        {{-- Total Item --}}
        <div class="relative bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 overflow-hidden group hover:border-orange-500/30 hover:shadow-2xl hover:shadow-orange-500/10 transition-all duration-500">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-orange-500/5 dark:bg-orange-400/5 rounded-full blur-3xl"></div>
            <div class="relative flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-2xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">Total Item</p>
                <div class="flex items-baseline gap-1">
                    <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $transaction->details->count() }}</p>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">item</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
            <h2 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Item Pembelian
            </h2>
        </div>

        {{-- Mobile View --}}
        <div class="md:hidden p-4 space-y-3">
            @forelse($transaction->details as $index => $item)
                <div class="rounded-xl border border-slate-100 dark:border-slate-800 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-bold text-slate-500">{{ $index + 1 }}</span>
                        <p class="font-bold text-slate-800 dark:text-white text-sm break-words">{{ $item->menu->name ?? '-' }}</p>
                    </div>

                    <div class="pl-8 flex flex-col gap-1">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Jumlah</span>
                            <span class="px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg text-xs font-bold">{{ $item->quantity }}x</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Harga Satuan</span>
                            <span class="text-slate-600 dark:text-slate-300 text-xs font-semibold">Rp {{ number_format($item->price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 mt-1 border-t border-slate-100 dark:border-slate-700/50">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Subtotal</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                    </div>
                    <p class="text-slate-400 text-sm font-medium">Tidak ada item</p>
                </div>
            @endforelse
        </div>

        {{-- Desktop View --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-sm text-left">
            <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-3 w-12">#</th>
                        <th class="px-6 py-3">Menu</th>
                        <th class="px-6 py-3 text-center">Qty</th>
                        <th class="px-6 py-3 text-right">Harga Satuan</th>
                        <th class="px-6 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                    @forelse($transaction->details as $index => $item)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-bold text-slate-500">{{ $index + 1 }}</span>
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-800 dark:text-white">{{ $item->menu->name ?? '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg text-sm font-bold">{{ $item->quantity }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-slate-600 dark:text-slate-300 font-semibold">
                                Rp {{ number_format($item->price, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-4">
                                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    </div>
                                    <p class="text-slate-400 text-sm font-medium">Tidak ada item</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Ringkasan Pembayaran --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
                <h2 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Ringkasan Pembayaran
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Subtotal</span>
                    <span class="text-lg font-bold text-slate-700 dark:text-slate-300">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Dibayar</span>
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <span class="text-base font-black text-slate-700 dark:text-slate-200 uppercase tracking-wide">Kembalian</span>
                    <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Informasi Tambahan --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
                <h2 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Informasi Transaksi
                </h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between gap-4">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Kode Transaksi</span>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300 font-mono text-right break-all">{{ $transaction->transaction_code }}</span>
                </div>
                <div class="flex items-start justify-between gap-4 pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Waktu Transaksi</span>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300 text-right">{{ $transaction->created_at->translatedFormat('l, d F Y') }}<br><span class="text-blue-600">{{ $transaction->created_at->format('H:i:s') }}</span></span>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Total Item</span>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $transaction->details->count() }} Item</span>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <span class="text-sm text-slate-500 dark:text-slate-400">Total Quantity</span>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $transaction->details->sum('quantity') }} Pcs</span>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Print Styles --}}
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #print-area, #print-area * {
        visibility: visible;
    }
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 20px;
    }
    button, .no-print {
        display: none !important;
    }
}
</style>

<div id="print-area" class="hidden print:block">
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold">DETAIL TRANSAKSI</h1>
        <p class="text-sm text-slate-600 mt-2">{{ $transaction->transaction_code }}</p>
        <p class="text-sm text-slate-600">{{ $transaction->created_at->translatedFormat('d F Y, H:i') }}</p>
    </div>

    <div class="mb-4 border-b pb-4">
        <table class="w-full text-sm">
            <tr><td class="py-1 font-semibold w-32">Kasir</td><td>: {{ $transaction->user->name ?? '-' }}</td></tr>
            <tr><td class="py-1 font-semibold">Metode Pembayaran</td><td>: {{ $transaction->paymentMethod->name ?? '-' }}</td></tr>
            <tr><td class="py-1 font-semibold">Status</td><td>: {{ $isPaid ? 'Lunas' : 'Kurang Bayar' }}</td></tr>
        </table>
    </div>

    <table class="w-full text-sm mb-4">
        <thead class="border-b-2 border-slate-900">
            <tr>
                <th class="py-2 text-left">#</th>
                <th class="py-2 text-left">Menu</th>
                <th class="py-2 text-center">Qty</th>
                <th class="py-2 text-right">Harga</th>
                <th class="py-2 text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody class="border-b border-slate-300">
            @foreach($transaction->details as $index => $item)
            <tr>
                <td class="py-2">{{ $index + 1 }}</td>
                <td class="py-2">{{ $item->menu->name ?? '-' }}</td>
                <td class="py-2 text-center">{{ $item->quantity }}</td>
                <td class="py-2 text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="py-2 text-right font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-6 text-right">
        <table class="ml-auto text-sm">
            <tr><td class="py-1 pr-4 text-left">Subtotal</td><td class="py-1 text-right font-semibold">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td></tr>
            <tr><td class="py-1 pr-4 text-left">Dibayar</td><td class="py-1 text-right font-semibold">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td></tr>
            <tr class="border-t-2 border-slate-900"><td class="py-2 pr-4 text-left font-bold">Kembalian</td><td class="py-2 text-right font-bold text-lg">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <div class="mt-8 text-center text-xs text-slate-500">
        <p>Terima kasih atas kunjungan Anda</p>
        <p class="mt-1">Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }}</p>
    </div>
</div>

@endsection


