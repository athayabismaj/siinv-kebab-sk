<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $pageTitle = trim($__env->yieldContent('title')); @endphp
    <title>{{ $pageTitle !== '' ? $pageTitle . ' | Kebab SK' : 'Kebab SK | Sistem Inventory' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @stack('styles')
</head>

<body class="app-shell-body font-sans antialiased selection:bg-blue-500/30 bg-slate-50 dark:bg-slate-950 text-slate-700 dark:text-slate-200 overflow-x-hidden relative transition-colors duration-300">

{{-- DECORATIVE BACKGROUND BLOBS --}}
<div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-blue-500/5 dark:bg-blue-500/10 blur-3xl mix-blend-multiply dark:mix-blend-lighten"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-[35%] h-[35%] rounded-full bg-emerald-500/5 dark:bg-emerald-500/10 blur-3xl mix-blend-multiply dark:mix-blend-lighten"></div>
</div>

<div x-data="{ sidebarOpen: false }" class="app-shell-root flex flex-1 w-full relative z-10">

    {{-- OVERLAY MOBILE --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 bg-black/40 md:hidden z-40">
    </div>

    {{-- SIDEBAR --}}
    @php
        $roleName = strtolower(optional(optional(auth()->user())->role)->name ?? '');
        $useOwnerSidebar = $roleName === 'owner' || request()->routeIs('owner.*');
        $useDeveloperSidebar = $roleName === 'developer' || request()->routeIs('developer.*');
    @endphp

    @if($useDeveloperSidebar)
        @include('partials.sidebar_developer')
    @elseif($useOwnerSidebar)
        @include('partials.sidebar_owner')
    @else
        @include('partials.sidebar_admin')
    @endif

    {{-- RIGHT SIDE --}}
    <div class="app-shell-content flex-1 flex flex-col w-full">

        {{-- HEADER --}}
        <div class="shrink-0">
            @include('partials.header')
        </div>

        {{-- SCROLLABLE CONTENT --}}
        <main data-app-main class="app-shell-main flex-1 overflow-y-auto p-6 md:p-8">
            @unless(trim($__env->yieldContent('disableGlobalAlerts')) === 'true')
                @include('partials.flash_alerts', ['class' => 'w-full space-y-2', 'position' => 'global'])
            @endunless
            @yield('content')
        </main>

        {{-- FOOTER (always pinned at bottom) --}}
        <div class="shrink-0 relative z-20">
            @include('partials.footer')
        </div>

    </div>

</div>

@stack('scripts')
</body>
</html>
