@props([
    'title',
    'subtitle' => null,
    'breadcrumbParent' => null,
    'breadcrumbChild' => null,
])

<header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if($breadcrumbParent || $breadcrumbChild)
        <nav class="flex items-center gap-2 text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">
            @if($breadcrumbParent)
            <span class="text-blue-600 dark:text-blue-400">{{ $breadcrumbParent }}</span>
            @endif
            @if($breadcrumbParent && $breadcrumbChild)
            <span>/</span>
            @endif
            @if($breadcrumbChild)
            <span>{{ $breadcrumbChild }}</span>
            @endif
        </nav>
        @endif
        
        <h1 class="{{ ($breadcrumbParent || $breadcrumbChild) ? 'mt-1.5' : '' }} text-2xl font-black tracking-tight text-slate-900 dark:text-white sm:text-3xl">{{ $title }}</h1>
        
        @if($subtitle)
        <p class="mt-1 text-[11px] font-medium text-slate-500 dark:text-slate-400 sm:text-xs">
            {{ $subtitle }}
        </p>
        @endif
    </div>

    @if(isset($slot) && $slot->isNotEmpty())
    <div class="flex flex-wrap items-center gap-2.5">
        {{ $slot }}
    </div>
    @endif
</header>
