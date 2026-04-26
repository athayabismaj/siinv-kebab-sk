@extends('layouts.app')

@section('content')
@php
    $routePrefix = 'admin.transactions';

    $hasActiveFilters = request()->filled('search')
        || request()->filled('user_id')
        || request()->filled('payment_method_id')
        || request()->filled('date_from')
        || request()->filled('date_to');
@endphp

<div class="space-y-8 max-w-full overflow-x-hidden">
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Kasir</span>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Monitoring Transaksi</span>
        </nav>

        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white tracking-tight mb-3">
            Monitoring Transaksi
        </h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
            Pantau transaksi kasir berdasarkan periode dan filter kasir/metode pembayaran.
        </p>

        <div class="inline-flex items-center gap-2.5 px-3 py-1.5 bg-blue-50/50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 rounded-lg shadow-sm">
            <span class="text-[11px] sm:text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide">
                Periode Data:
                <span class="ml-1 text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $dateFrom->format('d M Y') }}</span>
                @if(!$dateFrom->isSameDay($dateTo))
                    <span class="mx-0.5 text-slate-400 normal-case">-</span>
                    <span class="text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $dateTo->format('d M Y') }}</span>
                @endif
            </span>
        </div>
    </div>

    <form method="GET" action="{{ route($routePrefix.'.index') }}" id="filter-form" class="relative group z-10 mb-6">
        <div class="flex flex-col gap-3">
            <input type="hidden" name="type" id="hidden_type" value="{{ $type }}">
            <input type="hidden" name="date_from" id="hidden_date_from" value="{{ $dateFrom->toDateString() }}">
            <input type="hidden" name="date_to" id="hidden_date_to" value="{{ $dateTo->toDateString() }}">

            <div class="flex flex-col lg:flex-row gap-3 w-full">
                <div class="w-full lg:w-auto flex bg-slate-100 dark:bg-slate-800/50 p-1.5 rounded-xl border border-slate-200/50 dark:border-slate-700/50 shrink-0 overflow-x-auto no-scrollbar justify-start sm:justify-center">
                    <button type="button" onclick="changeType('daily')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'daily' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
                    <button type="button" onclick="changeType('weekly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'weekly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</button>
                    <button type="button" onclick="changeType('monthly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center {{ $type === 'monthly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
                </div>

                <div class="flex-1 flex items-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm p-1 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all w-full min-w-0">
                    <a href="{{ route($routePrefix.'.index', ['type' => $type, 'date_from' => $prevFrom, 'date_to' => $prevTo, 'search' => request('search'), 'user_id' => request('user_id'), 'payment_method_id' => request('payment_method_id')]) }}" title="Sebelumnya" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-700 dark:hover:text-blue-400 transition-all shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                    </a>

                    <div class="flex-1 flex px-3">
                        <input type="{{ $inputType }}" value="{{ $inputValue }}" onchange="updateDateRange(this, '{{ $type }}')" class="w-full text-center bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none dark:[color-scheme:dark]">
                    </div>

                    @if($isFuture)
                        <div class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-300 dark:text-slate-700 cursor-not-allowed shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    @else
                        <a href="{{ route($routePrefix.'.index', ['type' => $type, 'date_from' => $nextFrom, 'date_to' => $nextTo, 'search' => request('search'), 'user_id' => request('user_id'), 'payment_method_id' => request('payment_method_id')]) }}" title="Berikutnya" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:hover:bg-slate-700 dark:hover:text-blue-400 transition-all shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-3 w-full">
                <div class="flex-1 flex items-center bg-white dark:bg-slate-800 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all min-w-0">
                    <input type="text" name="search" id="search-input" value="{{ request('search') }}" placeholder="Cari Kode Transaksi / Kasir..." autocomplete="off" class="w-full bg-transparent border-none py-1.5 focus:ring-0 text-[13px] font-medium text-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none">
                </div>

                <div class="flex flex-row gap-3 flex-1">
                    <select name="user_id" onchange="this.form.submit()" class="flex-1 min-w-0 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm text-[13px] font-medium text-slate-700 dark:text-slate-200">
                        <option value="">Semua Kasir</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" {{ (string) request('user_id') === (string) $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                        @endforeach
                    </select>

                    <select name="payment_method_id" onchange="this.form.submit()" class="flex-1 min-w-0 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm text-[13px] font-medium text-slate-700 dark:text-slate-200">
                        <option value="">Semua Pembayaran</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" {{ (string) request('payment_method_id') === (string) $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                        @endforeach
                    </select>

                    @if($hasActiveFilters)
                        <a href="{{ route($routePrefix.'.index') }}" title="Reset Filter" class="inline-flex items-center justify-center shrink-0 w-[38px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 rounded-xl hover:text-red-500 transition-all shadow-sm">x</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Jumlah Transaksi</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($totalTransactions, 0, ',', '.') }}</p>
        </div>
        <div class="p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Omzet</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
        <div class="p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Rata-rata Transaksi</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white">Rp {{ number_format($avgTransaction, 0, ',', '.') }}</p>
        </div>
        <div class="p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Kasir Teraktif</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white truncate">{{ $topCashierName }}</p>
        </div>
    </div>

    @forelse($groupedTransactions as $date => $items)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                <h2 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</h2>
                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-[10px] font-bold text-slate-500 dark:text-slate-300">{{ $items->count() }} transaksi</span>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                        <tr>
                            <th class="px-6 py-3">Kode</th>
                            <th class="px-6 py-3">Kasir</th>
                            <th class="px-6 py-3">Pembayaran</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-center">Item</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3">Waktu</th>
                            <th class="px-6 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                        @foreach($items as $trx)
                            @php $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount; @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-800 dark:text-white">{{ $trx->transaction_code }}</td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $trx->user->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $trx->paymentMethod->name ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold {{ $isPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $isPaid ? 'Lunas' : 'Kurang' }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">{{ $trx->details_count }}</td>
                                <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs tabular-nums">{{ $trx->created_at->format('H:i') }}</td>
                                <td class="px-6 py-4"><a href="{{ route($routePrefix.'.show', $trx->id) }}" class="text-blue-600 hover:text-blue-700 text-xs font-bold">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="md:hidden p-4 space-y-3">
                @foreach($items as $trx)
                    @php $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount; @endphp
                    <div class="rounded-xl border border-slate-100 dark:border-slate-800 p-4 space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-bold text-slate-800 dark:text-white text-sm break-all">{{ $trx->transaction_code }}</p>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold {{ $isPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $isPaid ? 'Lunas' : 'Kurang' }}</span>
                        </div>
                        <p class="text-xs text-slate-500">{{ $trx->user->name ?? '-' }} - {{ $trx->paymentMethod->name ?? '-' }}</p>
                        <p class="text-xs text-slate-500">Total Rp {{ number_format($trx->total_amount, 0, ',', '.') }} - {{ $trx->created_at->format('H:i') }}</p>
                        <a href="{{ route($routePrefix.'.show', $trx->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700">Detail</a>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-16 text-center text-slate-400 text-sm">Tidak ada transaksi dalam rentang tanggal yang dipilih.</div>
    @endforelse

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-400 dark:text-slate-500 text-center sm:text-left">
            Halaman <span class="font-bold text-slate-700 dark:text-slate-200">{{ $transactions->currentPage() }}</span>
            dari <span class="font-bold text-slate-700 dark:text-slate-200">{{ $transactions->lastPage() }}</span>
            | Total <span class="font-bold text-slate-700 dark:text-slate-200">{{ $transactions->total() }}</span> transaksi
        </p>
        <div class="flex items-center justify-center gap-1.5">
            @if ($transactions->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-300 dark:text-slate-700 cursor-not-allowed">&lt; Prev</span>
            @else
                <a href="{{ $transactions->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">&lt; Prev</a>
            @endif
            @if ($transactions->hasMorePages())
                <a href="{{ $transactions->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm shadow-blue-500/20">Next &gt;</a>
            @else
                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-300 dark:text-slate-700 cursor-not-allowed">Next &gt;</span>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function formatStr(d) {
    return d.getFullYear() + '-' + (d.getMonth() < 9 ? '0' : '') + (d.getMonth() + 1) + '-' + (d.getDate() < 10 ? '0' : '') + d.getDate();
}

function resolveWeekRange(dateObj) {
    let day = dateObj.getDay();
    let diff = day === 0 ? -6 : 1 - day;
    let start = new Date(dateObj);
    start.setDate(dateObj.getDate() + diff);
    let end = new Date(start);
    end.setDate(start.getDate() + 6);
    return { from: formatStr(start), to: formatStr(end) };
}

function changeType(newType) {
    document.getElementById('hidden_type').value = newType;
    let d = new Date();
    let from = '', to = '';

    if (newType === 'daily') {
        from = to = formatStr(d);
    } else if (newType === 'weekly') {
        const range = resolveWeekRange(d);
        from = range.from;
        to = range.to;
    } else if (newType === 'monthly') {
        let start = new Date(d.getFullYear(), d.getMonth(), 1);
        let end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        from = formatStr(start);
        to = formatStr(end);
    }

    document.getElementById('hidden_date_from').value = from;
    document.getElementById('hidden_date_to').value = to;
    document.getElementById('filter-form').submit();
}

function updateDateRange(input, type) {
    let val = input.value;
    if (!val) return;

    let from = '', to = '';
    if (type === 'daily') {
        from = to = val;
    } else if (type === 'weekly') {
        const range = resolveWeekRange(new Date(val));
        from = range.from;
        to = range.to;
    } else if (type === 'monthly') {
        let parts = val.split('-');
        let start = new Date(parts[0], parts[1] - 1, 1);
        let end = new Date(parts[0], parts[1], 0);
        from = formatStr(start);
        to = formatStr(end);
    }

    document.getElementById('hidden_date_from').value = from;
    document.getElementById('hidden_date_to').value = to;
    document.getElementById('filter-form').submit();
}

let timeout = null;
const searchInput = document.getElementById('search-input');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            document.getElementById('filter-form').submit();
        }, 500);
    });
}
</script>
@endpush
