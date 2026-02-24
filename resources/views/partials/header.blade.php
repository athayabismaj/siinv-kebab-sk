<header class="bg-white dark:bg-slate-900
               border-b border-slate-200 dark:border-slate-800
               h-16 flex items-center justify-between px-8">

    <div class="flex items-center gap-4">

        {{-- Hamburger Mobile --}}
        <button @click="sidebarOpen = true"
                class="md:hidden p-2 rounded hover:bg-slate-100 dark:hover:bg-slate-800">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-6 h-6" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <h1 class="text-lg font-semibold text-slate-800 dark:text-white">
            @yield('page-title', 'Dashboard')
        </h1>
    </div>

    <button onclick="document.documentElement.classList.toggle('dark')"
            class="text-xs px-3 py-1 rounded bg-slate-100 dark:bg-slate-800">
        Toggle Dark
    </button>

</header>