<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @yield('title', 'Sistem Inventory')
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    @yield('sidebar')

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col">

        <main class="flex-1 p-6 md:p-10">
            @yield('content')
        </main>

        {{-- Footer --}}
        @include('partials.footer')

    </div>

</div>

</body>
</html>