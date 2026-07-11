@extends('layouts.app')

@section('content')
<div class="space-y-8 max-w-full overflow-x-hidden">

    <div class="mb-8">
        
        {{-- Breadcrumb --}}
        <nav class="mb-3 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 sm:text-[11px]">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Tutup Buku</span>
        </nav>

        {{-- Judul --}}
        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white mb-2">
            Tutup Buku
        </h1>

        {{-- Deskripsi Halaman --}}
        <p class="text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400 max-w-3xl mb-5">
            Kunci data transaksi bulan ini agar aman dari modifikasi dan tercatat permanen sebagai arsip.<br class="hidden sm:block mt-1">
            Pantau riwayat penutupan periode sebelumnya untuk mempermudah proses audit pembukuan.
        </p>

        {{-- Indikator Status (Modern Pill Badge - Warnanya Dinamis) --}}
        <div class="inline-flex items-center gap-2.5 px-3 py-1.5 border rounded-lg shadow-sm transition-colors 
            {{ $isClosed ? 'bg-emerald-50/50 dark:bg-emerald-500/10 border-emerald-100 dark:border-emerald-500/20' : 'bg-blue-50/50 dark:bg-blue-500/10 border-blue-100 dark:border-blue-500/20' }}">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $isClosed ? 'bg-emerald-400' : 'bg-blue-400' }}"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 {{ $isClosed ? 'bg-emerald-500' : 'bg-blue-500' }}"></span>
            </span>
            <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wide {{ $isClosed ? 'text-emerald-700 dark:text-emerald-400' : 'text-blue-700 dark:text-blue-400' }}">
                Status {{ $thisMonth->translatedFormat('F') }}:
                <span class="ml-1 text-slate-700 dark:text-slate-200 normal-case tracking-normal">
                    {{ $isClosed ? 'Sudah Ditutup' : 'Belum Ditutup' }}
                </span>
            </span>
        </div>
        
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-1 space-y-6">
            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-5 flex items-center gap-2">
                    <span class="w-4 h-1 {{ $isClosed ? 'bg-emerald-500' : 'bg-blue-500' }} rounded-full"></span>
                    Tutup Buku Bulan Ini
                </p>
                
                @if($isClosed)
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-500 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4 ring-1 ring-emerald-100 dark:ring-emerald-800">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-800 dark:text-white">Bulan ini sudah ditutup.</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Data telah diarsipkan secara permanen.</p>
                    </div>
                @else
                    <div class="space-y-5">
                        {{-- Estimasi Card --}}
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-black uppercase tracking-widest mb-3">Estimasi Penutupan:</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Total Omzet</p>
                                    <p class="text-sm font-black text-slate-800 dark:text-white tracking-tight">Rp {{ number_format($preview['totalRevenue'], 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Total Transaksi</p>
                                    <p class="text-sm font-black text-slate-800 dark:text-white tracking-tight">{{ number_format($preview['totalTransactions'], 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('owner.reports.closing.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="period_type" value="monthly">
                            <input type="hidden" name="period_date" value="{{ $thisMonth->toDateString() }}">
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-1.5">Catatan (Opsional)</label>
                                <textarea name="notes" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all resize-none" placeholder="Contoh: Stok opname selesai dan sesuai..."></textarea>
                            </div>

                            <button type="submit" 
                                    onclick="return confirm('Tutup buku {{ $thisMonth->translatedFormat('F Y') }} sekarang? Data yang sudah ditutup akan menjadi Snapshot permanen.')"
                                    class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white text-sm font-bold transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Proses Tutup Buku
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Info Box --}}
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-amber-800 dark:text-amber-400 shadow-sm">
                <p class="text-xs font-black uppercase tracking-widest flex items-center gap-1.5 mb-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Informasi Penting
                </p>
                <p class="text-[11px] leading-relaxed font-medium">
                    Tutup buku sebaiknya dilakukan setiap akhir bulan untuk mengunci data keuangan dan memudahkan proses audit di kemudian hari.
                </p>
            </div>
        </div>

        {{-- Perbaikan: Tambahkan self-start agar tingginya nge-pas dengan data --}}
        <div class="lg:col-span-2 self-start">
            {{-- Perbaikan: Hapus h-full dan flex-col --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                
                <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 flex items-center gap-2">
                        <span class="w-4 h-1 bg-violet-500 rounded-full"></span>
                        Riwayat Penutupan Periode
                    </p>
                </div>

                {{-- Perbaikan: Hapus flex-1 --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        {{-- Perbaikan Kontras: Gunakan bg-slate-800 solid --}}
                        <thead class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                            <tr>
                                <th class="px-6 py-4">Periode</th>
                                <th class="px-6 py-4">Tipe</th>
                                <th class="px-6 py-4 text-right">Omzet Final</th>
                                <th class="px-6 py-4">Oleh</th>
                                <th class="px-6 py-4">Tanggal Penutupan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($closings as $closing)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-800 dark:text-white">
                                        {{ $closing->period_type === 'monthly' ? $closing->period_date->translatedFormat('F Y') : $closing->period_date->format('Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest
                                            {{ $closing->period_type === 'monthly' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' }}">
                                            {{ $closing->period_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                        <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($closing->total_revenue, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs font-semibold">
                                        {{ $closing->closedBy->name ?? 'System' }}
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs tabular-nums">
                                        {{ $closing->created_at->format('d M Y, H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3">
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
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                        {{ $closings->links() }}
                    </div>
                @endif
                
            </div>
        </div>
        
    </div>
</div>
@endsection
