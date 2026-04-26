@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">Setting Target Harian</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Tetapkan target omzet dan jumlah transaksi harian, lalu pantau realisasinya.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-200">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('owner.targets.index') }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Tanggal</label>
        <div class="flex gap-3">
            <input type="date" name="date" value="{{ $selectedDate->toDateString() }}"
                   class="w-full md:w-80 px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">Tampilkan</button>
        </div>
    </form>

    <form method="POST" action="{{ route('owner.targets.store') }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 space-y-4">
        @csrf
        <input type="hidden" name="target_date" value="{{ $selectedDate->toDateString() }}">

        <p class="text-xs text-slate-500 dark:text-slate-400">
            Target yang disimpan di tanggal ini akan menjadi target default dan tetap berlaku untuk hari berikutnya sampai Anda ubah lagi.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Target Omzet (Rp)</label>
                <input type="number" min="0" step="1000" name="target_revenue"
                       value="{{ old('target_revenue', (int) $targetRevenue) }}"
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Target Transaksi</label>
                <input type="number" min="0" name="target_transactions"
                       value="{{ old('target_transactions', $targetTransactions) }}"
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Catatan (opsional)</label>
            <textarea name="notes" rows="2"
                      class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm">{{ old('notes', $target->notes ?? '') }}</textarea>
        </div>

        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700">Simpan Target</button>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Target Omzet</p>
            @if($target)
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Berlaku sejak {{ $target->target_date->format('d/m/Y') }}</p>
            @endif
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">Rp {{ number_format($targetRevenue, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Realisasi Omzet</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">Rp {{ number_format($actualRevenue, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Target Transaksi</p>
            @if($target)
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Berlaku sejak {{ $target->target_date->format('d/m/Y') }}</p>
            @endif
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">{{ number_format($targetTransactions, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Realisasi Transaksi</p>
            <p class="mt-2 text-2xl font-semibold text-slate-800 dark:text-white">{{ number_format($actualTransactions, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
        <p class="text-sm text-slate-600 dark:text-slate-300">
            Selisih omzet:
            <span class="{{ $revenueGap >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-semibold">
                Rp {{ number_format($revenueGap, 0, ',', '.') }}
            </span>
            | Selisih transaksi:
            <span class="{{ $transactionGap >= 0 ? 'text-emerald-600' : 'text-red-600' }} font-semibold">
                {{ number_format($transactionGap, 0, ',', '.') }}
            </span>
        </p>
    </div>
</div>
@endsection
