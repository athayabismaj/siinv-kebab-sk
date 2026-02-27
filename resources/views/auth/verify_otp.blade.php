@extends('layouts.auth')

@section('content')

<div class="max-w-md mx-auto otp-page">

    <h2 class="text-2xl font-bold mb-6 text-center">
        Verifikasi OTP
    </h2>

    @if(session('success'))
        <div class="mb-4 text-green-600 text-sm text-center">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 text-red-600 text-sm text-center">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.verifyOtp') }}" id="otpForm">
        @csrf

        <input type="hidden" name="otp" id="otp">

        <div class="flex justify-between gap-2 mb-6">
            @for($i = 0; $i < 6; $i++)
                <input type="text"
                       maxlength="1"
                       class="otp-box w-12 h-12 text-center text-xl border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
                       inputmode="numeric">
            @endfor
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
            Verifikasi
        </button>
    </form>

    <p class="text-sm text-gray-500 mt-4 text-center">
        OTP akan kadaluarsa dalam
        <span id="countdown"
              data-expire="{{ session('otp_expires_at') }}">
        </span> detik
    </p>

    <div class="text-center mt-4">
        <form method="POST" action="{{ route('password.sendOtp') }}">
            @csrf
            <input type="hidden" name="email" value="{{ session('otp_email') }}">
            <button type="submit"
                    id="resendBtn"
                    class="text-sm text-blue-600 hover:underline disabled:opacity-50"
                    disabled>
                Kirim ulang OTP
            </button>
        </form>
    </div>

</div>

@endsection