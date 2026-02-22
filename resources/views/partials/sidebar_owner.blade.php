<aside class="w-64 bg-blue-700 text-white p-6 hidden md:block">

    <h2 class="text-xl font-bold mb-8">Owner Panel</h2>

    <ul class="space-y-4 text-sm">
        <li>
            <a href="{{ route('owner.panel') }}"
               class="block hover:text-blue-200 transition">
                Dashboard
            </a>
        </li>
        <li>
            <a href="{{ route('owner.users.index') }}"
                class="block hover:text-blue-200 transition">
                User Management
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