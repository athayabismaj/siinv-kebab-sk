@php
    $sections = [
        [
            'label' => 'Ringkasan',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => route('developer.panel'),
                    'active' => request()->routeIs('developer.panel'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>',
                ],
            ],
        ],
        [
            'label' => 'Kelola Sistem',
            'items' => [
                [
                    'label' => 'Manajemen Owner',
                    'route' => route('developer.owners.index'),
                    'active' => request()->routeIs('developer.owners.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m8-4.13a4 4 0 11-8 0 4 4 0 018 0zm-8 0a4 4 0 11-8 0 4 4 0 018 0z"></path>',
                ],
                [
                    'label' => 'Manajemen Backup',
                    'route' => route('developer.backups.index'),
                    'active' => request()->routeIs('developer.backups.*'),
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>',
                ],
            ],
        ],
        [
            'label' => 'Navigasi Pintas',
            'items' => [
                [
                    'label' => 'Panel Admin',
                    'route' => route('admin.panel'),
                    'active' => false,
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>',
                ],
                [
                    'label' => 'Panel Owner',
                    'route' => route('owner.panel'),
                    'active' => false,
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>',
                ],
            ],
        ],
    ];

    $baseItemClass = 'group relative flex min-h-9 items-center gap-2.5 rounded-xl px-2.5 py-2 text-[12.5px] font-semibold transition-all';
    $activeItemClass = 'bg-white !text-slate-950 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:!text-slate-100 dark:ring-slate-700/80';
    $inactiveItemClass = 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-white';
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="fixed top-0 left-0 md:relative z-50 w-64 bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border-r border-slate-200/80 dark:border-slate-800/80 transform transition-transform duration-300 ease-in-out flex flex-col md:h-full"
    style="height: 100dvh;">

    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-200 dark:border-slate-800">
        <div class="flex min-w-0 items-center gap-3">
            <div class="h-8 w-8 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200 shadow-sm shadow-slate-200/70 dark:bg-slate-800 dark:ring-slate-700 dark:shadow-none">
                <img
                    src="{{ asset('images/kebab-sk-logo-report.jpeg') }}"
                    alt="Logo Kebab SK"
                    class="h-full w-full object-cover">
            </div>
            <div class="min-w-0">
                <h2 class="truncate text-[15px] font-bold text-slate-900 dark:text-white leading-tight">Kebab SK</h2>
                <p class="text-[9px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-[0.22em]">Super Admin</p>
            </div>
        </div>
        <button @click="sidebarOpen = false" class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-slate-800 transition" aria-label="Tutup sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-2.5 py-3 text-sm">
        <div class="space-y-3">
            @foreach($sections as $section)
                <section>
                    <div class="mb-1 flex items-center gap-2.5 px-2.5">
                        <p class="shrink-0 text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-600">{{ $section['label'] }}</p>
                        <div class="h-px flex-1 bg-slate-200/70 dark:bg-slate-800"></div>
                    </div>

                    <div class="space-y-1">
                        @foreach($section['items'] as $item)
                            <a href="{{ $item['route'] }}"
                               @click="sidebarOpen = false"
                               class="{{ $baseItemClass }} {{ $item['active'] ? $activeItemClass : $inactiveItemClass }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $item['active'] ? 'bg-slate-100 !text-slate-950 dark:bg-slate-700 dark:!text-slate-100' : 'bg-slate-100 text-slate-500 group-hover:bg-white dark:bg-slate-800 dark:text-slate-400 dark:group-hover:bg-slate-700' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                </span>

                                <span class="min-w-0 flex-1 truncate {{ $item['active'] ? '!text-slate-950 dark:!text-slate-100' : '' }}">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </nav>
</aside>
