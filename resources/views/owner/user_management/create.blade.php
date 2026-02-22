@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<h1 class="text-2xl font-bold mb-6">Tambah User</h1>

<form method="POST" action="{{ route('owner.users.store') }}" class="space-y-4">
    @csrf

    <input type="text" name="name" placeholder="Nama"
           class="border px-4 py-2 rounded w-full">

    <input type="text" name="username" placeholder="Username"
           class="border px-4 py-2 rounded w-full">

    <input type="password" name="password" placeholder="Password"
           class="border px-4 py-2 rounded w-full">

    <select name="role_id" class="border px-4 py-2 rounded w-full">
        @foreach($roles as $role)
            <option value="{{ $role->id }}">
                {{ ucfirst($role->name) }}
            </option>
        @endforeach
    </select>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Simpan
    </button>
</form>

@endsection