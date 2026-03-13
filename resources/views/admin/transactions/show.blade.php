@extends('layouts.app')

@section('title', 'Detail Transaksi')

@section('content')
@php
    $routePrefix = 'admin.transactions';
@endphp

<div class="mx-auto w-full max-w-7xl px-0 sm:px-2 md:px-4 py-4 md:py-6 space-y-5 md:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-slate-800 dark:text-white">Detail Transaksi</h1>
            <p class="text-sm text-slate-500 mt-1 break-all">{{ $transaction->transaction_code }}</p>
        </div>

        <a href="{{ route($routePrefix.'.index') }}"
           class="inline-flex items-center justify-center self-start px-3 py-1.5 text-xs md:text-sm font-medium rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition shadow-sm">
            &larr; Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 md:gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500">Kasir</p>
            <p class="mt-1 font-semibold break-words">{{ $transaction->user->name ?? '-' }}</p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500">Metode Bayar</p>
            <p class="mt-1 font-semibold break-words">{{ $transaction->paymentMethod->name ?? '-' }}</p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500">Tanggal</p>
            <p class="mt-1 font-semibold">{{ $transaction->created_at->format('d M Y H:i') }}</p>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4">
            <p class="text-xs text-slate-500">Total</p>
            <p class="mt-1 text-lg font-semibold">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 space-y-3">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Ringkasan Pembayaran</h3>

            <div class="flex justify-between text-sm gap-3">
                <span class="text-slate-500">Total</span>
                <span class="font-medium text-right">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between text-sm gap-3">
                <span class="text-slate-500">Dibayar</span>
                <span class="font-medium text-right">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between text-sm gap-3">
                <span class="text-slate-500">Kembalian</span>
                <span class="font-medium text-green-600 text-right">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 flex items-center justify-center">
            <div class="text-center">
                <p class="text-xs text-slate-500">Kode Transaksi</p>
                <p class="text-lg font-semibold mt-1 break-all">{{ $transaction->transaction_code }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
        <div class="px-4 md:px-5 py-4 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Item Transaksi</h3>
        </div>

        <div class="md:hidden p-3 space-y-3">
            @forelse($transaction->details as $item)
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                    <p class="font-semibold text-slate-800 dark:text-white break-words">{{ $item->menu->name ?? '-' }}</p>
                    <p class="flex justify-between text-sm"><span class="text-slate-500">Qty</span><span class="font-medium">{{ $item->quantity }}</span></p>
                    <p class="flex justify-between text-sm"><span class="text-slate-500">Harga</span><span class="font-medium">Rp {{ number_format($item->price, 0, ',', '.') }}</span></p>
                    <p class="flex justify-between text-sm"><span class="text-slate-500">Subtotal</span><span class="font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span></p>
                </div>
            @empty
                <div class="py-8 text-center text-slate-500 text-sm">Detail item tidak tersedia</div>
            @endforelse
        </div>

        <div class="hidden md:block overflow-x-auto">
            <table class="w-full min-w-[500px] text-sm">
                <thead class="text-xs uppercase text-slate-400 bg-slate-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left">Menu</th>
                        <th class="px-6 py-3 text-left">Qty</th>
                        <th class="px-6 py-3 text-left">Harga</th>
                        <th class="px-6 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaction->details as $item)
                        <tr class="border-t border-slate-200 dark:border-slate-800">
                            <td class="px-6 py-4 font-medium break-words">{{ $item->menu->name ?? '-' }}</td>
                            <td class="px-6 py-4">{{ $item->quantity }}</td>
                            <td class="px-6 py-4">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500">Detail item tidak tersedia</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
