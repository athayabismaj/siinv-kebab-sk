@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
        Arsip Bahan (Nonaktif)
    </h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
        Daftar bahan yang telah dinonaktifkan
    </p>
</div>

@if(session('success'))
    <div class="mb-6 p-3 text-sm rounded-xl
                bg-green-50 text-green-700
                border border-green-200">
        {{ session('success') }}
    </div>
@endif


<div class="bg-white dark:bg-slate-900
            rounded-2xl border border-slate-200 dark:border-slate-800
            shadow-sm overflow-hidden">

    {{-- ================= MOBILE VIEW ================= --}}
    <div class="block md:hidden divide-y divide-slate-200 dark:divide-slate-800">

        @forelse($ingredients as $ingredient)

            @php
                $stockRaw = in_array($ingredient->display_unit, ['kg','l'])
                    ? $ingredient->stock / 1000
                    : $ingredient->stock;

                $stock = number_format($stockRaw, 2);
            @endphp

            <div class="p-5">

                <div class="flex justify-between items-start">
                    <div class="font-medium text-slate-800 dark:text-white">
                        {{ $ingredient->name }}
                    </div>

                    <span class="text-xs px-2 py-1 rounded-full
                                 bg-red-100 text-red-600">
                        Nonaktif
                    </span>
                </div>

                <div class="mt-3 text-sm text-slate-500 space-y-1">
                    <div>
                        {{ $stock }} {{ $ingredient->display_unit }}
                    </div>

                    <div class="text-xs text-slate-400">
                        Dinonaktifkan:
                        {{ optional($ingredient->deleted_at)->format('d M Y H:i') }}
                    </div>
                </div>

                <div class="mt-4">
                    <form action="{{ route('owner.ingredients.restore', $ingredient->id) }}"
                          method="POST">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                                onclick="return confirm('Aktifkan kembali bahan ini?')"
                                class="text-sm text-blue-600 hover:underline transition">
                            Aktifkan
                        </button>
                    </form>
                </div>

            </div>

        @empty

            <div class="p-10 text-center text-slate-500">
                Tidak ada bahan nonaktif.
            </div>

        @endforelse

    </div>



    {{-- ================= DESKTOP VIEW ================= --}}
    <div class="hidden md:block overflow-x-auto">

        <table class="min-w-full text-sm">

            <thead class="text-xs uppercase text-slate-400
                          border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-6 py-4 text-left">Nama</th>
                    <th class="px-6 py-4 text-left">Satuan</th>
                    <th class="px-6 py-4 text-left">Stok Terakhir</th>
                    <th class="px-6 py-4 text-left">Dinonaktifkan</th>
                    <th class="px-6 py-4 text-left">Aksi</th>
                </tr>
            </thead>

            <tbody>

            @forelse($ingredients as $ingredient)

                @php
                    $stockRaw = in_array($ingredient->display_unit, ['kg','l'])
                        ? $ingredient->stock / 1000
                        : $ingredient->stock;

                    $stock = number_format($stockRaw, 2);
                @endphp

                <tr class="border-b border-slate-100 dark:border-slate-800
                           hover:bg-slate-50 dark:hover:bg-slate-800 transition">

                    <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">
                        {{ $ingredient->name }}
                    </td>

                    <td class="px-6 py-4 text-slate-500">
                        {{ $ingredient->display_unit }}
                    </td>

                    <td class="px-6 py-4 text-slate-500">
                        {{ $stock }} {{ $ingredient->display_unit }}
                    </td>

                    <td class="px-6 py-4 text-slate-500">
                        {{ optional($ingredient->deleted_at)->format('d M Y H:i') }}
                    </td>

                    <td class="px-6 py-4">
                        <form action="{{ route('owner.ingredients.restore', $ingredient->id) }}"
                              method="POST">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    onclick="return confirm('Aktifkan kembali bahan ini?')"
                                    class="text-blue-600 hover:underline transition">
                                Aktifkan
                            </button>
                        </form>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="5"
                        class="px-6 py-12 text-center text-slate-500">
                        Tidak ada bahan nonaktif.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>


@if(method_exists($ingredients, 'links'))
    <div class="mt-6">
        {{ $ingredients->links() }}
    </div>
@endif

@endsection