@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')
<div class="max-w-3xl space-y-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">Input Pengeluaran</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Catat biaya operasional. Pemasukan tetap diambil otomatis dari transaksi menu.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>- {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="POST" action="{{ route('admin.reports.cashflow.store') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @csrf

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Tanggal</label>
                <input type="date"
                       name="entry_date"
                       value="{{ $entryDate }}"
                       min="{{ $entryDate }}"
                       max="{{ $entryDate }}"
                       readonly
                       class="w-full cursor-not-allowed rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                <p class="mt-1 text-xs text-slate-400">Tanggal dikunci ke hari ini untuk mencegah salah input periode.</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Nominal</label>
                <input type="number" name="amount" min="1" step="0.01" value="{{ old('amount') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" placeholder="Contoh: 120000" required>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Kategori Pengeluaran</label>
                <input type="text" name="source" value="{{ old('source') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" placeholder="Contoh: Gas, Transport, Listrik" required>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600 dark:text-slate-300">Catatan</label>
                <input type="text" name="note" value="{{ old('note') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" placeholder="Opsional">
            </div>

            <div class="md:col-span-2 flex items-center justify-between pt-2">
                <a href="{{ route('admin.reports.cashflow') }}" class="text-sm text-slate-500 hover:text-blue-600">&larr; Kembali ke Laporan</a>
                <button type="submit" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Simpan Pengeluaran</button>
            </div>
        </form>
    </div>
</div>
@endsection
