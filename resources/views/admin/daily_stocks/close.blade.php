@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Tutup Sesi Stok Harian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex-1 w-full overflow-hidden">
            
            {{-- BREADCRUMB --}}
            <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto pb-1">
                <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">Kasir & Stok</span>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Stok Harian</a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">Tutup Sesi</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Tutup Sesi Harian Kasir
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Silakan lakukan opname fisik dan masukkan jumlah aktual bahan yang tersisa di outlet sebelum menutup sesi.
            </p>
        </div>

        <div class="shrink-0 mt-2 sm:mt-0">
            <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
               class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-[13px] font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Sesi
            </a>
        </div>
    </div>

    {{-- ================= INFO SESI (BANNER) ================= --}}
    <div class="flex items-center justify-between p-4 md:p-5 bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/50 rounded-2xl shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-md shadow-blue-500/30">
                {{ strtoupper(substr($session->cashier->name ?? 'U', 0, 1)) }}
            </div>
            <div>
                <p class="text-[11px] font-bold text-blue-500 dark:text-blue-400 uppercase tracking-widest mb-0.5">Sesi Akan Ditutup #{{ $session->id }}</p>
                <p class="font-bold text-slate-800 dark:text-slate-200 text-[15px]">
                    {{ $session->cashier->name ?? 'User Tidak Diketahui' }} 
                    <span class="font-normal text-slate-500 mx-1.5">&bull;</span> 
                    <span class="font-medium text-slate-600 dark:text-slate-400">{{ $session->session_date->translatedFormat('d F Y') }}</span>
                </p>
            </div>
        </div>
    </div>

    {{-- ================= SUMMARY CARDS ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Bahan</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ $summary['items_count'] }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-slate-500/20"></div>
        </div>

        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Dibawa (Base)</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ number_format($summary['total_opening'], 2, ',', '.') }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-emerald-500/30"></div>
        </div>

        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Sisa (Base)</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums" id="summary-remaining">{{ number_format($summary['total_remaining'], 2, ',', '.') }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-amber-500/30"></div>
        </div>

        <div class="relative overflow-hidden p-5 bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/50 rounded-2xl shadow-sm">
            <p class="text-[10px] font-bold text-rose-500 dark:text-rose-400 uppercase tracking-widest mb-1.5">Total Terpakai (Base)</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-rose-600 dark:text-rose-400 tabular-nums" id="summary-used">{{ number_format($summary['total_used'], 2, ',', '.') }}</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-rose-500/50"></div>
        </div>
    </div>

    {{-- ================= FORM GRID INPUT PENUTUPAN SESI ================= --}}
    <form method="POST" action="{{ route('admin.daily-stocks.close') }}" class="relative bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-md overflow-hidden">
        @csrf
        <input type="hidden" name="session_id" value="{{ $session->id }}">

        {{-- HEADER FORM (Peringatan & Judul) --}}
        <div class="px-6 py-5 border-b border-amber-200/60 dark:border-slate-800 bg-amber-50/50 dark:bg-slate-800/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-[13px] font-bold text-amber-700 dark:text-amber-500 uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Input Sisa Fisik Bahan
                </h2>
                <p class="text-[12px] text-slate-600 dark:text-slate-400 mt-1 font-medium">Bahan yang tidak diinput otomatis dianggap sisa = <strong class="text-slate-800 dark:text-slate-200">0</strong> (habis terpakai seluruhnya).</p>
            </div>
        </div>

        <div class="p-6">
            {{-- GRID ITEM BAHAN --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($session->items as $item)
                    @php
                        $openingQty = rtrim(rtrim(number_format((float) $item->opening_display, 2, '.', ''), '0'), '.');
                        $currentRemaining = rtrim(rtrim(number_format((float) $item->remaining_display, 2, '.', ''), '0'), '.');
                    @endphp
                    <div class="bg-slate-50/50 dark:bg-slate-800/30 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10 transition-all flex flex-col justify-between group relative overflow-hidden">
                        
                        {{-- Nama Bahan & Dibawa --}}
                        <div class="mb-5">
                            <p class="text-[15px] font-bold text-slate-800 dark:text-white leading-tight mb-2">{{ $item->ingredient->name }}</p>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-200/50 dark:bg-slate-900/50 rounded-md border border-slate-200/80 dark:border-slate-700">
                                <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Dibawa:</span>
                                <span class="text-[13px] font-bold text-slate-800 dark:text-slate-200">{{ $openingQty }}</span>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ $item->display_unit }}</span>
                            </div>
                        </div>
                        
                        {{-- Input Sisa Fisik --}}
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest mb-2">
                                Sisa Akhir Fisik
                            </label>
                            <div class="relative">
                                <input
                                    type="number"
                                    name="remaining[{{ $item->ingredient_id }}]"
                                    min="0"
                                    max="{{ $openingQty }}"
                                    step="0.01"
                                    value="{{ $currentRemaining }}"
                                    data-opening-base="{{ $item->opening_qty }}"
                                    data-display-unit="{{ $item->display_unit }}"
                                    data-pack-size="{{ $item->pack_size ?? 1 }}"
                                    placeholder="0.00"
                                    class="daily-remaining-input w-full h-[52px] pl-4 pr-16 rounded-xl border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-[16px] font-bold text-slate-900 dark:text-white outline-none tabular-nums focus:bg-white dark:focus:bg-slate-800 focus:border-blue-500 transition-all shadow-sm"
                                >
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none border-l border-slate-200 dark:border-slate-700 ml-2 pl-3">
                                    <span class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ $item->display_unit }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Dekorasi Garis Aktif --}}
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-500 opacity-0 group-focus-within:opacity-100 transition-opacity"></div>
                    </div>
                @endforeach
            </div>

            {{-- CATATAN PENUTUPAN (Dirombak menjadi TEXTAREA yang proporsional) --}}
            <div class="mt-8 pt-8 border-t border-slate-200 dark:border-slate-800">
                <div class="w-full">
                    <label class="block text-[12px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Catatan Penutupan Sesi (Opsional)
                    </label>
                    <textarea name="notes" 
                              rows="3"
                              maxlength="255" 
                              placeholder="Tambahkan keterangan seperti 'Ada bahan yang tumpah', 'Penyesuaian stok dengan fisik'..."
                              class="w-full p-4 rounded-xl border-2 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-[14px] font-medium text-slate-700 dark:text-slate-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all resize-none shadow-sm"></textarea>
                </div>
            </div>
        </div>

        {{-- ================= ACTION FOOTER (Lebih besar dan menonjol) ================= --}}
        <div class="px-6 py-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
            <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
               class="w-full sm:w-auto h-[48px] inline-flex items-center justify-center rounded-xl border-2 border-slate-300 dark:border-slate-600 px-8 text-[14px] font-bold text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700 transition-all">
                Batal
            </a>
            <button type="submit" class="w-full sm:w-auto h-[48px] min-w-[240px] flex items-center justify-center gap-2 rounded-xl bg-blue-600 text-white text-[15px] font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/30">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                Konfirmasi Tutup Sesi
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputs = document.querySelectorAll('.daily-remaining-input');
    if (!inputs.length) return;

    const remainingEl = document.getElementById('summary-remaining');
    const usedEl = document.getElementById('summary-used');

    const toNumber = (v) => {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : 0;
    };

    const toBase = (displayQty, unit, packSize) => {
        if (unit === 'kg' || unit === 'l') return displayQty * 1000;
        if (unit === 'pcs') return displayQty;
        return displayQty;
    };

    const fmt = (n) => {
        return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const recompute = () => {
        let totalOpening = 0;
        let totalRemaining = 0;

        inputs.forEach((input) => {
            const openingBase = toNumber(input.dataset.openingBase);
            const unit = (input.dataset.displayUnit || '').toLowerCase();
            const packSize = parseInt(input.dataset.packSize || '1', 10);
            
            const maxDisplay = toNumber(input.getAttribute('max'));
            let currentDisplay = toNumber(input.value);
            
            if (currentDisplay > maxDisplay) {
                input.value = maxDisplay;
                currentDisplay = maxDisplay;
            } else if (currentDisplay < 0) {
                input.value = 0;
                currentDisplay = 0;
            }

            const remainingBase = toBase(currentDisplay, unit, packSize);

            totalOpening += openingBase;
            totalRemaining += remainingBase;
        });

        const totalUsed = Math.max(0, totalOpening - totalRemaining);

        if (remainingEl) remainingEl.textContent = fmt(totalRemaining);
        if (usedEl) usedEl.textContent = fmt(totalUsed);
    };

    inputs.forEach((input) => {
        input.addEventListener('input', recompute);
        input.addEventListener('blur', function() {
            if (this.value === '') this.value = '0';
            recompute();
        });
    });
    
    recompute(); 
});
</script>
@endpush
