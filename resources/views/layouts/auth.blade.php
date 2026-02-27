<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication | Sistem Inventory Kebab SK</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-slate-100 flex items-center justify-center px-4">

    <div class="w-full max-w-md">

        {{-- CARD --}}
        <div class="bg-white rounded-2xl shadow-lg p-8">

            {{-- BRAND --}}
            <div class="text-center mb-8">
                <h1 class="text-xl font-semibold text-slate-800">
                    Sistem Inventory Kebab Sk
                </h1>
            </div>

            {{-- CONTENT --}}
            @yield('content')

        </div>

        {{-- FOOTER --}}
        <p class="text-center text-xs text-slate-400 mt-6">
            © {{ date('Y') }} Sistem Inventory Kebab SK
        </p>

    </div>

</body>
</html>