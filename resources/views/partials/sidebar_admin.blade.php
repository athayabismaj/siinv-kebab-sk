<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed top-0 left-0 md:relative z-50 w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transform transition-transform duration-300 ease-in-out flex flex-col md:h-full"
    style="height: 100dvh;">

    {{-- BRAND HEADER --}}
    <div class="h-16 flex flex-col justify-center px-6 border-b border-slate-200 dark:border-slate-800">
        <h2 class="text-base font-semibold text-slate-800 dark:text-white">Kebab SK</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">Panel Admin</p>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-4 md:py-6 space-y-4 md:space-y-6 text-sm">

        {{-- 1. BERANDA --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Beranda</p>
            <div class="space-y-1">
                <a href="{{ route('admin.panel') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.panel') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        {{-- 2. INVENTORI --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Inventori</p>
            <div class="space-y-1">
                <a href="{{ route('admin.ingredient-categories.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.ingredient-categories.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    <span>Kategori Bahan</span>
                </a>
                <a href="{{ route('admin.ingredients.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.ingredients.index') || request()->routeIs('admin.ingredients.create') || request()->routeIs('admin.ingredients.edit') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span>Manajemen Bahan</span>
                </a>
                <a href="{{ route('admin.stocks.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.stocks.index') || request()->routeIs('admin.stocks.restock.*') || request()->routeIs('admin.stocks.adjust.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span>Restok & Penyesuaian</span>
                </a>
                <a href="{{ route('admin.stocks.logs') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.stocks.logs') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <span>Riwayat Stok</span>
                </a>
                <a href="{{ route('admin.ingredients.archive') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.ingredients.archive') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    <span>Arsip Bahan</span>
                </a>
            </div>
        </div>

        {{-- 3. MENU & RESEP --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Menu & Resep</p>
            <div class="space-y-1">
                <a href="{{ route('admin.menu-categories.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.menu-categories.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    <span>Kategori Menu</span>
                </a>
                <a href="{{ route('admin.menus.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.menus.index') || request()->routeIs('admin.menus.create') || request()->routeIs('admin.menus.edit') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span>Manajemen Menu</span>
                </a>
                <a href="{{ route('admin.recipes.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.recipes.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>Manajemen Resep</span>
                </a>
                <a href="{{ route('admin.menus.archive') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.menus.archive') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    <span>Arsip Menu</span>
                </a>
            </div>
        </div>

        {{-- 4. OPERASIONAL KASIR --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Kasir</p>
            <div class="space-y-1">
                <a href="{{ route('admin.transactions.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.transactions.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Monitoring Transaksi</span>
                </a>
            </div>
        </div>

        {{-- 5. PELAPORAN --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Pelaporan</p>
            <div class="space-y-1">
                <a href="{{ route('admin.reports.usage') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.reports.usage') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Laporan Pemakaian</span>
                </a>
                <a href="{{ route('admin.reports.cashflow') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('admin.reports.cashflow') || request()->routeIs('admin.reports.cashflow.export') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 2v10m0 0v2"></path></svg>
                    <span>Laporan Pengeluaran</span>
                </a>
            </div>
        </div>

    </nav>
</aside>
