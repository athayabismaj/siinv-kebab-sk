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

    {{-- HEADER --}}
    <div class="h-16 flex flex-col justify-center px-6
                border-b border-slate-200 dark:border-slate-800">

        <h2 class="text-lg font-semibold text-slate-800 dark:text-white">
            Owner Panel
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Kebab SK Management
        </p>
    </div>

    {{-- NAV --}}
    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1 text-sm">

        {{-- Dashboard --}}
        <a href="{{ route('owner.panel') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition
           {{ request()->routeIs('owner.panel') 
                ? 'bg-blue-600 text-white' 
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">

            {{-- Icon --}}
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-5 h-5"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.8"
                      d="M3 12l2-2 7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/>
            </svg>

            Dashboard
        </a>

        {{-- User Management --}}
        <a href="{{ route('owner.users.index') }}"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 px-4 py-3 rounded-lg transition
           {{ request()->routeIs('owner.users.*') 
                ? 'bg-blue-600 text-white' 
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-5 h-5"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.8"
                      d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m10-2.13a4 4 0 10-8 0 4 4 0 008 0z"/>
            </svg>

            User Management
        </a>

        {{-- Laporan --}}
        <a href="#"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 px-4 py-3 rounded-lg
                  text-slate-600 dark:text-slate-300
                  hover:bg-slate-100 dark:hover:bg-slate-800 transition">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-5 h-5"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.8"
                      d="M9 17v-6m4 6V7m4 10V4M5 20h14"/>
            </svg>

            Laporan Penjualan
        </a>

        {{-- Monitoring --}}
        <a href="#"
           @click="sidebarOpen = false"
           class="flex items-center gap-3 px-4 py-3 rounded-lg
                  text-slate-600 dark:text-slate-300
                  hover:bg-slate-100 dark:hover:bg-slate-800 transition">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-5 h-5"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.8"
                      d="M20 13V7a2 2 0 00-2-2h-4l-2-2-2 2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6"/>
            </svg>

            Monitoring Stok
        </a>

        {{-- ARSIP --}}
        <div>
            <button 
                @click="openArchive = !openArchive"
                class="w-full flex items-center justify-between px-4 py-3 rounded-lg
                       text-slate-600 dark:text-slate-300
                       hover:bg-slate-100 dark:hover:bg-slate-800 transition">

                <div class="flex items-center gap-3">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="1.8"
                              d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>

                    Arsip Data
                </div>

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
                 class="mt-1 space-y-1 pl-10 text-sm">

                <a href="{{ route('owner.users.archive') }}"
                   @click="sidebarOpen = false"
                   class="block px-3 py-2 rounded-md
                          text-slate-500 dark:text-slate-400
                          hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    User
                </a>
                <a href="#" 
                    @click="localStorage.setItem('sidebarOpen', true)" 
                    class="block px-3 py-2 rounded-md 
                        text-slate-500 dark:text-slate-400 
                        hover:bg-slate-100 dark:hover:bg-slate-800 transition"> 
                    Menu 
                </a> 
                <a href="#" @click="localStorage.setItem('sidebarOpen', true)" 
                    class="block px-3 py-2 rounded-md 
                        text-slate-500 dark:text-slate-400 
                        hover:bg-slate-100 dark:hover:bg-slate-800 transition"> 
                Ingredients </a>

            </div>
        </div>

    </nav>

    {{-- FOOTER --}}
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