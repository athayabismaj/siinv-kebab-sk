@props([
    'panelLabel',
    'sections',
    'variant' => 'default',
])

@php
    $baseItemClass = 'group relative flex min-h-9 items-center gap-2.5 rounded-xl px-2.5 py-2 text-[12.5px] font-semibold transition-all';
    $isDeveloper = $variant === 'developer';
    $activeItemClass = $isDeveloper
        ? 'bg-white !text-slate-950 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:!text-slate-100 dark:ring-slate-700/80'
        : 'bg-blue-600 !text-white shadow-md shadow-blue-500/20 ring-1 ring-blue-700/50 dark:bg-blue-500 dark:ring-blue-400/50';
    $inactiveItemClass = 'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-white';
@endphp

<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
    class="app-sidebar fixed left-0 top-0 z-50 flex w-64 transform flex-col border-r border-slate-200/80 bg-white/90 backdrop-blur-xl transition-transform duration-300 ease-in-out dark:border-slate-800/80 dark:bg-slate-900/90 md:relative md:h-full md:translate-x-0">
    <div class="flex h-16 items-center justify-between border-b border-slate-200 px-4 dark:border-slate-800">
        <div class="flex min-w-0 items-center gap-3">
            <div class="h-8 w-8 overflow-hidden rounded-xl bg-white shadow-sm shadow-slate-200/70 ring-1 ring-slate-200 dark:bg-slate-800 dark:shadow-none dark:ring-slate-700">
                <img src="{{ asset('images/kebab-sk-logo-report.jpeg') }}" alt="Logo Kebab SK" class="h-full w-full object-cover">
            </div>
            <div class="min-w-0">
                <h2 class="truncate text-[15px] font-bold leading-tight text-slate-900 dark:text-white">Kebab SK</h2>
                <p class="text-[9px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">{{ $panelLabel }}</p>
            </div>
        </div>
        <button @click="sidebarOpen = false" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-white md:hidden" aria-label="Tutup sidebar">
            <x-icon name="close" class="h-5 w-5" />
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
                            <a href="{{ $item['route'] }}" @click="sidebarOpen = false" class="{{ $baseItemClass }} {{ $item['active'] ? $activeItemClass : $inactiveItemClass }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $item['active'] ? ($isDeveloper ? 'bg-slate-100 !text-slate-950 dark:bg-slate-700 dark:!text-slate-100' : 'bg-white/20 !text-white shadow-inner dark:bg-black/10') : 'bg-slate-100 text-slate-500 group-hover:bg-white dark:bg-slate-800 dark:text-slate-400 dark:group-hover:bg-slate-700' }}">
                                    <x-icon :name="$item['icon']" class="h-4 w-4" />
                                </span>
                                <span class="min-w-0 flex-1 truncate {{ $item['active'] ? ($isDeveloper ? '!text-slate-950 dark:!text-slate-100' : '!text-white font-bold') : '' }}">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </nav>
</aside>
