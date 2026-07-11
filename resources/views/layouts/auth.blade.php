<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @hasSection('title')
            @yield('title') | Kebab SK
        @else
            Autentikasi | Kebab SK
        @endif
    </title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="h-full bg-slate-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-xl font-semibold text-slate-800">
                    Sistem Inventory Kebab SK
                </h1>
            </div>

            @include('partials.flash_alerts', ['includeErrors' => true])
            @yield('content')
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">
            &copy; {{ date('Y') }} Sistem Inventory Kebab SK
        </p>
    </div>
</body>
</html>
