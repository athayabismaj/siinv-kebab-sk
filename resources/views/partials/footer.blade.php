<footer class="bg-white dark:bg-slate-900
               border-t border-slate-200 dark:border-slate-800
               h-14 flex items-center justify-between
               px-6 text-sm text-slate-500 dark:text-slate-400">

    <div>
        © {{ date('Y') }}
        <span class="font-medium text-slate-700 dark:text-slate-200">
            Sistem Inventory 
        </span> - Kebab SK
    </div>

    <div class="flex items-center gap-4 text-xs">
        @auth
            <span>|</span>
            <span class="capitalize">
                {{ auth()->user()->role->name }} Panel
            </span>
        @endauth
    </div>

</footer>