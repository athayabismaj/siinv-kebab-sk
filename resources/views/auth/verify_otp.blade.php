@extends('layouts.auth')

@section('content')

<div class="max-w-md mx-auto">

    <h2 class="text-2xl font-bold mb-6 text-center">
        Verifikasi OTP
    </h2>

    @if(session('success'))
        <div class="mb-4 text-green-600 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.verifyOtp') }}">
        @csrf

        <div class="mb-4">
            <label>Masukkan OTP</label>
            <input type="text"
                   name="otp"
                   maxlength="6"
                   required
                   class="w-full px-4 py-2 border rounded">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded">
            Verifikasi
        </button>

    </form>

</div>

@endsection