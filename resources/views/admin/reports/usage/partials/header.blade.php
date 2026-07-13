<x-page-header 
    title="Laporan Pemakaian Bahan" 
    subtitle="Pantau total bahan baku yang terpakai berdasarkan periode harian, mingguan, atau bulanan. Data pemakaian bahan dihitung berdasarkan resep menu dari transaksi penjualan yang berhasil pada periode yang dipilih." 
    breadcrumb-parent="Pelaporan" 
    breadcrumb-child="Laporan Pemakaian Bahan">
    
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 shrink-0 mt-2 lg:mt-0">
        <div class="inline-flex w-full sm:w-auto items-center justify-center sm:justify-start gap-2 rounded-full bg-blue-50 border border-blue-100/50 px-3 py-1.5 dark:bg-blue-500/10 dark:border-blue-800/30 shadow-sm">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            <span class="text-[11px] font-bold tracking-wide text-blue-700 dark:text-blue-400 uppercase">
                PERIODE DATA:
                <span class="font-medium text-slate-700 dark:text-slate-300 ml-1 normal-case">
                    @if($dateFrom === $dateTo)
                        {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y') }}
                    @endif
                </span>
            </span>
        </div>

        <a href="{{ route($stockRoute) }}"
           class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-4 py-1.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-full hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all shadow-sm">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            {{ $isOwner ? 'Monitoring Stok' : 'Riwayat Stok' }}
        </a>
    </div>
</x-page-header>
