<x-page-header 
    title="Manajemen Menu" 
    subtitle="Kelola daftar menu utama restoran. Harga dan resep diatur di dalam masing-masing variant menu." 
    breadcrumb-parent="Menu & Resep" 
    breadcrumb-child="Manajemen Menu">
    
    <div class="flex items-center gap-2 shrink-0 lg:mt-6">
        <a href="{{ route('admin.menus.archive') }}" class="inline-flex items-center justify-center gap-1.5 px-4 h-10 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-[12px] font-bold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
            <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
            Arsip
        </a>
        <a href="{{ route('admin.menus.create') }}" class="inline-flex items-center justify-center gap-1.5 px-5 h-10 bg-slate-900 dark:bg-blue-600 text-white text-[12px] font-bold rounded-xl hover:bg-slate-800 dark:hover:bg-blue-500 transition-all shadow-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            Tambah Menu
        </a>
    </div>
</x-page-header>
