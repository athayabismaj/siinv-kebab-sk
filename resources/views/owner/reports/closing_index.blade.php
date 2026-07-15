@extends('layouts.app')

@section('content')
<div class="space-y-8 max-w-full overflow-x-hidden">

    <x-page-header 
        title="Tutup Buku" 
        subtitle="Kunci data transaksi bulan ini agar aman dari modifikasi dan tercatat permanen sebagai arsip. Pantau riwayat penutupan periode sebelumnya untuk mempermudah proses audit pembukuan." 
        breadcrumb-parent="Beranda" 
        breadcrumb-parent-url="{{ route('owner.panel') }}"
        breadcrumb-child="Tutup Buku">
        
        {{-- Indikator Status (Modern Pill Badge - Warnanya Dinamis) --}}
        <div class="inline-flex items-center gap-2.5 px-3 py-1.5 border rounded-lg shadow-sm transition-colors 
            {{ $isClosed ? 'bg-emerald-50/50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20' : ((!$usesBranchClosing || $branchId) ? 'bg-blue-50/50 dark:bg-blue-500/10 border-blue-100 dark:border-blue-500/20' : 'bg-amber-50/50 dark:bg-amber-500/10 border-amber-100 dark:border-amber-500/20') }}">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $isClosed ? 'bg-emerald-400' : ((!$usesBranchClosing || $branchId) ? 'bg-blue-400' : 'bg-amber-400') }}"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 {{ $isClosed ? 'bg-emerald-500' : ((!$usesBranchClosing || $branchId) ? 'bg-blue-500' : 'bg-amber-500') }}"></span>
            </span>
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wide {{ $isClosed ? 'text-emerald-700 dark:text-emerald-400' : ((!$usesBranchClosing || $branchId) ? 'text-blue-700 dark:text-blue-400' : 'text-amber-700 dark:text-amber-400') }}">
                Status {{ $thisMonth->translatedFormat('F') }}:
                <span class="ml-1 text-slate-700 dark:text-slate-200 normal-case tracking-normal">
                    @if($usesBranchClosing && $branchId)
                        {{ $selectedBranch->name ?? 'Cabang' }} - {{ $isClosed ? 'Sudah Ditutup' : 'Belum Ditutup' }}
                    @elseif(!$usesBranchClosing)
                        {{ $isClosed ? 'Sudah Ditutup' : 'Belum Ditutup' }}
                    @else
                        Pilih cabang terlebih dahulu
                    @endif
                </span>
            </span>
        </div>
    </x-page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        {{-- KIRI: Form Proses Tutup Buku (lg:col-span-1) --}}
        <div class="flex flex-col gap-5">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 {{ $isClosed ? 'bg-emerald-500' : 'bg-blue-500' }}"></div>
                
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $isClosed ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($isClosed)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            @endif
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Proses Tutup Buku</h2>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">Periode: {{ $thisMonth->translatedFormat('F Y') }}</p>
                    </div>
                </div>

                @if($usesBranchClosing && !$branchId)
                    <div class="text-center py-6">
                        <div class="w-14 h-14 bg-amber-50 dark:bg-amber-900/30 text-amber-500 dark:text-amber-400 rounded-full flex items-center justify-center mx-auto mb-3 ring-1 ring-amber-100 dark:ring-amber-800">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M6 21V7l6-4 6 4v14M9 21v-6h6v6"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-800 dark:text-white">Cabang belum dipilih</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Pilih cabang di menu atas terlebih dahulu agar penutupan data tidak tercatat untuk seluruh cabang.</p>
                    </div>
                @elseif($isClosed)
                    <div class="text-center py-10 px-4 bg-gradient-to-b from-emerald-50/50 to-transparent dark:from-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-800/30">
                        <div class="relative w-20 h-20 mx-auto mb-5">
                            <div class="absolute inset-0 bg-emerald-200/50 dark:bg-emerald-500/20 rounded-full animate-ping"></div>
                            <div class="relative w-full h-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center ring-4 ring-emerald-50 dark:ring-emerald-900/30 shadow-sm">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white tracking-tight mb-2">Buku Selesai Ditutup</h3>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 leading-relaxed max-w-[250px] mx-auto">
                            Seluruh data transaksi dan stok pada periode ini telah dikunci dan diarsipkan secara permanen.
                        </p>
                    </div>
                @else
                    <div class="space-y-5">
                        {{-- Estimasi Card --}}
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-black uppercase tracking-widest mb-3">Estimasi Penutupan:</p>
                            <div class="grid grid-cols-2 gap-4 divide-x divide-slate-200 dark:divide-slate-700">
                                <div>
                                    <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">Total Omzet</p>
                                    <p class="text-[13px] font-black text-slate-800 dark:text-white tracking-tight mt-0.5">Rp {{ number_format($preview['totalRevenue'], 0, ',', '.') }}</p>
                                </div>
                                <div class="pl-4">
                                    <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">Total Transaksi</p>
                                    <p class="text-[13px] font-black text-slate-800 dark:text-white tracking-tight mt-0.5">{{ number_format($preview['totalTransactions'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('owner.reports.closing.store') }}" method="POST" class="space-y-5">
                            @csrf
                            <input type="hidden" name="period_type" value="monthly">
                            <input type="hidden" name="period_date" value="{{ $thisMonth->toDateString() }}">
                            @if($usesBranchClosing)
                                <input type="hidden" name="branch_id" value="{{ $branchId }}">
                            @endif
                            
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Catatan Penutupan <span class="text-slate-400 font-medium">(Opsional)</span></label>
                                <textarea name="notes" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-[13px] font-medium text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all resize-none shadow-sm" placeholder="Contoh: Stok opname selesai dan sesuai..."></textarea>
                            </div>

                            <button type="submit"
                                    data-confirm
                                    data-confirm-message="Tutup buku {{ $thisMonth->translatedFormat('F Y') }}{{ $usesBranchClosing ? ' untuk cabang ' . ($selectedBranch->name ?? 'terpilih') : '' }} sekarang? Data yang sudah ditutup akan menjadi Snapshot permanen."
                                    class="w-full flex items-center justify-center gap-2 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-[13px] font-bold transition-all shadow-sm focus:outline-none focus:ring-4 focus:ring-blue-500/20">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Proses Tutup Buku
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Info Box --}}
            <div class="p-4 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100/50 dark:from-amber-900/20 dark:to-amber-900/10 border border-amber-200/60 dark:border-amber-500/20 shadow-sm relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-1/4 -translate-y-1/4">
                    <svg class="w-24 h-24 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="relative z-10">
                    <p class="text-[11px] font-black uppercase tracking-widest flex items-center gap-1.5 mb-2 text-amber-800 dark:text-amber-500">
                        Informasi Penting
                    </p>
                    <p class="text-[11px] leading-relaxed font-medium text-amber-700/80 dark:text-amber-400/80">
                        Tutup buku diproses secara spesifik per cabang. Pastikan Anda telah memilih cabang yang benar sebelum memproses penutupan data.
                    </p>
                </div>
            </div>
        </div>

        {{-- KANAN: Tabel Riwayat Penutupan (lg:col-span-2) --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm flex flex-col h-full overflow-hidden">
                
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white tracking-tight">Riwayat Penutupan</h3>
                        <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-0.5">Daftar periode yang telah dikunci dan diarsipkan.</p>
                    </div>
                </div>

                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-sm text-left">
                        <thead class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <tr>
                                <th class="px-6 py-4">Periode</th>
                                <th class="px-6 py-4">Cabang</th>
                                <th class="px-6 py-4">Tipe</th>
                                <th class="px-6 py-4 text-right">Omzet Final</th>
                                <th class="px-6 py-4 text-right">Tgl Penutupan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($closings as $closing)
                                @php
                                    $canCancelClosing = (bool) ($cancelableClosingIds[$closing->id] ?? false);
                                @endphp
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-slate-800 dark:text-white">
                                            {{ $closing->period_type === 'monthly' ? $closing->period_date->translatedFormat('F Y') : $closing->period_date->format('Y') }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 border border-slate-200/50 dark:border-slate-700/50">
                                                <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            </div>
                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                {{ $closing->branch->name ?? 'Cabang lama' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-md px-2.5 py-1.5 text-[10px] font-black uppercase tracking-widest shadow-sm
                                            {{ $closing->period_type === 'monthly' ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/50' : 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:border-purple-800/50' }}">
                                            {{ $closing->period_type === 'monthly' ? 'Bulanan' : 'Tahunan' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white tracking-tight">
                                        <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($closing->total_revenue, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ $closing->created_at->format('d M Y') }}</p>
                                        @if($canCancelClosing)
                                            <form action="{{ route('owner.reports.closing.cancel', $closing) }}" method="POST" class="mt-2" data-closing-cancel>
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="confirmation" value="">
                                                <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[9px] font-bold uppercase tracking-wider text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/20">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    Batalkan
                                                </button>
                                            </form>
                                        @else
                                            <span class="mt-2 inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[9px] font-bold uppercase tracking-wider text-slate-400 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-500">
                                                Terkunci
                                            </span>
                                        @endif
                                        <p class="text-[10px] font-medium text-slate-400 dark:text-slate-500 mt-0.5">{{ $closing->created_at->format('H:i') }} • {{ $closing->closedBy->name ?? 'Sistem' }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3 ring-1 ring-slate-100 dark:ring-slate-700">
                                                <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Belum ada riwayat tutup buku.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($closings->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                        {{ $closings->links() }}
                    </div>
                @endif
                
            </div>
        </div>
        
    </div>
</div>
@endsection
