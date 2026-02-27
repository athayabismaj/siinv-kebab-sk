@extends('layouts.auth')

@section('content')

<div class="max-w-md mx-auto">

    <h2 class="text-2xl font-bold mb-6 text-center">
        Reset Password
    </h2>

    <form method="POST" action="{{ route('password.reset') }}">
        @csrf

        <div class="mb-4">
            <label>Password Baru</label>
            <input type="password"
                   name="password"
                   required
                   class="w-full px-4 py-2 border rounded">
        </div>

        <div class="mb-4">
            <label>Konfirmasi Password</label>
            <input type="password"
                   name="password_confirmation"
                   required
                   class="w-full px-4 py-2 border rounded">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded">
            Reset Password
        </button>

    </form>

</div>

@endsection