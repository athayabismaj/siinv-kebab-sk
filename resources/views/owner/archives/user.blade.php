@extends('layouts.app')

@section('title', 'Arsip User')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

{{-- HEADER --}}
<div class="mb-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">

        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
                Arsip User (Nonaktif)
            </h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Daftar user yang telah dinonaktifkan
            </p>
        </div>

        <a href="{{ route('owner.users.index') }}"
           class="text-sm text-slate-500 hover:text-blue-600 transition">
            ← Kembali
        </a>

    </div>
</div>


@if(session('success'))
    <div class="mb-6 p-3 text-sm rounded-xl
                bg-green-50 text-green-700
                border border-green-200">
        {{ session('success') }}
    </div>
@endif


{{-- MAIN CARD --}}
<div class="bg-white dark:bg-slate-900
            rounded-2xl border border-slate-200 dark:border-slate-800
            shadow-sm overflow-hidden">


    {{-- ================= MOBILE VIEW ================= --}}
    <div class="block md:hidden divide-y divide-slate-200 dark:divide-slate-800">

        @forelse($users as $user)

            <div class="p-5">

                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-medium text-slate-800 dark:text-white">
                            {{ $user->name }}
                        </div>
                        <div class="text-xs text-slate-400">
                            {{ $user->username }}
                        </div>
                    </div>

                    <span class="text-xs px-2 py-1 rounded-full
                                 bg-red-100 text-red-600">
                        Nonaktif
                    </span>
                </div>

                <div class="mt-3 text-sm text-slate-500 space-y-1">
                    <div>{{ $user->email }}</div>
                    <div class="capitalize">{{ $user->role->name }}</div>
                    <div class="text-xs text-slate-400">
                        Nonaktif {{ optional($user->deleted_at)->format('d M Y') }}
                    </div>
                </div>

                <div class="mt-4">
                    <form action="{{ route('owner.users.restore', $user->id) }}"
                          method="POST">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                                onclick="return confirm('Aktifkan kembali user ini?')"
                                class="text-sm text-blue-600 hover:underline transition">
                            Aktifkan
                        </button>
                    </form>
                </div>

            </div>

        @empty

            <div class="p-10 text-center text-slate-500">
                Tidak ada user nonaktif.
            </div>

        @endforelse

    </div>



    {{-- ================= DESKTOP TABLE ================= --}}
    <div class="hidden md:block overflow-x-auto">

        <table class="min-w-full text-sm">

            <thead class="text-xs uppercase text-slate-400
                          border-b border-slate-200 dark:border-slate-800">
                <tr>
                    <th class="px-6 py-4 text-left">NAMA</th>
                    <th class="px-6 py-4 text-left">USERNAME</th>
                    <th class="px-6 py-4 text-left">EMAIL</th>
                    <th class="px-6 py-4 text-left">ROLE</th>
                    <th class="px-6 py-4 text-left">DINONAKTIFKAN</th>
                    <th class="px-6 py-4 text-left">Aksi</th>
                </tr>
            </thead>

            <tbody>

            @forelse($users as $user)

                <tr class="border-b border-slate-100 dark:border-slate-800
                           hover:bg-slate-50 dark:hover:bg-slate-800 transition">

                    <td class="px-6 py-4 font-medium text-slate-800 dark:text-white">
                        {{ $user->name }}
                    </td>

                    <td class="px-6 py-4 text-slate-500">
                        {{ $user->username }}
                    </td>

                    <td class="px-6 py-4 text-slate-500">
                        {{ $user->email }}
                    </td>

                    <td class="px-6 py-4 text-slate-500 capitalize">
                        {{ $user->role->name }}
                    </td>

                    <td class="px-6 py-4 text-xs text-slate-400">
                        {{ optional($user->deleted_at)->format('d M Y') }}
                    </td>

                    <td class="px-6 py-4">
                        <form action="{{ route('owner.users.restore', $user->id) }}"
                              method="POST">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    onclick="return confirm('Aktifkan kembali user ini?')"
                                    class="text-blue-600 hover:underline transition">
                                Aktifkan
                            </button>
                        </form>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="6"
                        class="px-6 py-12 text-center text-slate-500">
                        Tidak ada user nonaktif.
                    </td>
                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>


@if(method_exists($users, 'links'))
    <div class="mt-8">
        {{ $users->links() }}
    </div>
@endif

@endsection