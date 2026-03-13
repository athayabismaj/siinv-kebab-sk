<aside
    x-data="{
        openLaporan: {{ request()->routeIs('owner.reports.*') || request()->routeIs('owner.transactions.*') ? 'true' : 'false' }},
        openAnalisis: {{ request()->routeIs('owner.analytics.*') ? 'true' : 'false' }},
        openManajemen: {{ request()->routeIs('owner.users.*') ? 'true' : 'false' }}
    }"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed top-0 left-0 md:relative z-50 w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 transform transition-transform duration-300 ease-in-out flex flex-col min-h-screen md:min-h-full">

    <div class="h-16 flex flex-col justify-center px-6 border-b border-slate-200 dark:border-slate-800">
        <h2 class="text-base font-semibold text-slate-800 dark:text-white">Kebab SK</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">Panel Owner</p>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-5 text-sm">
        <a href="{{ route('owner.panel') }}"
           @click="sidebarOpen = false"
           class="block px-4 py-3 rounded-xl transition font-medium {{ request()->routeIs('owner.panel') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            Dashboard
        </a>

        <a href="{{ route('owner.stocks.index') }}"
           @click="sidebarOpen = false"
           class="block px-4 py-3 rounded-xl transition font-medium {{ request()->routeIs('owner.stocks.*') ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            Monitoring Stok
        </a>

        <div>
            <button @click="openLaporan = !openLaporan"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Laporan</span>
                <span :class="openLaporan ? 'rotate-180' : ''" class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openLaporan" x-collapse x-cloak class="mt-2 space-y-2">
                <div class="px-2">
                    <p class="px-2 py-1 text-[11px] uppercase tracking-wider text-slate-400">Laporan Penjualan</p>
                    <div class="space-y-1 mt-1">
                        <a href="{{ route('owner.reports.sales.daily') }}"
                           @click="sidebarOpen = false"
                           class="block px-4 py-2 rounded-lg transition {{ request()->routeIs('owner.reports.sales') || request()->routeIs('owner.reports.sales.daily') ? 'bg-blue-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            Harian
                        </a>
                        <a href="{{ route('owner.reports.sales.monthly') }}"
                           @click="sidebarOpen = false"
                           class="block px-4 py-2 rounded-lg transition {{ request()->routeIs('owner.reports.sales.monthly') ? 'bg-blue-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                            Bulanan
                        </a>
                    </div>
                </div>

                <a href="{{ route('owner.transactions.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition {{ request()->routeIs('owner.transactions.*') ? 'bg-blue-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Riwayat Transaksi
                </a>

                <a href="{{ route('owner.reports.usage') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition {{ request()->routeIs('owner.reports.usage') ? 'bg-blue-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Laporan Pemakaian
                </a>
            </div>
        </div>

        <div>
            <button @click="openAnalisis = !openAnalisis"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Analisis</span>
                <span :class="openAnalisis ? 'rotate-180' : ''" class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openAnalisis" x-collapse x-cloak class="mt-2 space-y-1">
                <a href="{{ route('owner.analytics.menu') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition {{ request()->routeIs('owner.analytics.menu') ? 'bg-blue-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Analisis Menu
                </a>
            </div>
        </div>

        <div>
            <button @click="openManajemen = !openManajemen"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Manajemen</span>
                <span :class="openManajemen ? 'rotate-180' : ''" class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openManajemen" x-collapse x-cloak class="mt-2 space-y-1">
                <a href="{{ route('owner.users.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition {{ request()->routeIs('owner.users.index') || request()->routeIs('owner.users.create') || request()->routeIs('owner.users.edit') || request()->routeIs('owner.users.reset.*') ? 'bg-blue-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Daftar User
                </a>

                <a href="{{ route('owner.users.archive') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition {{ request()->routeIs('owner.users.archive') ? 'bg-blue-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Arsip User
                </a>
            </div>
        </div>
    </nav>
</aside>

