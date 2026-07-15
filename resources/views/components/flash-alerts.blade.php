@if($flashMessages->isNotEmpty() || $validationErrors->isNotEmpty())
    <div
        @if($shouldAutoDismiss)
            x-data="{ visible: true }"
            x-init="setTimeout(() => visible = false, 6000)"
            x-show="visible"
            x-transition.opacity.duration.300ms
        @endif
        data-flash-alerts="{{ $position }}"
        class="{{ $containerClass }}"
    >
        @foreach($flashMessages as $flash)
            <div class="flex items-start gap-3 rounded-2xl border {{ $flash['border'] }} bg-white px-4 py-3 shadow-sm dark:bg-slate-900">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $flash['iconBg'] }}">
                    <x-icon :name="$flash['icon']" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest {{ $flash['labelColor'] }}">{{ $flash['label'] }}</p>
                    <p class="mt-0.5 whitespace-pre-line text-sm font-semibold leading-relaxed text-slate-700 dark:text-slate-200">{{ $flash['message'] }}</p>
                </div>
            </div>
        @endforeach

        @if($validationErrors->isNotEmpty())
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-white px-4 py-3 shadow-sm dark:border-rose-900/60 dark:bg-slate-900">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                    <x-icon name="error" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-300">Input Belum Valid</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4 text-sm font-semibold leading-relaxed text-slate-700 dark:text-slate-200">
                        @foreach($validationErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
@endif
