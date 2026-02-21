<!DOCTYPE html>
<html>
<head>
    <title>Panel Owner</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100">

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside class="w-64 bg-blue-700 text-white p-6 hidden md:block">
        <h2 class="text-xl font-bold mb-8">Owner</h2>

        <ul class="space-y-4 text-sm">
            <li>
                <a href="{{ route('owner.panel') }}"
                   class="block hover:text-blue-200 transition">
                    Ringkasan Operasional
                </a>
            </li>
            <li>
                <a href="#" class="block hover:text-blue-200 transition">
                    Laporan Penjualan
                </a>
            </li>
            <li>
                <a href="#" class="block hover:text-blue-200 transition">
                    Monitoring Stok
                </a>
            </li>
        </ul>

        <form method="POST" action="{{ route('logout') }}" class="mt-10">
            @csrf
            <button class="text-sm hover:text-blue-200">
                Logout
            </button>
        </form>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1 p-6 md:p-10">
        @yield('content')
    </main>

</div>

</body>
</html>