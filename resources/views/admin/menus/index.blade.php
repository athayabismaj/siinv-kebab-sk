@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

{{-- ================= HEADER ================= --}}
<div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-center gap-4">

    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            Manajemen Menu
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Kelola daftar menu yang tersedia
        </p>
    </div>

    <a href="{{ route('admin.menus.create') }}"
       class="inline-flex items-center justify-center
              bg-blue-600 hover:bg-blue-700
              text-white px-5 py-2.5 rounded-xl
              text-sm font-medium transition">
        + Tambah Menu
    </a>

</div>


@if(session('success'))
    <div class="mb-6 p-3 text-sm rounded-xl
                bg-green-50 text-green-700
                border border-green-200">
        {{ session('success') }}
    </div>
@endif


{{-- ================= MAIN CARD ================= --}}
<div class="bg-white dark:bg-slate-900
            rounded-2xl border border-slate-200 dark:border-slate-800
            shadow-sm overflow-hidden">


    {{-- ================= MOBILE VIEW ================= --}}
    <div class="block md:hidden divide-y divide-slate-200 dark:divide-slate-800">

        @forelse($menus as $menu)

            <div class="p-5">

                <div class="flex gap-4">

                    {{-- IMAGE --}}
                    @if($menu->image_path)
                        <img src="{{ asset('storage/'.$menu->image_path) }}"
                             class="w-16 h-16 object-cover rounded-lg">
                    @else
                        <div class="w-16 h-16 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                    @endif

                    {{-- INFO --}}
                    <div class="flex-1">

                        <div class="font-medium text-slate-800 dark:text-white">
                            {{ $menu->name }}
                        </div>

                        <div class="text-sm text-slate-500 mt-1">
                            Rp {{ number_format($menu->price, 0, ',', '.') }}
                        </div>

                        <div class="flex gap-4 mt-3 text-sm">

                            <a href="{{ route('admin.menus.edit', $menu->id) }}"
                               class="text-blue-600 hover:underline">
                                Edit
                            </a>

                            <form action="{{ route('admin.menus.destroy', $menu->id) }}"
                                  method="POST">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        onclick="return confirm('Yakin ingin menghapus menu ini?')"
                                        class="text-red-600 hover:underline">
                                    Hapus
                                </button>
                            </form>

                        </div>

                    </div>

                </div>

            </div>

        @empty

            <div class="p-10 text-center text-slate-500">
                Tidak ada data menu.
            </div>

        @endforelse

    </div>



    {{-- ================= DESKTOP TABLE ================= --}}
    <div class="hidden md:block overflow-x-auto">

        <table class="min-w-full text-sm">

            <thead class="text-xs uppercase text-slate-400
                          border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-6 py-4 text-left">Foto</th>
                    <th class="px-6 py-4 text-left">Nama</th>
                    <th class="px-6 py-4 text-left">Harga</th>
                    <th class="px-6 py-4 text-left">Aksi</th>
                </tr>
            </thead>

            <tbody>

            @forelse($menus as $menu)

                <tr class="border-b border-slate-100 dark:border-slate-800
                           hover:bg-slate-50 dark:hover:bg-slate-800 transition">

                    {{-- FOTO --}}
                    <td class="px-6 py-4">
                        @if($menu->image_path)
                            <img src="{{ asset('storage/'.$menu->image_path) }}"
                                 class="w-16 h-16 object-cover rounded-lg">
                        @else
                            <div class="w-16 h-16 bg-slate-200 dark:bg-slate-700 rounded-lg"></div>
                        @endif
                    </td>

                    {{-- NAMA --}}
                    <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">
                        {{ $menu->name }}
                    </td>

                    {{-- HARGA --}}
                    <td class="px-6 py-4 text-slate-500">
                        Rp {{ number_format($menu->price, 0, ',', '.') }}
                    </td>

                    {{-- AKSI --}}
                    <td class="px-6 py-4">
                        <div class="flex gap-5 text-sm">

                            <a href="{{ route('admin.menus.edit', $menu->id) }}"
                               class="text-blue-600 hover:underline">
                                Edit
                            </a>

                            <form action="{{ route('admin.menus.destroy', $menu->id) }}"
                                  method="POST">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        onclick="return confirm('Yakin ingin menghapus menu ini?')"
                                        class="text-red-600 hover:underline">
                                    Hapus
                                </button>
                            </form>

                        </div>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="4"
                        class="px-6 py-12 text-center text-slate-500">
                        Tidak ada data menu.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>


@if(method_exists($menus, 'links'))
    <div class="mt-8">
        {{ $menus->links() }}
    </div>
@endif

@endsection