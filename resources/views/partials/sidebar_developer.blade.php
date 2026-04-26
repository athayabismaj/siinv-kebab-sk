<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed top-0 left-0 md:relative z-50 w-64 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-r border-slate-200/80 dark:border-slate-800/80 transform transition-transform duration-300 ease-in-out flex flex-col md:h-full"
    style="height: 100dvh;">

    {{-- BRAND HEADER --}}
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-blue-500/25">
                SK
            </div>
            <div>
                <h2 class="text-base font-semibold text-slate-800 dark:text-white leading-tight">Kebab SK</h2>
                <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest">Super Admin</p>
            </div>
        </div>
        <button @click="sidebarOpen = false" class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-4 py-4 md:py-6 space-y-4 md:space-y-6 text-sm">

        {{-- 1. BERANDA --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Beranda</p>
            <div class="space-y-1">
                <a href="{{ route('developer.panel') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('developer.panel') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        {{-- 2. KELOLA SISTEM --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Kelola Sistem</p>
            <div class="space-y-1">
                <a href="{{ route('developer.owners.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('developer.owners.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Manajemen Owner</span>
                </a>
                <a href="{{ route('developer.backups.index') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium {{ request()->routeIs('developer.backups.*') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    <span>Manajemen Backup</span>
                </a>
            </div>
        </div>

        {{-- 3. NAVIGASI PINTAS --}}
        <div>
            <p class="px-4 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">Navigasi Pintas</p>
            <div class="space-y-1">
                <a href="{{ route('admin.panel') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Panel Admin</span>
                </a>
                <a href="{{ route('owner.panel') }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 px-4 py-2 rounded-xl transition font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span>Panel Owner</span>
                </a>
            </div>
        </div>

    </nav>
</aside>
