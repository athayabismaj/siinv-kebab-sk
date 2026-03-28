<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Inventory')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-700 dark:text-slate-200 overflow-x-hidden" style="height: 100dvh; display: flex; flex-direction: column;">

<div x-data="{ sidebarOpen: false }" class="flex flex-1 w-full relative" style="min-height: 0; overflow: hidden;">

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
    @endphp

    @if($useOwnerSidebar)
        @include('partials.sidebar_owner')
    @else
        @include('partials.sidebar_admin')
    @endif

    {{-- RIGHT SIDE --}}
    <div class="flex-1 flex flex-col w-full" style="min-height: 0; overflow: hidden;">

        {{-- HEADER --}}
        <div class="shrink-0">
            @include('partials.header')
        </div>

        {{-- SCROLLABLE CONTENT --}}
        <main class="flex-1 overflow-y-auto p-6 md:p-8" style="min-height: 0;">
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
