<aside
    x-data="{
        openLaporan: {{ request()->routeIs('owner.reports.*') || request()->routeIs('owner.transactions.*') ? 'true' : 'false' }},
        openAnalisis: {{ request()->routeIs('owner.analytics.*') ? 'true' : 'false' }},
        openManajemen: {{ request()->routeIs('owner.users.*') ? 'true' : 'false' }}
    }"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed top-0 left-0 md:relative z-50 w-64 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-r border-slate-200/80 dark:border-slate-800/80 transform transition-transform duration-300 ease-in-out flex flex-col md:h-full"
    style="height: 100dvh;">

    {{-- BRAND HEADER --}}
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-blue-500/25">
                SK
            </div>
            <div>
                <h2 class="text-base font-semibold text-slate-800 dark:text-white leading-tight">Kebab SK</h2>
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Panel Owner</p>
            </div>
        </div>
        <button @click="sidebarOpen = false" class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-4 md:py-6 space-y-4 md:space-y-6 text-sm">

        {{-- BERANDA --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Beranda</p>
            <div class="space-y-1">
                <a href="{{ route('owner.panel') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.panel') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('owner.stocks.index') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.stocks.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span>Monitoring Stok</span>
                </a>
            </div>
        </div>

        {{-- PENJUALAN --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Penjualan</p>
            <div class="space-y-1">
                <a href="{{ route('owner.reports.sales') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.reports.sales') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path></svg>
                    <span>Laporan Penjualan</span>
                </a>
                <a href="{{ route('owner.transactions.index') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.transactions.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Riwayat Transaksi</span>
                </a>
                <a href="{{ route('owner.targets.index') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.targets.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Setting Target Harian</span>
                </a>
            </div>
        </div>

        {{-- ANALISIS --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Analisis</p>
            <div class="space-y-1">
                <a href="{{ route('owner.analytics.menu') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.analytics.menu') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Analisis Menu</span>
                </a>
                <a href="{{ route('owner.reports.usage') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.reports.usage') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Pemakaian Bahan</span>
                </a>
            </div>
        </div>

        {{-- KEUANGAN --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Keuangan</p>
            <div class="space-y-1">
                <a href="{{ route('owner.reports.closing.index') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.reports.closing.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <span>Tutup Buku</span>
                </a>
                <a href="{{ route('owner.reports.cashflow') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.reports.cashflow') || request()->routeIs('owner.reports.cashflow.export') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 2v10m0 0v2"></path></svg>
                    <span>Laporan Pengeluaran</span>
                </a>
            </div>
        </div>

        {{-- PENGGUNA --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Pengguna</p>
            <div class="space-y-1">
                <a href="{{ route('owner.users.index') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.users.index') || request()->routeIs('owner.users.create') || request()->routeIs('owner.users.edit') || request()->routeIs('owner.users.reset.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Daftar User</span>
                </a>
                <a href="{{ route('owner.users.archive') }}"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('owner.users.archive') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    <span>Arsip User</span>
                </a>
            </div>
        </div>

    </nav>
</aside>


