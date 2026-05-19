@php
    $sections = [
        [
            'label' => 'Ringkasan',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => route('admin.panel'),
                    'active' => request()->routeIs('admin.panel'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>',
                ],
            ],
        ],
        [
            'label' => 'Inventori',
            'items' => [
                [
                    'label' => 'Kategori Bahan',
                    'route' => route('admin.ingredient-categories.index'),
                    'active' => request()->routeIs('admin.ingredient-categories.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>',
                ],
                [
                    'label' => 'Manajemen Bahan',
                    'route' => route('admin.ingredients.index'),
                    'active' => request()->routeIs('admin.ingredients.index') || request()->routeIs('admin.ingredients.create') || request()->routeIs('admin.ingredients.edit'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>',
                ],
                [
                    'label' => 'Restok & Penyesuaian',
                    'route' => route('admin.stocks.index'),
                    'active' => request()->routeIs('admin.stocks.index') || request()->routeIs('admin.stocks.restock.*') || request()->routeIs('admin.stocks.adjust.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>',
                ],
                [
                    'label' => 'Riwayat Stok',
                    'route' => route('admin.stocks.logs'),
                    'active' => request()->routeIs('admin.stocks.logs'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>',
                ],
                [
                    'label' => 'Arsip Bahan',
                    'route' => route('admin.ingredients.archive'),
                    'active' => request()->routeIs('admin.ingredients.archive'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>',
                ],
            ],
        ],
        [
            'label' => 'Menu & Resep',
            'items' => [
                [
                    'label' => 'Kategori Menu',
                    'route' => route('admin.menu-categories.index'),
                    'active' => request()->routeIs('admin.menu-categories.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>',
                ],
                [
                    'label' => 'Manajemen Menu',
                    'route' => route('admin.menus.index'),
                    'active' => request()->routeIs('admin.menus.index') || request()->routeIs('admin.menus.create') || request()->routeIs('admin.menus.edit') || request()->routeIs('admin.menu-variants.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>',
                ],
                [
                    'label' => 'Manajemen Resep',
                    'route' => route('admin.recipes.index'),
                    'active' => request()->routeIs('admin.recipes.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>',
                ],
                [
                    'label' => 'Arsip Menu',
                    'route' => route('admin.menus.archive'),
                    'active' => request()->routeIs('admin.menus.archive'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>',
                ],
            ],
        ],
        [
            'label' => 'Kasir',
            'items' => [
                [
                    'label' => 'Stok Harian',
                    'route' => route('admin.daily-stocks.index'),
                    'active' => request()->routeIs('admin.daily-stocks.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>',
                ],
                [
                    'label' => 'Monitoring Transaksi',
                    'route' => route('admin.transactions.index'),
                    'active' => request()->routeIs('admin.transactions.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>',
                ],
            ],
        ],
        [
            'label' => 'Pelaporan',
            'items' => [
                [
                    'label' => 'Laporan Pemakaian',
                    'route' => route('admin.reports.usage'),
                    'active' => request()->routeIs('admin.reports.usage') || request()->routeIs('admin.reports.usage.export'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>',
                ],
                [
                    'label' => 'Laporan Stok Harian',
                    'route' => route('admin.reports.daily-stock'),
                    'active' => request()->routeIs('admin.reports.daily-stock') || request()->routeIs('admin.reports.daily-stock.export'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>',
                ],
                [
                    'label' => 'Laporan Pengeluaran',
                    'route' => route('admin.reports.cashflow'),
                    'active' => request()->routeIs('admin.reports.cashflow') || request()->routeIs('admin.reports.cashflow.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 2v10m0 0v2"></path>',
                ],
            ],
        ],
    ];

    $baseItemClass = 'group relative flex min-h-9 items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-semibold transition-all';
    $activeItemClass = 'bg-blue-600 text-white shadow-md shadow-blue-500/20';
    $inactiveItemClass = 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-white';
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed top-0 left-0 md:relative z-50 w-64 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-r border-slate-200/80 dark:border-slate-800/80 transform transition-transform duration-300 ease-in-out flex flex-col md:h-full"
    style="height: 100dvh;">

    {{-- BRAND HEADER --}}
    <div class="h-16 flex items-center justify-between px-5 border-b border-slate-200 dark:border-slate-800">
        <div class="flex min-w-0 items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex shrink-0 items-center justify-center text-white font-bold text-sm shadow-lg shadow-blue-500/25">
                SK
            </div>
            <div class="min-w-0">
                <h2 class="truncate text-base font-semibold text-slate-800 dark:text-white leading-tight">Kebab SK</h2>
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Panel Admin</p>
            </div>
        </div>
        <button @click="sidebarOpen = false" class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-slate-800 transition" aria-label="Tutup sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-3 text-sm">
        <div class="space-y-3.5">
            @foreach($sections as $section)
                <section>
                    <div class="mb-1.5 flex items-center gap-2.5 px-3">
                        <p class="shrink-0 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-600">{{ $section['label'] }}</p>
                        <div class="h-px flex-1 bg-slate-200/70 dark:bg-slate-800"></div>
                    </div>

                    <div class="space-y-1">
                        @foreach($section['items'] as $item)
                            <a href="{{ $item['route'] }}"
                               @click="sidebarOpen = false"
                               class="{{ $baseItemClass }} {{ $item['active'] ? $activeItemClass : $inactiveItemClass }}">
                                @if($item['active'])
                                    <span class="absolute left-0 top-1/2 h-4 w-1 -translate-y-1/2 rounded-r-full bg-white/90"></span>
                                @endif

                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md {{ $item['active'] ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-white dark:bg-slate-800 dark:text-slate-400 dark:group-hover:bg-slate-700' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                </span>

                                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </nav>
</aside>
