@extends('layouts.app')

@section('content')

@php
    $routePrefix = 'admin.transactions';
    $activeDate = $selectedDate ?? now()->startOfDay();
    $todayDate = now()->startOfDay();
    $isToday = $activeDate->isSameDay($todayDate);

    $navParams = [
        'search' => request('search'),
        'payment_method_id' => request('payment_method_id'),
    ];

    $prevDateParams = array_filter(array_merge($navParams, [
        'date' => $activeDate->copy()->subDay()->toDateString(),
    ]), fn ($value) => $value !== null && $value !== '');

    $nextDateParams = array_filter(array_merge($navParams, [
        'date' => $activeDate->copy()->addDay()->toDateString(),
    ]), fn ($value) => $value !== null && $value !== '');

    $hasActiveFilters = request()->filled('search')
        || request()->filled('payment_method_id')
        || request()->filled('date');
@endphp

<div class="space-y-6 max-w-full overflow-x-hidden">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
        <div>
            <h1 class="text-xl md:text-2xl font-semibold text-slate-800 dark:text-white">
                Monitoring Transaksi
            </h1>
            <p class="text-sm text-slate-500">
                Data transaksi kasir per hari ({{ $activeDate->translatedFormat('d M Y') }})
            </p>
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

            <select name="payment_method_id"
                    class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
                <option value="">Semua Pembayaran</option>
                @foreach($paymentMethods as $method)
                    <option value="{{ $method->id }}"
                        {{ (string) request('payment_method_id') === (string) $method->id ? 'selected' : '' }}>
                        {{ $method->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                    class="w-full md:w-auto px-5 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                Terapkan
            </button>

            @if($hasActiveFilters)
                <a href="{{ route($routePrefix.'.index') }}"
                   class="w-full md:w-auto text-sm text-slate-500 px-2 py-2 text-center hover:text-slate-700 dark:hover:text-slate-300">
                    Reset
                </a>
            @endif
        </div>
    </form>

    @php
        $groupedTransactions = $transactions->getCollection()->groupBy(fn ($trx) => $trx->created_at->toDateString());
    @endphp

    @forelse($groupedTransactions as $date => $items)
        @php
            $groupDate = \Carbon\Carbon::parse($date);
            $groupLabel = $groupDate->translatedFormat('d M Y');
            if ($groupDate->isToday()) {
                $groupLabel = 'Hari ini';
            } elseif ($groupDate->isYesterday()) {
                $groupLabel = 'Kemarin';
            }
        @endphp

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="px-4 md:px-6 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $groupLabel }}</p>
            </div>

            <div class="md:hidden p-3 space-y-3">
                @foreach($items as $trx)
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3 space-y-2">
                        <p class="font-semibold text-slate-800 dark:text-white break-all">{{ $trx->transaction_code }}</p>
                        <p class="flex justify-between text-sm gap-2"><span class="text-slate-500">Kasir</span><span class="font-medium text-right">{{ $trx->user->name ?? '-' }}</span></p>
                        <p class="flex justify-between text-sm gap-2"><span class="text-slate-500">Pembayaran</span><span class="font-medium text-right">{{ $trx->paymentMethod->name ?? '-' }}</span></p>
                        <p class="flex justify-between text-sm gap-2"><span class="text-slate-500">Total</span><span class="font-semibold text-right">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</span></p>
                        <p class="flex justify-between text-sm gap-2"><span class="text-slate-500">Waktu</span><span class="font-medium text-right">{{ $trx->created_at->format('d M Y H:i') }}</span></p>
                        <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="inline-block text-sm text-blue-600 hover:underline">Detail</a>
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-3 text-left">Kode</th>
                            <th class="px-6 py-3 text-left">Kasir</th>
                            <th class="px-6 py-3 text-left">Pembayaran</th>
                            <th class="px-6 py-3 text-left">Total</th>
                            <th class="px-6 py-3 text-left">Tanggal</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $trx)
                            <tr class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-4 font-medium">{{ $trx->transaction_code }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $trx->user->name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-md bg-slate-100 dark:bg-slate-800">
                                        {{ $trx->paymentMethod->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-slate-500">{{ $trx->created_at->format('d M Y H:i') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="text-blue-600 hover:underline">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 px-6 py-10 text-center text-slate-500">
            Belum ada transaksi pada tanggal ini
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
