@extends('layouts.auth')

@section('content')

    <h2 class="text-2xl font-bold mb-6 text-center text-slate-800">
        Atur Ulang Kata Sandi
    </h2>

    <form method="POST" action="{{ route('password.reset') }}" class="space-y-5">
        @csrf

        {{-- Password Baru --}}
        <div>
            <label class="block text-sm text-slate-600 mb-2">
                Kata Sandi Baru
            </label>
            <input type="password"
                   name="password"
                   required
                   class="w-full px-4 py-2.5 rounded-lg
                          border border-slate-300
                          focus:outline-none
                          focus:ring-2 focus:ring-blue-500
                          transition">
            @error('password')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div>
            <label class="block text-sm text-slate-600 mb-2">
                Konfirmasi Kata Sandi
            </label>
            <input type="password"
                   name="password_confirmation"
                   required
                   class="w-full px-4 py-2.5 rounded-lg
                          border border-slate-300
                          focus:outline-none
                          focus:ring-2 focus:ring-blue-500
                          transition">
        </div>

        <button type="submit"
                class="w-full bg-blue-600 text-white py-2.5 rounded-lg
                       hover:bg-blue-700 active:scale-[0.98]
                       transition">
            Atur Ulang Kata Sandi
        </button>
    </form>

@endsection
