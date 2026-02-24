<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem Inventory')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-slate-100 dark:bg-slate-950">

<div x-data="{ sidebarOpen: false }" class="flex min-h-screen">

    {{-- SIDEBAR --}}
    @yield('sidebar')

    {{-- RIGHT SIDE --}}
    <div class="flex-1 flex flex-col">

        {{-- HEADER --}}
        @include('partials.header')

        {{-- CONTENT --}}
        <main class="flex-1 p-8">
            @yield('content')
        </main>

        {{-- FOOTER --}}
        @include('partials.footer')

    </div>

</div>

</body>
</html>