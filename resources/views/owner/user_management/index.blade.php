@extends('layouts.app')

@section('title', 'User Management')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-8">
    <h1 class="text-xl font-semibold text-slate-800 dark:text-white">
        User Management
    </h1>

    <a href="{{ route('owner.users.create') }}"
       class="text-sm text-slate-500 hover:text-blue-600 transition">
        + Tambah User
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 text-sm bg-green-50 text-green-700 
                border border-green-200 rounded-lg">
        {{ session('success') }}
    </div>
@endif


{{-- ================= MOBILE CARD VIEW ================= --}}
<div class="space-y-4 sm:hidden">

    @forelse($users as $user)
        <div class="bg-white dark:bg-slate-900 
                    border border-slate-200 dark:border-slate-800 
                    rounded-xl p-4">

            <div class="font-medium text-slate-800 dark:text-white">
                {{ $user->name }}
            </div>

            <div class="text-xs text-slate-400 mt-1">
                {{ $user->username }}
            </div>

            <div class="text-xs text-slate-400">
                {{ $user->email }}
            </div>

            <div class="text-xs text-slate-400 capitalize">
                {{ $user->role->name }}
            </div>

            <div class="mt-3 text-xs">
                @if($user->deleted_at)
                    <span class="text-slate-400">Nonaktif</span>
                @else
                    <span class="text-slate-600 dark:text-slate-300">Aktif</span>
                @endif
            </div>

            <div class="mt-4 flex gap-4 text-sm">

                <a href="{{ route('owner.users.edit', $user->id) }}"
                   class="text-slate-500 hover:text-blue-600 transition">
                    Edit
                </a>

                <a href="{{ route('owner.users.reset.form', $user->id) }}"
                   class="text-slate-500 hover:text-blue-600 transition">
                    Reset
                </a>

                <form action="{{ route('owner.users.destroy', $user->id) }}"
                      method="POST">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            onclick="return confirm('Yakin ingin menonaktifkan user ini?')"
                            class="text-slate-500 hover:text-blue-600 transition">
                        Nonaktifkan
                    </button>
                </form>

            </div>
        </div>
    @empty
        <div class="text-center text-slate-400 text-sm py-8">
            Tidak ada data user.
        </div>
    @endforelse

</div>



{{-- ================= DESKTOP TABLE VIEW ================= --}}
<div class="hidden sm:block bg-white dark:bg-slate-900 
            rounded-xl border border-slate-200 dark:border-slate-800">

    <table class="min-w-full text-sm">

        <thead class="text-xs uppercase text-slate-400 border-b border-slate-200 dark:border-slate-800">
            <tr>
                <th class="px-6 py-4 text-left">Nama</th>
                <th class="px-6 py-4 text-left">Username</th>
                <th class="px-6 py-4 text-left">Email</th>
                <th class="px-6 py-4 text-left">Role</th>
                <th class="px-6 py-4 text-left">Status</th>
                <th class="px-6 py-4 text-left">Dibuat</th>
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

                    <td class="px-6 py-4 text-xs">
                        @if($user->deleted_at)
                            <span class="text-slate-400">Nonaktif</span>
                        @else
                            <span class="text-slate-600 dark:text-slate-300">Aktif</span>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-xs text-slate-400">
                        {{ $user->created_at->format('d M Y') }}
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex gap-4 text-sm">

                            <a href="{{ route('owner.users.edit', $user->id) }}"
                               class="text-slate-500 hover:text-blue-600 transition">
                                Edit
                            </a>

                            <a href="{{ route('owner.users.reset.form', $user->id) }}"
                               class="text-slate-500 hover:text-blue-600 transition">
                                Reset
                            </a>

                            <form action="{{ route('owner.users.destroy', $user->id) }}"
                                  method="POST">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        onclick="return confirm('Yakin ingin menonaktifkan user ini?')"
                                        class="text-slate-500 hover:text-blue-600 transition">
                                    Nonaktifkan
                                </button>
                            </form>

                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-center text-slate-400 text-sm">
                        Tidak ada data user.
                    </td>
                </tr>
            @endforelse
        </tbody>

    </table>

</div>


@if(method_exists($users, 'links'))
    <div class="mt-6 text-sm">
        {{ $users->links() }}
    </div>
@endif

@endsection