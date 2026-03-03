<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Inventory')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950 text-slate-700 dark:text-slate-200">

<div 
    x-data="{ sidebarOpen: false }"
    class="flex min-h-screen"
>

    {{-- OVERLAY MOBILE --}}
    <div 
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        x-transition.opacity
        class="fixed inset-0 bg-black/40 md:hidden z-40">
    </div>

    {{-- SIDEBAR --}}
    @include('partials.sidebar_admin')

    {{-- RIGHT CONTENT --}}
    <div class="flex-1 flex flex-col">

        {{-- HEADER --}}
        @include('partials.header')

        {{-- CONTENT --}}
        <main class="flex-1 p-6 md:p-8">
            @yield('content')
        </main>

        {{-- FOOTER --}}
        @include('partials.footer')

    </div>

</div>

</body>
</html>