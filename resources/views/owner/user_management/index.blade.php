@extends('layouts.app')

@section('title', 'User Management')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">User Management</h1>

    {{-- Tombol Tambah --}}
    <a href="{{ route('owner.users.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
        + Tambah User
    </a>
</div>

<div class="bg-white shadow rounded-xl overflow-hidden">

    <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-4 text-left">Nama</th>
                <th class="p-4 text-left">Username</th>
                <th class="p-4 text-left">Role</th>
                <th class="p-4 text-left">Status</th>
                <th class="p-4 text-left">Dibuat</th>
                <th class="p-4 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-4">{{ $user->name }}</td>
                    <td class="p-4">{{ $user->username }}</td>
                    <td class="p-4 capitalize">{{ $user->role->name }}</td>
                    <td class="p-4">
                        @if($user->deleted_at)
                            <span class="px-2 py-1 text-xs bg-red-100 text-red-600 rounded">
                                Nonaktif
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-600 rounded">
                                Aktif
                            </span>
                        @endif
                    </td>
                    <td class="p-4">
                        {{ $user->created_at->format('d M Y') }}
                    </td>
                    <td class="p-4 space-x-2">

                        {{-- Tombol Edit --}}
                        <a href="{{ route('owner.users.edit', $user->id) }}"
                           class="text-blue-600 hover:underline">
                            Edit
                        </a>

                        {{-- Tombol Nonaktifkan --}}
                        <form action="{{ route('owner.users.destroy', $user->id) }}"
                              method="POST"
                              class="inline-block">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    onclick="return confirm('Yakin ingin menonaktifkan user ini?')"
                                    class="text-red-600 hover:underline">
                                Nonaktifkan
                            </button>
                        </form>

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-500">
                        Tidak ada data user.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>

{{-- Pagination --}}
<div class="mt-6">
    {{ $users->links() }}
</div>

@endsection