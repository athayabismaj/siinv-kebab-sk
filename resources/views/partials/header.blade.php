<header 
    x-data="{
        darkMode: localStorage.getItem('darkMode') === null
            ? window.matchMedia('(prefers-color-scheme: dark)').matches
            : localStorage.getItem('darkMode') === 'true',
        init() {
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
        toggleTheme() {
            this.darkMode = !this.darkMode;
            document.documentElement.classList.toggle('dark', this.darkMode);
            localStorage.setItem('darkMode', this.darkMode);
        }
    }"
    class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl
           border-b border-slate-200/80 dark:border-slate-800/80
           h-16 flex items-center justify-between
           px-4 sm:px-6 relative z-30">

    <div class="flex min-w-0 items-center gap-3">

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

    <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">

        {{-- Toggle Theme --}}
        <style>
            .theme-switch {
                position: relative;
                width: 64px;
                height: 32px;
                flex: 0 0 64px;
                border-radius: 9999px;
                padding: 3px;
                transition: background-color 250ms ease, border-color 250ms ease, box-shadow 250ms ease;
            }

            .theme-switch-light {
                background: #292b2f;
                border: 1px solid #3f4248;
                box-shadow: inset 0 1px 2px rgb(0 0 0 / 22%);
            }

            .theme-switch-dark {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                box-shadow: inset 0 1px 2px rgb(15 23 42 / 8%);
            }

            .theme-switch-knob {
                display: grid;
                width: 24px;
                height: 24px;
                place-items: center;
                border-radius: 9999px;
                transform: translateX(0);
                transition: transform 280ms cubic-bezier(.22, 1, .36, 1), background-color 250ms ease, color 250ms ease;
            }

            .theme-switch-knob-light {
                color: #111827;
                background: #ffffff;
                box-shadow: 0 1px 4px rgb(0 0 0 / 24%);
            }

            .theme-switch-knob-dark {
                color: #ffffff;
                background: #050505;
                box-shadow: 0 1px 4px rgb(0 0 0 / 28%);
                transform: translateX(32px);
            }
        </style>

        <button
            type="button"
            @click="toggleTheme()"
            :aria-label="darkMode ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
            :title="darkMode ? 'Mode gelap aktif' : 'Mode terang aktif'"
            :class="darkMode ? 'theme-switch-dark' : 'theme-switch-light'"
            class="theme-switch focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900">
            <span
                :class="darkMode ? 'theme-switch-knob-dark' : 'theme-switch-knob-light'"
                class="theme-switch-knob">
                <svg
                    x-show="!darkMode"
                    x-transition.opacity.duration.150ms
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1.5M12 19.5V21M4.22 4.22l1.06 1.06M18.72 18.72l1.06 1.06M3 12h1.5M19.5 12H21M4.22 19.78l1.06-1.06M18.72 5.28l1.06-1.06M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"></path>
                </svg>

                <svg
                    x-show="darkMode"
                    x-transition.opacity.duration.150ms
                    class="h-4 w-4"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    stroke="none">
                    <path d="M20.2 15.3A8.6 8.6 0 018.7 3.8a8.7 8.7 0 1011.5 11.5z"></path>
                </svg>
            </span>
        </button>

        {{-- NOTIFICATION BELL --}}
        @php
            $roleName = strtolower(optional(optional(auth()->user())->role)->name ?? '');
            $stockRoute = $roleName === 'owner' ? route('owner.stocks.index') : route('admin.stocks.index');
        @endphp
        
        @if(in_array($roleName, ['admin', 'owner'], true))
        <div class="relative" x-data="{ openNotification: false }">
            <button @click="openNotification = !openNotification"
                    class="relative flex h-10 w-10 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                
                @php
                    $totalNotif = ($outOfStockCount ?? 0) + ($lowStockCount ?? 0);
                @endphp

                @if($totalNotif > 0)
                <span class="absolute top-1.5 right-1.5 flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ ($outOfStockCount ?? 0) > 0 ? 'bg-red-400' : 'bg-amber-400' }} opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 {{ ($outOfStockCount ?? 0) > 0 ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                </span>
                @endif
            </button>

            <div x-show="openNotification"
                 @click.outside="openNotification = false"
                 x-transition
                 class="absolute right-0 mt-2 w-72 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg overflow-hidden z-50">
                <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-sm font-bold text-slate-800 dark:text-white">Notifikasi</p>
                    @if($totalNotif > 0)
                        <span class="px-2 py-0.5 rounded-full {{ ($outOfStockCount ?? 0) > 0 ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }} text-[10px] font-bold">{{ $totalNotif }} Baru</span>
                    @endif
                </div>
                
                <div class="max-h-64 overflow-y-auto">
                    @if($totalNotif > 0)
                        {{-- Out of stock alert --}}
                        @if(($outOfStockCount ?? 0) > 0)
                        <a href="{{ $stockRoute }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-l-4 border-red-500 {{ ($lowStockCount ?? 0) > 0 ? 'border-b border-slate-100 dark:border-slate-800' : '' }}">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 p-2 bg-red-50 dark:bg-red-500/10 text-red-500 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Stok Habis!</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">Ada <strong>{{ $outOfStockCount }} bahan</strong> yang kehabisan stok. Segera restock untuk melanjutkan penjualan.</p>
                                </div>
                            </div>
                        </a>
                        @endif

                        {{-- Low stock alert --}}
                        @if(($lowStockCount ?? 0) > 0)
                        <a href="{{ $stockRoute }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-l-4 border-amber-500">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 p-2 bg-amber-50 dark:bg-amber-500/10 text-amber-500 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">Stok Menipis</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">Ada <strong>{{ $lowStockCount }} bahan</strong> yang persediaannya akan segera habis.</p>
                                </div>
                            </div>
                        </a>
                        @endif
                    @else
                        <div class="p-6 text-center">
                            <svg class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Tidak ada notifikasi baru.</p>
                        </div>
                    @endif
                </div>
                
                @if($totalNotif > 0)
                <a href="{{ $stockRoute }}" class="block text-center py-2 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-800 text-xs font-bold text-blue-600 hover:text-blue-700 dark:hover:text-blue-400 transition">
                    Lihat Semua Stok
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- PROFILE --}}
        <div class="relative" x-data="{ openProfile: false }">

            <button @click="openProfile = !openProfile"
                    class="flex h-10 w-10 items-center justify-center rounded-full transition hover:bg-slate-100 dark:hover:bg-slate-800">

                {{-- Avatar --}}
                <div class="w-9 h-9 rounded-full
                            bg-blue-600 text-white
                            flex items-center justify-center">
                    <span class="block translate-x-[0.5px] translate-y-[0.5px] text-sm font-semibold leading-none">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
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
