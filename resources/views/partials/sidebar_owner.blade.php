<aside 
    x-data="{ openArchive: false }"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed md:relative z-50
           w-64
           bg-white dark:bg-slate-900
           border-r border-slate-200 dark:border-slate-800
           transform transition-transform duration-300
           flex flex-col
           h-screen">

    {{-- MOBILE OVERLAY --}}
    <div @click="sidebarOpen = false"
         x-show="sidebarOpen"
         x-transition
         class="fixed inset-0 bg-black/40 md:hidden">
    </div>

    {{-- SIDEBAR HEADER (SAMAKAN DENGAN HEADER) --}}
    <div class="h-16 flex flex-col justify-center px-6
                border-b border-slate-200 dark:border-slate-800">

        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">
            Owner Panel
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Kebab SK Management
        </p>
    </div>

    {{-- NAVIGATION --}}
    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1 text-sm">

        <a href="{{ route('owner.panel') }}"
           class="block px-4 py-3 rounded-lg transition
           {{ request()->routeIs('owner.panel') 
                ? 'bg-blue-600 text-white' 
                : 'hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            Dashboard
        </a>

        <a href="{{ route('owner.users.index') }}"
           class="block px-4 py-3 rounded-lg transition
           {{ request()->routeIs('owner.users.*') 
                ? 'bg-blue-600 text-white' 
                : 'hover:bg-slate-100 dark:hover:bg-slate-800' }}">
            User Management
        </a>

        <a href="#" class="block px-4 py-3 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
            Laporan Penjualan
        </a>

        <a href="#" class="block px-4 py-3 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
            Monitoring Stok
        </a>

        {{-- ARSIP --}}
        <div>
            <button 
                @click="openArchive = !openArchive"
                class="w-full text-left px-4 py-3 rounded-lg
                       hover:bg-slate-100 dark:hover:bg-slate-800 flex justify-between">

                <span>Arsip Data</span>

                <svg :class="openArchive ? 'rotate-180' : ''"
                     class="w-4 h-4 transition-transform"
                     fill="none" stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="openArchive"
                 x-collapse
                 x-cloak
                 class="mt-1 space-y-1 pl-6">

                <a href="{{ route('owner.users.archive') }}"
                   class="block px-3 py-2 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800">
                    User
                </a>

                <a href="#" class="block px-3 py-2 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800">
                    Menu
                </a>

                <a href="#" class="block px-3 py-2 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800">
                    Ingredients
                </a>
            </div>
        </div>
    </nav>

    {{-- SIDEBAR FOOTER (SAMAKAN DENGAN FOOTER) --}}
    <div class="h-14 px-6 flex items-center
                border-t border-slate-200 dark:border-slate-800">

        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button class="w-full bg-red-500 hover:bg-red-600
                           text-white text-sm py-2 rounded-lg transition">
                Logout
            </button>
        </form>
    </div>

</aside>