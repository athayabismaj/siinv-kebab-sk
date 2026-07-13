@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Tutup Sesi Stok Harian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Tutup Sesi Harian Kasir" 
        subtitle="Silakan lakukan opname fisik dan masukkan jumlah aktual bahan yang tersisa di outlet sebelum menutup sesi." 
        breadcrumb-parent="Kasir & Stok" 
        breadcrumb-child="Tutup Sesi">
        
        <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}"
           class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-[13px] font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all shadow-sm">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Sesi
        </a>
    </x-page-header>

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

    {{-- ================= SUMMARY CARDS (Per-Unit) ================= --}}
    {{-- Tailwind safelist: md:grid-cols-3 md:grid-cols-4 md:grid-cols-5 md:grid-cols-6 --}}
    @php $unitColors = ['emerald', 'amber', 'violet', 'cyan', 'rose']; @endphp
    <div class="grid grid-cols-2 md:grid-cols-{{ min(count($summary['by_unit']) + 1, 5) }} gap-4">
        {{-- Card: Total Bahan --}}
        <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Bahan</p>
            <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ $summary['items_count'] }}</span>
                <span class="text-[10px] font-medium text-slate-400">item</span>
            </div>
            <div class="absolute bottom-0 left-0 h-1 w-full bg-slate-500/20"></div>
        </div>

        {{-- Per-Unit Cards --}}
        @foreach($summary['by_unit'] as $idx => $unitData)
            @php $color = $unitColors[$idx % count($unitColors)]; @endphp
            <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-{{ $color }}-500 uppercase tracking-widest mb-2">
                    Stok {{ $unitData['unit'] }}
                </p>
                <div class="space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Dibawa</span>
                        <span class="text-[13px] font-extrabold text-slate-800 dark:text-white tabular-nums">
                            {{ rtrim(rtrim(number_format($unitData['opening'], 2, '.', ''), '0'), '.') }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Sisa</span>
                        <span class="text-[13px] font-extrabold text-amber-600 dark:text-amber-400 tabular-nums" id="summary-remaining-{{ $idx }}">
                            {{ rtrim(rtrim(number_format($unitData['remaining'], 2, '.', ''), '0'), '.') }}
                        </span>
                    </div>
                    <div class="h-px bg-slate-100 dark:bg-slate-800"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-bold text-orange-500 uppercase tracking-wider">Terpakai</span>
                        <span class="text-[14px] font-black text-orange-600 dark:text-orange-400 tabular-nums" id="summary-used-{{ $idx }}">
                            {{ rtrim(rtrim(number_format($unitData['used'], 2, '.', ''), '0'), '.') }}
                        </span>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-{{ $color }}-500/30"></div>
            </div>
        @endforeach
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
                <p class="text-[12px] text-slate-600 dark:text-slate-400 mt-1 font-medium">Nilai sisa terakhir sudah terisi otomatis. Sesuaikan dengan stok fisik sebelum sesi ditutup.</p>
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

    // Baca mapping unit dari PHP (by_unit array)
    const unitMap = @json(collect($summary['by_unit'])->pluck('unit')->values());

    const toNumber = (v) => {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : 0;
    };

    const toBase = (displayQty, unit) => {
        if (unit === 'kg' || unit === 'l') return displayQty * 1000;
        return displayQty;
    };

    const fmt = (n) => {
        // Format tanpa trailing zeros
        let s = n.toFixed(2);
        s = s.replace(/0+$/, '').replace(/\.$/, '');
        return s || '0';
    };

    const recompute = () => {
        // Group by unit
        const groups = {};
        unitMap.forEach((unit, idx) => {
            groups[unit] = { opening: 0, remaining: 0 };
        });

        inputs.forEach((input) => {
            const openingBase = toNumber(input.dataset.openingBase);
            const unit = (input.dataset.displayUnit || 'unit').toUpperCase();

            const maxDisplay = toNumber(input.getAttribute('max'));
            let currentDisplay = toNumber(input.value);

            if (currentDisplay > maxDisplay) {
                input.value = maxDisplay;
                currentDisplay = maxDisplay;
            } else if (currentDisplay < 0) {
                input.value = 0;
                currentDisplay = 0;
            }

            const remainingBase = toBase(currentDisplay, (input.dataset.displayUnit || '').toLowerCase());

            if (!groups[unit]) {
                groups[unit] = { opening: 0, remaining: 0 };
            }
            groups[unit].opening += openingBase;
            groups[unit].remaining += remainingBase;
        });

        // Update each unit card
        unitMap.forEach((unit, idx) => {
            const group = groups[unit];
            if (!group) return;

            const used = Math.max(0, group.opening - group.remaining);
            // Convert base back to display for the card
            const isConvertUnit = unit === 'KG' || unit === 'L';
            const dispRemaining = isConvertUnit ? group.remaining / 1000 : group.remaining;
            const dispUsed = isConvertUnit ? used / 1000 : used;

            const remainingEl = document.getElementById('summary-remaining-' + idx);
            const usedEl = document.getElementById('summary-used-' + idx);
            if (remainingEl) remainingEl.textContent = fmt(dispRemaining);
            if (usedEl) usedEl.textContent = fmt(dispUsed);
        });
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
