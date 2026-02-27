<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication | SK SmartPOS</title>

    @vite('resources/css/app.css')
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-md">

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-xl p-8">

            {{-- Logo / Title --}}
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold text-slate-800">
                    SK SmartPOS & Inventory
                </h1>
            </div>

            {{-- Content --}}
            @yield('content')

        </div>

        {{-- Footer --}}
        <p class="text-center text-xs text-slate-400 mt-6">
            © {{ date('Y') }} SK SmartPOS System
        </p>

    </div>

</body>
</html>