@extends('layouts.app')

@section('title', 'Arsip User')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Arsip User (Nonaktif)</h1>

    <a href="{{ route('owner.users.index') }}"
       class="text-blue-600 hover:underline">
        ← Kembali ke User Aktif
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="bg-white shadow rounded-xl overflow-hidden">

    <table class="min-w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-4 text-left">Nama</th>
                <th class="p-4 text-left">Username</th>
                <th class="p-4 text-left">Role</th>
                <th class="p-4 text-left">Dinonaktifkan</th>
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
                        {{ $user->deleted_at->format('d M Y H:i') }}
                    </td>
                    <td class="p-4">

                        <form action="{{ route('owner.users.restore', $user->id) }}"
                              method="POST">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    onclick="return confirm('Aktifkan kembali user ini?')"
                                    class="text-green-600 hover:underline">
                                Aktifkan
                            </button>
                        </form>

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="p-4 text-center text-gray-500">
                        Tidak ada user nonaktif.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>

<div class="mt-6">
    {{ $users->links() }}
</div>

@endsection