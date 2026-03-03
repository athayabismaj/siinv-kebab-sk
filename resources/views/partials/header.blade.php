<header 
    x-data="{ openProfile: false }"
    class="bg-white dark:bg-slate-900
           border-b border-slate-200 dark:border-slate-800
           h-16 flex items-center justify-between
           px-6 relative">

    <div class="flex items-center gap-4">

        <button 
            @click="sidebarOpen = true"
            class="md:hidden p-2 rounded-lg
                hover:bg-slate-100 dark:hover:bg-slate-800 transition"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6 text-slate-600 dark:text-slate-300"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <h1 class="text-lg font-semibold text-slate-800 dark:text-white">
            @yield('page-title')
        </h1>
    </div>

    <div class="flex items-center gap-4">

        {{-- Toggle Dark --}}
        <button
            onclick="
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode',
                document.documentElement.classList.contains('dark'));
            "
            class="text-xs px-3 py-1.5 rounded-lg
                   bg-slate-100 dark:bg-slate-800
                   text-slate-600 dark:text-slate-300
                   hover:bg-slate-200 dark:hover:bg-slate-700 transition">
            Toggle Dark
        </button>

        {{-- PROFILE --}}
        <div class="relative">

            <button @click="openProfile = !openProfile"
                    class="flex items-center gap-2
                           p-2 rounded-full
                           hover:bg-slate-100 dark:hover:bg-slate-800 transition">

                {{-- Avatar --}}
                <div class="w-9 h-9 rounded-full
                            bg-blue-600 text-white
                            flex items-center justify-center
                            text-sm font-semibold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </button>

            {{-- DROPDOWN --}}
            <div x-show="openProfile"
                 @click.outside="openProfile = false"
                 x-transition
                 class="absolute right-0 mt-2 w-48
                        bg-white dark:bg-slate-900
                        border border-slate-200 dark:border-slate-800
                        rounded-xl shadow-lg
                        overflow-hidden z-50">

                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800">
                    <p class="text-sm font-medium text-slate-800 dark:text-white">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ auth()->user()->email }}
                    </p>
                </div>

                <a href="{{ route('profile.show') }}"
                   class="block px-4 py-2 text-sm
                          text-slate-600 dark:text-slate-300
                          hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Profile
                </a>

                <a href="{{ route('profile.password.form') }}"
                   class="block px-4 py-2 text-sm
                          text-slate-600 dark:text-slate-300
                          hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Ubah Password
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-4 py-2 text-sm
                                   text-red-600 hover:bg-red-50
                                   dark:hover:bg-red-900/30 transition">
                        Logout
                    </button>
                </form>

            </div>

        </div>

    </div>

</header>

<script>
    if (localStorage.getItem('darkMode') === 'true') {
        document.documentElement.classList.add('dark');
    }
</script>