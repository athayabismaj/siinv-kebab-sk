@extends('layouts.app')

@section('title', 'Arsip User')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-8">
    <h1 class="text-xl font-semibold text-slate-800 dark:text-white">
        Arsip User
    </h1>

    <a href="{{ route('owner.users.index') }}"
       class="text-sm text-slate-400 hover:text-blue-600 transition">
        ← Kembali
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 text-sm bg-green-50 text-green-700 
                border border-green-200 rounded-lg">
        {{ session('success') }}
    </div>
@endif


{{-- ================= MOBILE (CARD) ================= --}}
<div class="space-y-4 sm:hidden">

    @forelse($users as $user)

        <div class="bg-white dark:bg-slate-900 
                    border border-slate-200 dark:border-slate-800 
                    rounded-xl p-5">

            <div class="flex justify-between items-start mb-3">
                <div>
                    <div class="font-medium text-slate-800 dark:text-white">
                        {{ $user->name }}
                    </div>
                    <div class="text-xs text-slate-400">
                        {{ $user->username }}
                    </div>
                </div>

                <span class="text-xs text-slate-400">
                    Nonaktif
                </span>
            </div>

            <div class="text-xs text-slate-400 space-y-1 mb-4">
                <div>{{ $user->email }}</div>
                <div class="capitalize">{{ $user->role->name }}</div>
                <div>Nonaktif {{ $user->deleted_at->format('d M Y') }}</div>
            </div>

            <form action="{{ route('owner.users.restore', $user->id) }}"
                  method="POST">
                @csrf
                @method('PATCH')

                <button type="submit"
                        onclick="return confirm('Aktifkan kembali user ini?')"
                        class="text-sm text-slate-500 hover:text-blue-600 transition">
                    Aktifkan
                </button>
            </form>

        </div>

    @empty

        <div class="text-center text-slate-400 text-sm py-10">
            Tidak ada user nonaktif.
        </div>

    @endforelse

</div>



{{-- ================= DESKTOP (TABLE) ================= --}}
<div class="hidden sm:block 
            bg-white dark:bg-slate-900 
            border border-slate-200 dark:border-slate-800 
            rounded-xl overflow-hidden">

    <table class="min-w-full text-sm">

        <thead class="text-xs uppercase text-slate-400 
                      border-b border-slate-200 dark:border-slate-800">
            <tr>
                <th class="px-6 py-4 text-left">Nama</th>
                <th class="px-6 py-4 text-left">Username</th>
                <th class="px-6 py-4 text-left">Email</th>
                <th class="px-6 py-4 text-left">Role</th>
                <th class="px-6 py-4 text-left">Nonaktif</th>
                <th class="px-6 py-4 text-left">Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($users as $user)

                <tr class="border-b border-slate-100 dark:border-slate-800">

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
                        {{ $user->deleted_at->format('d M Y') }}
                    </td>

                    <td class="px-6 py-4">
                        <form action="{{ route('owner.users.restore', $user->id) }}"
                              method="POST">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    onclick="return confirm('Aktifkan kembali user ini?')"
                                    class="text-sm text-slate-500 hover:text-blue-600 transition">
                                Aktifkan
                            </button>
                        </form>
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-slate-400 text-sm">
                        Tidak ada user nonaktif.
                    </td>
                </tr>

            @endforelse
        </tbody>

    </table>

</div>


@if(method_exists($users, 'links'))
    <div class="mt-8 text-sm">
        {{ $users->links() }}
    </div>
@endif

@endsection