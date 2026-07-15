@props(['id', 'maxWidth' => 'md', 'type' => 'default'])

<div
    x-data="{ show: false, name: '{{ $id }}' }"
    x-show="show"
    @open-modal.window="if ($event.detail === name) show = true"
    @close-modal.window="if ($event.detail === name) show = false"
    @keydown.escape.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto overflow-x-hidden p-4 sm:p-6"
    x-cloak
>
    <!-- Backdrop -->
    <div
        x-show="show"
        class="fixed inset-0 transform transition-all"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="show = false"
    >
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm dark:bg-slate-900/60"></div>
    </div>

    <!-- Modal Content -->
    <div
        x-show="show"
        class="relative w-full @switch($maxWidth) @case('sm') sm:max-w-sm @break @case('md') sm:max-w-md @break @case('lg') sm:max-w-lg @break @case('xl') sm:max-w-xl @break @case('2xl') sm:max-w-2xl @break @default sm:max-w-2xl @endswitch transform overflow-hidden rounded-3xl bg-white text-left align-middle shadow-2xl ring-1 ring-slate-200 transition-all dark:bg-slate-900 dark:ring-slate-800"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
    >
        @if (isset($title) || isset($icon))
            <div class="flex items-start gap-4 px-6 pt-6 pb-4 sm:px-8 sm:pt-8">
                @if (isset($icon))
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl 
                        @switch($type)
                            @case('danger') bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 @break
                            @case('warning') bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400 @break
                            @case('success') bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 @break
                            @default bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400
                        @endswitch
                    ">
                        {{ $icon }}
                    </div>
                @endif
                <div class="flex-1 pt-1">
                    @if (isset($title))
                        <h3 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $title }}</h3>
                    @endif
                    @if (isset($description))
                        <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            {{ $description }}
                        </div>
                    @endif
                </div>
                <button type="button" @click="show = false" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-500 focus:outline-none dark:hover:bg-slate-800 dark:hover:text-slate-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        @endif

        <div class="px-6 py-2 sm:px-8 sm:pb-6">
            {{ $slot }}
        </div>

        @if (isset($footer))
            <div class="flex flex-col-reverse gap-3 bg-slate-50/50 px-6 py-5 sm:flex-row sm:justify-end sm:px-8 dark:bg-slate-800/30">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
