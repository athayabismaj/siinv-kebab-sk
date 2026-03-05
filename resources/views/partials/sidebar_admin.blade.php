<aside
    x-data="{
        openInventori: {{ request()->routeIs('admin.ingredients.*') || request()->routeIs('admin.ingredient-categories.*') || request()->routeIs('admin.stocks.*') ? 'true' : 'false' }},
        openMenu: {{ request()->routeIs('admin.menus.*') || request()->routeIs('admin.menu-categories.*') || request()->routeIs('admin.recipes.*') || request()->routeIs('admin.menu-variants.*') ? 'true' : 'false' }},
        openKasir: false,
        openLaporan: {{ request()->routeIs('admin.stocks.logs') ? 'true' : 'false' }}
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
            Panel Admin
        </p>
    </div>

    {{-- NAVIGATION --}}
    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-6 text-sm">

        {{-- 1. DASHBOARD --}}
        <a href="{{ route('admin.panel') }}"
           @click="sidebarOpen = false"
           class="block px-4 py-3 rounded-xl transition font-medium
           {{ request()->routeIs('admin.panel')
                ? 'bg-blue-600 text-white shadow-sm'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            Dashboard
        </a>

        {{-- 2. INVENTORI --}}
        <div>
            <button @click="openInventori = !openInventori"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider
                           text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Inventori</span>
                <span :class="openInventori ? 'rotate-180' : ''"
                      class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openInventori" x-collapse x-cloak class="mt-2 space-y-1">
                <a href="{{ route('admin.ingredient-categories.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.ingredient-categories.*')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Kategori Bahan
                </a>

                <a href="{{ route('admin.ingredients.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.ingredients.index')
                        || request()->routeIs('admin.ingredients.create')
                        || request()->routeIs('admin.ingredients.edit')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Manajemen Bahan
                </a>

                <a href="{{ route('admin.stocks.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.stocks.index') || request()->routeIs('admin.stocks.restock.*') || request()->routeIs('admin.stocks.adjust.*')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Restok & Penyesuaian
                </a>

                <a href="{{ route('admin.stocks.logs') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.stocks.logs')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Riwayat Stok
                </a>

                <a href="{{ route('admin.ingredients.archive') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.ingredients.archive')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Arsip Bahan
                </a>
            </div>
        </div>

        {{-- 3. MENU & RESEP --}}
        <div>
            <button @click="openMenu = !openMenu"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider
                           text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Menu & Resep</span>
                <span :class="openMenu ? 'rotate-180' : ''"
                      class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openMenu" x-collapse x-cloak class="mt-2 space-y-1">
                <a href="{{ route('admin.menu-categories.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.menu-categories.*')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Kategori Menu
                </a>

                <a href="{{ route('admin.menus.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.menus.index') || request()->routeIs('admin.menus.create') || request()->routeIs('admin.menus.edit')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Manajemen Menu
                </a>

                <a href="{{ route('admin.recipes.index') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.recipes.*')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Manajemen Resep
                </a>

                <a href="{{ route('admin.menus.archive') }}"
                   @click="sidebarOpen = false"
                   class="block px-4 py-2 rounded-lg transition
                   {{ request()->routeIs('admin.menus.archive')
                        ? 'bg-blue-600 text-white'
                        : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    Arsip Menu
                </a>

            </div>
        </div>

        {{-- 4. OPERASIONAL KASIR --}}
        <div>
            <button @click="openKasir = !openKasir"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider
                           text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Operasional Kasir</span>
                <span :class="openKasir ? 'rotate-180' : ''"
                      class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openKasir" x-collapse x-cloak class="mt-2 space-y-1">
                <a href="#"
                   @click.prevent
                   class="block px-4 py-2 rounded-lg text-slate-400 dark:text-slate-500 cursor-not-allowed">
                    Transaksi (Segera)
                </a>
                <a href="#"
                   @click.prevent
                   class="block px-4 py-2 rounded-lg text-slate-400 dark:text-slate-500 cursor-not-allowed">
                    Detail Transaksi (Segera)
                </a>
            </div>
        </div>

        {{-- 5. PELAPORAN --}}
        <div>
            <button @click="openLaporan = !openLaporan"
                    class="w-full flex justify-between items-center px-4 py-2 text-xs uppercase tracking-wider
                           text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-white transition">
                <span>Pelaporan</span>
                <span :class="openLaporan ? 'rotate-180' : ''"
                      class="transition-transform duration-200">v</span>
            </button>

            <div x-show="openLaporan" x-collapse x-cloak class="mt-2 space-y-1">
                <a href="#"
                   @click.prevent
                   class="block px-4 py-2 rounded-lg text-slate-400 dark:text-slate-500 cursor-not-allowed">
                    Laporan Pemakaian (Segera)
                </a>

                <a href="#"
                   @click.prevent
                   class="block px-4 py-2 rounded-lg text-slate-400 dark:text-slate-500 cursor-not-allowed">
                    Laporan Penjualan (Segera)
                </a>
            </div>
        </div>
    </nav>
</aside>
