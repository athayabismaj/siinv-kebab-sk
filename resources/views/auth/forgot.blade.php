@extends('layouts.auth')

@section('content')

<div class="max-w-md mx-auto">

    <h2 class="text-2xl font-bold mb-6 text-center">
        Forgot Password
    </h2>

    @if(session('success'))
        <div class="mb-4 text-green-600 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.sendOtp') }}">
        @csrf

        <div class="mb-4">
            <label>Email</label>
            <input type="email"
                   name="email"
                   required
                   class="w-full px-4 py-2 border rounded">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded">
            Kirim OTP
        </button>

    </form>

</div>

@endsection