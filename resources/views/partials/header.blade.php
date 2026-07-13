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

    <div class="flex min-w-0 items-center gap-1.5 sm:gap-3 shrink">

        <button 
            @click="sidebarOpen = true"
            class="md:hidden p-1.5 sm:p-2 rounded-lg
                hover:bg-slate-100 dark:hover:bg-slate-800 transition shrink-0"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5 sm:w-6 sm:h-6 text-slate-600 dark:text-slate-300"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <h1 class="text-[13px] sm:text-lg font-bold text-slate-800 dark:text-white truncate hidden min-[375px]:block">
            @yield('page-title')
        </h1>
    </div>

    <div class="ml-auto flex shrink-0 items-center gap-1.5 sm:gap-3">

        @php
            $headerRoleName = strtolower(optional(optional(auth()->user())->role)->name ?? '');
            
            if ($headerRoleName === 'admin' && class_exists(\App\Support\BranchScope::class)) {
                $headerBranchOptions = \App\Support\BranchScope::optionsFor(auth()->user());
                $headerActiveBranchId = \App\Support\BranchScope::scopedBranchIdFor(auth()->user());
                $headerBranchRoute = route('admin.branch-context.switch');
                $headerShowAllOption = false;
            } elseif ($headerRoleName === 'owner' && class_exists(\App\Support\BranchScope::class)) {
                $headerBranchOptions = \App\Support\BranchScope::options();
                $headerActiveBranchId = (int) session('owner_active_branch_id', 0);
                $headerBranchRoute = route('owner.branch-context.switch');
                $headerShowAllOption = true;
            } else {
                $headerBranchOptions = collect();
                $headerActiveBranchId = null;
                $headerBranchRoute = '#';
                $headerShowAllOption = false;
            }

            $activeBranchName = 'Semua Cabang';
            if ($headerActiveBranchId !== 0 && $headerActiveBranchId !== null) {
                $activeBranch = $headerBranchOptions->firstWhere('id', $headerActiveBranchId);
                if ($activeBranch) {
                    $activeBranchName = $activeBranch->name;
                }
            }
        @endphp

        @if($headerBranchOptions->count() > 1 || ($headerShowAllOption && $headerBranchOptions->count() >= 1))
        <div class="relative" x-data="{ openBranch: false }">
            <button @click="openBranch = !openBranch"
                    class="relative flex h-8 w-8 sm:h-9 sm:w-auto sm:px-3 sm:pr-2.5 items-center justify-center sm:justify-between rounded-full border border-slate-200/80 bg-slate-50/50 text-slate-500 sm:text-slate-600 transition hover:bg-slate-100 hover:text-slate-700 dark:border-slate-700/80 dark:bg-slate-800/50 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 sm:mr-1.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span class="hidden sm:block text-[11px] font-bold truncate max-w-[120px]">{{ $activeBranchName }}</span>
                <svg class="hidden sm:block h-3.5 w-3.5 ml-1.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <div x-show="openBranch"
                 @click.outside="openBranch = false"
                 x-transition
                 class="absolute right-0 sm:left-0 sm:right-auto mt-2 w-48 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-slate-200/20 dark:shadow-slate-900/50 overflow-hidden z-50">
                
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Pilih Cabang</p>
                </div>
                
                <div class="p-1.5 max-h-64 overflow-y-auto">
                    <form method="POST" action="{{ $headerBranchRoute }}" x-ref="branchForm">
                        @csrf
                        <input type="hidden" name="branch_id" x-ref="branchInput">
                        
                        @if($headerShowAllOption)
                            <button type="button" @click="$refs.branchInput.value = '0'; $refs.branchForm.submit()"
                               class="w-full flex items-center justify-between rounded-xl px-3 py-2.5 text-xs font-semibold {{ (int) $headerActiveBranchId === 0 ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/50 dark:hover:text-white' }} transition-colors">
                                <span>Semua Cabang</span>
                                @if((int) $headerActiveBranchId === 0)
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                @endif
                            </button>
                        @endif
                        
                        @foreach($headerBranchOptions as $branch)
                            <button type="button" @click="$refs.branchInput.value = '{{ $branch->id }}'; $refs.branchForm.submit()"
                               class="w-full flex items-center justify-between rounded-xl px-3 py-2.5 text-xs font-semibold mt-0.5 {{ (int) $headerActiveBranchId === (int) $branch->id ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/50 dark:hover:text-white' }} transition-colors">
                                <span class="truncate pr-2">{{ $branch->name }}</span>
                                @if((int) $headerActiveBranchId === (int) $branch->id)
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                @endif
                            </button>
                        @endforeach
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Toggle Theme --}}
        <button
            type="button"
            @click="toggleTheme()"
            :aria-label="darkMode ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
            :title="darkMode ? 'Mode gelap aktif' : 'Mode terang aktif'"
            class="relative flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-full border border-slate-200/80 bg-slate-50/50 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-slate-700/80 dark:bg-slate-800/50 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
            
            <svg x-show="!darkMode" x-transition.opacity.duration.200ms class="absolute h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
            <svg x-show="darkMode" x-transition.opacity.duration.200ms class="absolute h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 5.657l-.707-.707m12.728 0l-.707.707M6.343 18.343l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </button>

        {{-- NOTIFICATION BELL --}}
        @php
            $roleName = strtolower(optional(optional(auth()->user())->role)->name ?? '');
            $stockRoute = $roleName === 'owner' ? route('owner.stocks.index') : route('admin.stocks.index');
        @endphp
        
        @if(in_array($roleName, ['admin', 'owner'], true))
        <div class="relative" x-data="{ openNotification: false }">
            <button @click="openNotification = !openNotification"
                    class="relative flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-full border border-slate-200/80 bg-slate-50/50 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-slate-700/80 dark:bg-slate-800/50 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                
                @php
                    $totalNotif = ($outOfStockCount ?? 0) + ($lowStockCount ?? 0);
                @endphp

                @if($totalNotif > 0)
                <span class="absolute right-2 top-2 sm:right-2.5 sm:top-2.5 flex h-1.5 w-1.5">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ ($outOfStockCount ?? 0) > 0 ? 'bg-red-400' : 'bg-amber-400' }} opacity-75"></span>
                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full {{ ($outOfStockCount ?? 0) > 0 ? 'bg-red-500' : 'bg-amber-500' }}"></span>
                </span>
                @endif
            </button>

            <div x-show="openNotification"
                 @click.outside="openNotification = false"
                 x-transition
                 class="absolute right-0 mt-2 w-72 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-slate-200/20 dark:shadow-slate-900/50 overflow-hidden z-50">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-800 dark:text-slate-200">Notifikasi</p>
                    @if($totalNotif > 0)
                        <span class="px-2 py-0.5 rounded-full {{ ($outOfStockCount ?? 0) > 0 ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400' }} text-[10px] font-black ring-1 {{ ($outOfStockCount ?? 0) > 0 ? 'ring-red-200 dark:ring-red-500/20' : 'ring-amber-200 dark:ring-amber-500/20' }}">{{ $totalNotif }} Baru</span>
                    @endif
                </div>
                
                <div class="max-h-64 overflow-y-auto">
                    @if($totalNotif > 0)
                        {{-- Out of stock alert --}}
                        @if(($outOfStockCount ?? 0) > 0)
                        <a href="{{ $stockRoute }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-l-2 border-red-500 {{ ($lowStockCount ?? 0) > 0 ? 'border-b border-slate-50 dark:border-slate-800/50' : '' }}">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 p-2 bg-red-50 dark:bg-red-500/10 text-red-500 rounded-xl">
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
                        <a href="{{ $stockRoute }}" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border-l-2 border-amber-500">
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 p-2 bg-amber-50 dark:bg-amber-500/10 text-amber-500 rounded-xl">
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
                            <p class="text-[11px] font-medium text-slate-400 dark:text-slate-500">Tidak ada notifikasi baru.</p>
                        </div>
                    @endif
                </div>
                
                @if($totalNotif > 0)
                <a href="{{ $stockRoute }}" class="block text-center py-2.5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 text-[10px] font-black uppercase tracking-widest text-blue-600 hover:text-blue-700 dark:hover:text-blue-400 transition">
                    Lihat Semua Stok
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- PROFILE --}}
        <div class="relative" x-data="{ openProfile: false }">

            <button @click="openProfile = !openProfile"
                    class="group flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600 ring-1 ring-slate-200 transition hover:ring-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:ring-slate-500">
                <span class="text-[10px] sm:text-[11px] font-bold leading-none">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
            </button>

            {{-- DROPDOWN --}}
            <div x-show="openProfile"
                 @click.outside="openProfile = false"
                 x-transition
                 class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-slate-200/20 dark:shadow-slate-900/50 overflow-hidden z-50">

                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                    <p class="text-xs font-black text-slate-800 dark:text-white truncate">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 truncate mt-0.5">
                        {{ auth()->user()->email }}
                    </p>
                </div>

                <div class="p-1.5">
                    <a href="{{ route('profile.show') }}"
                       class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/50 dark:hover:text-white transition-colors">
                        <svg class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Profile
                    </a>

                    <a href="{{ route('profile.password.form') }}"
                       class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/50 dark:hover:text-white transition-colors">
                        <svg class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        Ubah Kata Sandi
                    </a>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800 p-1.5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-500/10 dark:hover:text-red-400 transition-colors">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Logout
                        </button>
                    </form>
                </div>

            </div>

        </div>

    </div>

</header>
