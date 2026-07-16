<x-page-header
    title="Arsip Menu"
    subtitle="Daftar menu yang telah dinonaktifkan. Menu di arsip tidak muncul di kasir, namun tetap bisa dipulihkan."
    breadcrumb-parent="Manajemen Menu"
    breadcrumb-child="Arsip Menu">

    <a href="{{ route('admin.menus.index') }}" class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-[13px] font-black text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-500/10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 sm:w-auto">
        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>
</x-page-header>
