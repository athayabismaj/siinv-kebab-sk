@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<h1 class="text-2xl font-bold mb-6">Edit User</h1>

<form method="POST" action="{{ route('owner.users.update', $user->id) }}" class="space-y-4">
    @csrf
    @method('PUT')

    <input type="text" name="name" value="{{ $user->name }}"
           class="border px-4 py-2 rounded w-full">

    <input type="text" name="username" value="{{ $user->username }}"
           class="border px-4 py-2 rounded w-full">

    <input type="password" name="password"
           placeholder="Kosongkan jika tidak ingin ubah password"
           class="border px-4 py-2 rounded w-full">

    <select name="role_id" class="border px-4 py-2 rounded w-full">
        @foreach($roles as $role)
            <option value="{{ $role->id }}"
                {{ $user->role_id == $role->id ? 'selected' : '' }}>
                {{ ucfirst($role->name) }}
            </option>
        @endforeach
    </select>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Update
    </button>
</form>

@endsection