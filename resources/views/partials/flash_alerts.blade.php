@php
    $class = $class ?? 'mb-5 w-full space-y-2';
    $autoDismiss = $autoDismiss ?? true;
    $includeErrors = $includeErrors ?? false;

    $flashMessages = collect([
        'success' => [
            'label' => 'Berhasil',
            'message' => session('success'),
            'border' => 'border-emerald-200 dark:border-emerald-900/60',
            'iconBg' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
            'labelColor' => 'text-emerald-600 dark:text-emerald-300',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>',
        ],
        'warning' => [
            'label' => 'Perlu Dicek',
            'message' => session('warning'),
            'border' => 'border-amber-200 dark:border-amber-900/60',
            'iconBg' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
            'labelColor' => 'text-amber-600 dark:text-amber-300',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>',
        ],
        'error' => [
            'label' => 'Gagal',
            'message' => session('error'),
            'border' => 'border-rose-200 dark:border-rose-900/60',
            'iconBg' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
            'labelColor' => 'text-rose-600 dark:text-rose-300',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
        ],
    ])->filter(fn ($item) => filled($item['message']));

    $validationErrors = $includeErrors && $errors->any()
        ? collect($errors->all())->filter()
        : collect();

    $shouldAutoDismiss = $autoDismiss && $flashMessages->isNotEmpty() && $validationErrors->isEmpty();
@endphp

@if($flashMessages->isNotEmpty() || $validationErrors->isNotEmpty())
    <div
        @if($shouldAutoDismiss)
            x-data="{ visible: true }"
            x-init="setTimeout(() => visible = false, 6000)"
            x-show="visible"
            x-transition.opacity.duration.300ms
        @endif
        data-flash-alerts="{{ $position ?? 'inline' }}"
        class="{{ $class }}"
    >
        @foreach($flashMessages as $flash)
            <div class="flex items-start gap-3 rounded-2xl border {{ $flash['border'] }} bg-white px-4 py-3 shadow-sm dark:bg-slate-900">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $flash['iconBg'] }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $flash['icon'] !!}</svg>
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
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
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
