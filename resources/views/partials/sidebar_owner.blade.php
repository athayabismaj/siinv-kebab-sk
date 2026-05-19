@php
    $sections = [
        [
            'label' => 'Ringkasan',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => route('owner.panel'),
                    'active' => request()->routeIs('owner.panel'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>',
                ],
            ],
        ],
        [
            'label' => 'Operasional',
            'items' => [
                [
                    'label' => 'Monitoring Stok',
                    'route' => route('owner.stocks.index'),
                    'active' => request()->routeIs('owner.stocks.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>',
                ],
                [
                    'label' => 'Target Harian',
                    'route' => route('owner.targets.index'),
                    'active' => request()->routeIs('owner.targets.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                ],
            ],
        ],
        [
            'label' => 'Penjualan',
            'items' => [
                [
                    'label' => 'Laporan Penjualan',
                    'route' => route('owner.reports.sales'),
                    'active' => request()->routeIs('owner.reports.sales'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path>',
                ],
                [
                    'label' => 'Riwayat Transaksi',
                    'route' => route('owner.transactions.index'),
                    'active' => request()->routeIs('owner.transactions.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>',
                ],
            ],
        ],
        [
            'label' => 'Analisis',
            'items' => [
                [
                    'label' => 'Analisis Menu',
                    'route' => route('owner.analytics.menu'),
                    'active' => request()->routeIs('owner.analytics.menu'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>',
                ],
                [
                    'label' => 'Pemakaian Bahan',
                    'route' => route('owner.reports.usage'),
                    'active' => request()->routeIs('owner.reports.usage'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>',
                ],
            ],
        ],
        [
            'label' => 'Keuangan',
            'items' => [
                [
                    'label' => 'Tutup Buku',
                    'route' => route('owner.reports.closing.index'),
                    'active' => request()->routeIs('owner.reports.closing.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>',
                ],
                [
                    'label' => 'Laporan Pengeluaran',
                    'route' => route('owner.reports.cashflow'),
                    'active' => request()->routeIs('owner.reports.cashflow') || request()->routeIs('owner.reports.cashflow.export'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10V6m0 2v10m0 0v2"></path>',
                ],
            ],
        ],
        [
            'label' => 'Pengguna',
            'items' => [
                [
                    'label' => 'Daftar User',
                    'route' => route('owner.users.index'),
                    'active' => request()->routeIs('owner.users.index') || request()->routeIs('owner.users.create') || request()->routeIs('owner.users.edit') || request()->routeIs('owner.users.reset.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>',
                ],
                [
                    'label' => 'Arsip User',
                    'route' => route('owner.users.archive'),
                    'active' => request()->routeIs('owner.users.archive'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>',
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
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Panel Owner</p>
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
