@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="w-full">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            Reset Password
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Menyetel ulang password pengguna sistem
        </p>
    </div>

    <div class="bg-white dark:bg-slate-900 
                shadow-lg rounded-2xl 
                border border-slate-200 dark:border-slate-800 
                p-8">

        <form method="POST" action="{{ route('owner.users.resetPassword', $user->id) }}">
            @csrf

            <div class="grid grid-cols-1 gap-6">

                {{-- Password --}}
                <div>
                    <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                        Password Baru
                    </label>
                    <input type="password"
                           name="password"
                           required
                           class="w-full px-4 py-2 rounded-xl
                                  border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-slate-800
                                  text-slate-700 dark:text-white
                                  focus:ring-2 focus:ring-blue-500
                                  focus:border-blue-500 outline-none transition">
                </div>

                {{-- Konfirmasi --}}
                <div>
                    <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                        Konfirmasi Password
                    </label>
                    <input type="password"
                           name="password_confirmation"
                           required
                           class="w-full px-4 py-2 rounded-xl
                                  border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-slate-800
                                  text-slate-700 dark:text-white
                                  focus:ring-2 focus:ring-blue-500
                                  focus:border-blue-500 outline-none transition">
                </div>

            </div>

            <div class="flex justify-end gap-3 mt-8">

                <a href="{{ route('owner.users.index') }}"
                   class="px-5 py-2 rounded-xl 
                          bg-slate-100 hover:bg-slate-200
                          dark:bg-slate-800 dark:hover:bg-slate-700
                          text-slate-600 dark:text-slate-300
                          text-sm transition">
                    Batal
                </a>

                <button type="submit"
                        class="px-6 py-2 rounded-xl
                               bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-medium
                               shadow-md transition">
                    Reset Password
                </button>

            </div>

        </form>

    </div>

</div>

@endsection