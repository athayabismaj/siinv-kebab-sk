<aside
    x-data="{
        openUser: {{ request()->routeIs('owner.users.*') ? 'true' : 'false' }},
        openMonitoring: false,
        openLaporan: false
    }"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed md:relative z-50
           w-64
           bg-white dark:bg-slate-900
           border-r border-slate-200 dark:border-slate-800
           transform transition-transform duration-300 ease-in-out
           flex flex-col
           min-h-screen md:min-h-full">

    {{-- HEADER --}}
    <div class="h-16 flex flex-col justify-center px-6
                border-b border-slate-200 dark:border-slate-800">
        <h2 class="text-base font-semibold text-slate-800 dark:text-white">
            Kebab SK
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Panel Owner
        </p>
    </div>

    {{-- NAVIGATION --}}
    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-6 text-sm">

        {{-- DASHBOARD --}}
        <a href="{{ route('owner.panel') }}"
           @click="sidebarOpen = false"
           class="block px-4 py-3 rounded-xl transition font-medium
           {{ request()->routeIs('owner.panel')
                ? 'bg-blue-600 text-white shadow-sm'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            Dashboard
        </a>

        {{-- ================= USER MANAGEMENT ================= --}}
        <div>
            <button @click="openUser = !openUser"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider
                           text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>User Management</span>
                <span :class="openUser ? 'rotate-180' : ''"
                      class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openUser"
                 x-collapse
                 x-cloak
                 class="mt-2 space-y-1">

                <a href="{{ route('owner.users.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('owner.users.index') || request()->routeIs('owner.users.create') || request()->routeIs('owner.users.edit') || request()->routeIs('owner.users.reset.*')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Kelola User
                </a>

                <a href="{{ route('owner.users.archive') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('owner.users.archive')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Arsip User
                </a>
            </div>
        </div>

        {{-- ================= MONITORING ================= --}}
        <div>
            <button @click="openMonitoring = !openMonitoring"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider
                           text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Monitoring</span>
                <span :class="openMonitoring ? 'rotate-180' : ''"
                      class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openMonitoring"
                 x-collapse
                 x-cloak
                 class="mt-2 space-y-1">

                <a href="{{ route('owner.ingredients.archive') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('owner.ingredients.archive')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Arsip Bahan
                </a>

                <a href="#"
                   @click.prevent
                   class="block px-4 py-2 rounded-lg
                          text-slate-400 dark:text-slate-500
                          cursor-not-allowed">
                    Monitoring Stok (Segera)
                </a>
            </div>
        </div>

        {{-- ================= LAPORAN ================= --}}
        <div>
            <button @click="openLaporan = !openLaporan"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider
                           text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Laporan</span>
                <span :class="openLaporan ? 'rotate-180' : ''"
                      class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openLaporan"
                 x-collapse
                 x-cloak
                 class="mt-2 space-y-1">
                <a href="#"
                   @click.prevent
                   class="block px-4 py-2 rounded-lg
                          text-slate-400 dark:text-slate-500
                          cursor-not-allowed">
                    Laporan Penjualan (Segera)
                </a>
            </div>
        </div>
    </nav>

    {{-- FOOTER --}}
    <div class="h-14 px-6 flex items-center border-t border-slate-200 dark:border-slate-800">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button class="w-full bg-red-500 hover:bg-red-600 text-white text-sm py-2 rounded-lg transition">
                Logout
            </button>
        </form>
    </div>
</aside>
