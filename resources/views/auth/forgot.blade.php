@extends('layouts.auth')

@section('content')

    <h2 class="text-2xl font-bold mb-6 text-center text-slate-800">
        Forgot Password
    </h2>

    @if(session('success'))
        <div class="mb-4 p-3 rounded-lg text-sm
                    bg-green-50 text-green-700 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-3 rounded-lg text-sm
                    bg-red-50 text-red-600 border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.sendOtp') }}" class="space-y-5">
        @csrf

        <div>
            <label class="block text-sm text-slate-600 mb-2">
                Email
            </label>
            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   class="w-full px-4 py-2 rounded-lg
                          border border-slate-300
                          focus:outline-none
                          focus:ring-2 focus:ring-blue-500
                          transition">
            @error('email')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded-lg
                       hover:bg-blue-700 transition">
            Kirim OTP
        </button>
    </form>

    {{-- TOMBOL KEMBALI --}}
    <div class="mt-6 text-center">
        <a href="{{ route('login') }}"
           class="text-sm text-slate-500 hover:text-blue-600 transition">
            ← Kembali ke Login
        </a>
    </div>

@endsection