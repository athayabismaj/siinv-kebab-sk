<x-page-header 
    title="Restok dan Penyesuaian" 
    subtitle="Kelola penambahan stok baru dan lakukan penyesuaian (adjustment) stok fisik bahan baku secara manual." 
    breadcrumb-parent="Inventori" 
    breadcrumb-child="Restok & Penyesuaian">
    
    <div class="shrink-0 mt-2 lg:mt-0">
        <a href="{{ route('admin.stocks.logs') }}"
           class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-[13px] font-semibold rounded-xl hover:bg-slate-800 transition-all shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Riwayat Stok
        </a>
    </div>
</x-page-header>
