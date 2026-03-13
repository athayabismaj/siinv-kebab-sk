@extends('layouts.app')

@section('content')
@php
    $routePrefix = 'owner.transactions';
    $activeDate = $selectedDate ?? now()->startOfDay();
    $todayDate = now()->startOfDay();
    $isToday = $activeDate->isSameDay($todayDate);

    $navParams = [
        'search' => request('search'),
        'user_id' => request('user_id'),
        'payment_method_id' => request('payment_method_id'),
    ];

    $prevDateParams = array_filter(array_merge($navParams, [
        'date' => $activeDate->copy()->subDay()->toDateString(),
    ]), fn ($value) => $value !== null && $value !== '');

    $nextDateParams = array_filter(array_merge($navParams, [
        'date' => $activeDate->copy()->addDay()->toDateString(),
    ]), fn ($value) => $value !== null && $value !== '');

    $hasActiveFilters = request()->filled('search')
        || request()->filled('user_id')
        || request()->filled('payment_method_id')
        || request()->filled('date');
@endphp

<div class="space-y-6 max-w-full overflow-x-hidden">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-xl sm:text-2xl font-semibold text-slate-800 dark:text-white">Riwayat Transaksi</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Versi owner: ringkasan transaksi + daftar transaksi per hari.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Transaksi</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Omzet</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Rata-rata / Transaksi</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">Rp {{ number_format($avgTransaction, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
            <p class="text-xs uppercase tracking-wide text-slate-500">Kasir Teraktif</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">{{ $topCashierName }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route($routePrefix.'.index') }}" class="space-y-3">
        <div class="flex items-center gap-2 w-full">
            <a href="{{ route($routePrefix.'.index', $prevDateParams) }}"
               class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800">
                &lt;
            </a>

            <input type="date"
                   name="date"
                   value="{{ $activeDate->toDateString() }}"
                   max="{{ $todayDate->toDateString() }}"
                   class="flex-1 min-w-0 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">

            @if($isToday)
                <span class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600 cursor-not-allowed">
                    &gt;
                </span>
            @else
                <a href="{{ route($routePrefix.'.index', $nextDateParams) }}"
                   class="w-10 h-10 shrink-0 flex items-center justify-center rounded-lg border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800">
                    &gt;
                </a>
            @endif
        </div>

        <div class="flex flex-col md:flex-row md:items-center gap-2">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari kode / nama kasir..."
                   class="flex-1 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">

            <select name="user_id"
                    class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                <option value="">Semua Kasir</option>
                @foreach($cashiers as $cashier)
                    <option value="{{ $cashier->id }}" {{ (string) request('user_id') === (string) $cashier->id ? 'selected' : '' }}>
                        {{ $cashier->name }}
                    </option>
                @endforeach
            </select>

            <select name="payment_method_id"
                    class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                <option value="">Semua Pembayaran</option>
                @foreach($paymentMethods as $method)
                    <option value="{{ $method->id }}" {{ (string) request('payment_method_id') === (string) $method->id ? 'selected' : '' }}>
                        {{ $method->name }}
                    </option>
                @endforeach
            </select>

            <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-2">
                <button type="submit"
                        class="w-full sm:w-auto px-5 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                    Terapkan
                </button>

                <a href="{{ route($routePrefix.'.export', request()->query()) }}"
                   class="w-full sm:w-auto px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700 text-center">
                    Export
                </a>

                @if($hasActiveFilters)
                    <a href="{{ route($routePrefix.'.index') }}"
                       class="w-full sm:w-auto text-sm text-slate-500 px-2 text-center sm:text-left hover:text-slate-700 dark:hover:text-slate-300">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    @forelse($groupedTransactions as $date => $items)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
                </h2>
                <span class="text-xs text-slate-500">{{ $items->count() }} transaksi</span>
            </div>

            <div class="md:hidden p-3 space-y-3">
                @foreach($items as $trx)
                    @php
                        $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount;
                    @endphp
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-semibold text-slate-800 dark:text-white break-all">{{ $trx->transaction_code }}</p>
                            @if($isPaid)
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-emerald-100 text-emerald-700">Lunas</span>
                            @else
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-red-100 text-red-700">Kurang</span>
                            @endif
                        </div>

                        <div class="text-sm text-slate-600 dark:text-slate-300 space-y-1">
                            <p class="flex items-start justify-between gap-2"><span>Kasir</span><span class="font-medium text-right">{{ $trx->user->name ?? '-' }}</span></p>
                            <p class="flex items-start justify-between gap-2"><span>Pembayaran</span><span class="font-medium text-right">{{ $trx->paymentMethod->name ?? '-' }}</span></p>
                            <p class="flex items-start justify-between gap-2"><span>Item</span><span class="font-medium">{{ $trx->details_count }}</span></p>
                            <p class="flex items-start justify-between gap-2"><span>Total</span><span class="font-medium">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</span></p>
                            <p class="flex items-start justify-between gap-2"><span>Dibayar</span><span class="font-medium">Rp {{ number_format($trx->paid_amount, 0, ',', '.') }}</span></p>
                            <p class="flex items-start justify-between gap-2"><span>Kembalian</span><span class="font-medium">Rp {{ number_format($trx->change_amount, 0, ',', '.') }}</span></p>
                            <p class="flex items-start justify-between gap-2"><span>Waktu</span><span class="font-medium">{{ $trx->created_at->format('H:i') }}</span></p>
                        </div>

                        <a href="{{ route($routePrefix.'.show', $trx->id) }}"
                           class="inline-block text-sm text-blue-600 hover:underline pt-1">
                            Detail
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-4 text-left">Kode</th>
                            <th class="px-6 py-4 text-left">Kasir</th>
                            <th class="px-6 py-4 text-left">Pembayaran</th>
                            <th class="px-6 py-4 text-left">Status</th>
                            <th class="px-6 py-4 text-left">Item</th>
                            <th class="px-6 py-4 text-left">Total</th>
                            <th class="px-6 py-4 text-left">Dibayar</th>
                            <th class="px-6 py-4 text-left">Kembalian</th>
                            <th class="px-6 py-4 text-left">Waktu</th>
                            <th class="px-6 py-4 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $trx)
                            @php
                                $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount;
                            @endphp
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">{{ $trx->transaction_code }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $trx->user->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $trx->paymentMethod->name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @if($isPaid)
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-emerald-100 text-emerald-700">Lunas</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-red-100 text-red-700">Kurang</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-500">{{ $trx->details_count }}</td>
                                <td class="px-6 py-4 text-slate-500">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-slate-500">Rp {{ number_format($trx->paid_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-slate-500">Rp {{ number_format($trx->change_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $trx->created_at->format('H:i') }}</td>
                                <td class="px-6 py-4 text-left">
                                    <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="text-blue-600 hover:underline">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-10 text-center text-slate-500">
            Belum ada transaksi pada tanggal ini.
        </div>
    @endforelse

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
                <span class="px-4 py-2 text-sm rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed text-center">
                    &lt; Previous
                </span>
            @else
                <a href="{{ $transactions->previousPageUrl() }}"
                   class="px-4 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition text-center">
                    &lt; Previous
                </a>
            @endif

            @if ($transactions->hasMorePages())
                <a href="{{ $transactions->nextPageUrl() }}"
                   class="px-4 py-2 text-sm rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition text-center">
                    Next &gt;
                </a>
            @else
                <span class="px-4 py-2 text-sm rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed text-center">
                    Next &gt;
                </span>
            @endif
        </div>
    </div>
</div>
@endsection

