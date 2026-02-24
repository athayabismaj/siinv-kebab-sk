@extends('layouts.app')

@section('title', 'User Management')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
    <h1 class="text-2xl font-bold">User Management</h1>

    <a href="{{ route('owner.users.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-center">
        + Tambah User
    </a>
</div>

<div class="bg-white shadow rounded-xl overflow-hidden">

    <div class="overflow-x-auto">

        <table class="min-w-full text-sm">

            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-4 text-left">Nama</th>
                    <th class="p-4 text-left hidden sm:table-cell">Username</th>
                    <th class="p-4 text-left hidden md:table-cell">Role</th>
                    <th class="p-4 text-left">Status</th>
                    <th class="p-4 text-left hidden lg:table-cell">Dibuat</th>
                    <th class="p-4 text-left">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($users as $user)
                    <tr class="border-t hover:bg-gray-50 align-top">

                        {{-- MOBILE STACKED INFO --}}
                        <td class="p-4 font-medium">
                            {{ $user->name }}

                            {{-- Username mobile --}}
                            <div class="text-xs text-gray-500 sm:hidden">
                                {{ $user->username }}
                            </div>

                            {{-- Role mobile --}}
                            <div class="text-xs text-gray-400 sm:hidden capitalize">
                                {{ $user->role->name }}
                            </div>
                        </td>

                        {{-- Username desktop --}}
                        <td class="p-4 hidden sm:table-cell">
                            {{ $user->username }}
                        </td>

                        {{-- Role desktop --}}
                        <td class="p-4 capitalize hidden md:table-cell">
                            {{ $user->role->name }}
                        </td>

                        {{-- Status --}}
                        <td class="p-4">
                            @if($user->deleted_at)
                                <span class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded-full">
                                    Nonaktif
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-600 rounded-full">
                                    Aktif
                                </span>
                            @endif
                        </td>

                        {{-- Tanggal desktop --}}
                        <td class="p-4 hidden lg:table-cell">
                            {{ $user->created_at->format('d M Y') }}
                        </td>

                        {{-- Aksi --}}
                        <td class="p-4">
                            <div class="flex flex-col sm:flex-row gap-2">

                                <a href="{{ route('owner.users.edit', $user->id) }}"
                                   class="text-blue-600 hover:underline text-sm">
                                    Edit
                                </a>

                                <form action="{{ route('owner.users.destroy', $user->id) }}"
                                      method="POST">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            onclick="return confirm('Yakin ingin menonaktifkan user ini?')"
                                            class="text-red-600 hover:underline text-sm">
                                        Nonaktifkan
                                    </button>
                                </form>

                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-gray-500">
                            Tidak ada data user.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>

    </div>
</div>

@if(method_exists($users, 'links'))
    <div class="mt-6 flex justify-center sm:justify-start">
        {{ $users->links() }}
    </div>
@endif

@endsection