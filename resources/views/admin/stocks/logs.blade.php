@extends('layouts.app')

@section('title', 'Riwayat Stok')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-800 dark:text-white">
                Riwayat Perubahan Stok
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Jejak restok, pemakaian transaksi, dan penyesuaian manual
            </p>
        </div>

        <a href="{{ route('admin.stocks.index') }}"
           class="inline-flex items-center justify-center rounded-xl
                  bg-slate-800 px-4 py-2 text-sm font-medium text-white
                  transition hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600">
            Kembali ke Stok
        </a>
    </div>


    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" class="flex flex-col gap-3 md:flex-row md:items-center">
            <div class="relative flex-1">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari nama bahan..."
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500
                              dark:border-slate-700 dark:bg-slate-800">
            </div>

            <select name="type"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500
                           dark:border-slate-700 dark:bg-slate-800">
                <option value="">Semua Tipe</option>
                <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Restok</option>
                <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Pemakaian</option>
                <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Penyesuaian</option>
            </select>

            <button type="submit"
                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700">
                Filter
            </button>

            @if(request()->filled('search') || request()->filled('type'))
                <a href="{{ route('admin.stocks.logs') }}"
                   class="text-sm text-slate-500 transition hover:text-blue-600">
                    Reset
                </a>
            @endif
        </form>
    </div>


    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        <th class="p-3 text-left">Tanggal</th>
                        <th class="p-3 text-left">Bahan</th>
                        <th class="p-3 text-center">Tipe</th>
                        <th class="p-3 text-center">Jumlah</th>
                        <th class="p-3 text-left">Sumber</th>
                        <th class="p-3 text-left">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $currentGroupDate = null;
                    @endphp
                    @forelse($logs as $log)
                        @php
                            $logDate = $log->created_at->toDateString();
                            $groupLabel = $log->created_at->format('d M Y');
                            if ($log->created_at->isToday()) {
                                $groupLabel = 'Hari ini';
                            } elseif ($log->created_at->isYesterday()) {
                                $groupLabel = 'Kemarin';
                            }

                            $rawQty = (float) $log->quantity;
                            $displayUnit = strtolower(trim((string) ($log->ingredient->display_unit ?? $log->ingredient->base_unit ?? '')));
                            $qtyDisplay = in_array($displayUnit, ['kg', 'l'], true) ? $rawQty / 1000 : $rawQty;

                            $formattedQty = number_format(abs($qtyDisplay), 2, '.', '');
                            $formattedQty = rtrim(rtrim($formattedQty, '0'), '.');
                            if ($formattedQty === '') {
                                $formattedQty = '0';
                            }

                            if ($log->type === 'in') {
                                $qtyPrefix = '+';
                                $qtyClass = 'text-emerald-600';
                                $typeClass = 'bg-emerald-100 text-emerald-700';
                                $typeLabel = 'Restok';
                                $sourceLabel = 'Manual Restok';
                            } elseif ($log->type === 'adjustment') {
                                $qtyPrefix = $rawQty >= 0 ? '+' : '-';
                                $qtyClass = $rawQty >= 0 ? 'text-amber-600' : 'text-red-600';
                                $typeClass = 'bg-amber-100 text-amber-700';
                                $typeLabel = 'Penyesuaian';
                                $sourceLabel = 'Manual Adjust';
                            } else {
                                $qtyPrefix = '-';
                                $qtyClass = 'text-red-600';
                                $typeClass = 'bg-red-100 text-red-700';
                                $typeLabel = 'Pemakaian';
                                $sourceLabel = $log->reference_id ? 'TRX-' . $log->reference_id : 'Transaksi';
                            }
                        @endphp

                        @if($currentGroupDate !== $logDate)
                            <tr>
                                <td colspan="6" class="bg-slate-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                    {{ $groupLabel }}
                                </td>
                            </tr>
                            @php
                                $currentGroupDate = $logDate;
                            @endphp
                        @endif

                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="p-3 whitespace-nowrap">
                                {{ $log->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="p-3">
                                {{ $log->ingredient->name ?? '-' }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $typeClass }}">
                                    {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="p-3 text-center font-semibold {{ $qtyClass }}">
                                {{ $qtyPrefix }}{{ $formattedQty }} {{ $displayUnit }}
                            </td>
                            <td class="p-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $sourceLabel }}
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300">
                                {{ $log->note ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-slate-500 dark:text-slate-400">
                                Belum ada riwayat stok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-2 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="text-sm text-slate-500 dark:text-slate-400 text-center md:text-left">
            Halaman
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $logs->currentPage() }}
            </span>
            dari
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $logs->lastPage() }}
            </span>
            | Total
            <span class="font-semibold text-slate-800 dark:text-white">
                {{ $logs->total() }}
            </span>
            data
        </div>

        <div class="flex justify-center md:justify-end gap-2">
            @if ($logs->onFirstPage())
                <span class="px-4 py-2 text-sm rounded-xl
                             bg-slate-200 dark:bg-slate-800
                             text-slate-400 cursor-not-allowed">
                    &lt; Previous
                </span>
            @else
                <a href="{{ $logs->previousPageUrl() }}"
                   class="px-4 py-2 text-sm rounded-xl
                          border border-slate-300 dark:border-slate-700
                          bg-white dark:bg-slate-900
                          text-slate-700 dark:text-slate-200
                          hover:bg-slate-50 dark:hover:bg-slate-800
                          transition">
                    &lt; Previous
                </a>
            @endif

            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}"
                   class="px-4 py-2 text-sm rounded-xl
                          bg-blue-600 text-white
                          hover:bg-blue-700 transition">
                    Next >
                </a>
            @else
                <span class="px-4 py-2 text-sm rounded-xl
                             bg-slate-200 dark:bg-slate-800
                             text-slate-400 cursor-not-allowed">
                    Next >
                </span>
            @endif
        </div>
    </div>

</div>

@endsection
