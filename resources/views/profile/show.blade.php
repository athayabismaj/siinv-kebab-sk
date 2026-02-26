@extends('layouts.app')

@section('sidebar')
    @if(auth()->user()->role->name === 'owner')
        @include('partials.sidebar_owner')
    @else
        @include('partials.sidebar_admin')
    @endif
@endsection

@section('content')

<div class="w-full">

    {{-- HEADER --}}
    <div class="mb-10">
        <h1 class="text-2xl md:text-3xl font-semibold text-slate-800 dark:text-white">
            Profile
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">
            Kelola informasi akun Anda
        </p>
    </div>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="mb-6 px-5 py-4 text-sm
                    bg-emerald-50 dark:bg-emerald-900/30
                    text-emerald-600 dark:text-emerald-300
                    border border-emerald-200 dark:border-emerald-800
                    rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    {{-- CARD FULL WIDTH --}}
    <div class="bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800
                rounded-2xl p-8 md:p-12 shadow-sm">

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                {{-- Nama --}}
                <div>
                    <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                        Nama
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $user->name) }}"
                           required
                           class="w-full px-4 py-3 rounded-xl
                                  border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-slate-800
                                  text-slate-700 dark:text-white
                                  focus:ring-2 focus:ring-blue-500
                                  focus:border-blue-500 outline-none transition">
                </div>

                {{-- Username --}}
                <div>
                    <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                        Username
                    </label>
                    <input type="text"
                           name="username"
                           value="{{ old('username', $user->username) }}"
                           required
                           class="w-full px-4 py-3 rounded-xl
                                  border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-slate-800
                                  text-slate-700 dark:text-white
                                  focus:ring-2 focus:ring-blue-500
                                  focus:border-blue-500 outline-none transition">
                </div>

                {{-- Email --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                        Email
                    </label>
                    <input type="email"
                           name="email"
                           value="{{ old('email', $user->email) }}"
                           required
                           class="w-full px-4 py-3 rounded-xl
                                  border border-slate-300 dark:border-slate-700
                                  bg-white dark:bg-slate-800
                                  text-slate-700 dark:text-white
                                  focus:ring-2 focus:ring-blue-500
                                  focus:border-blue-500 outline-none transition">
                </div>

                {{-- Role --}}
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                        Role
                    </label>
                    <input type="text"
                           value="{{ ucfirst($user->role->name) }}"
                           disabled
                           class="w-full px-4 py-3 rounded-xl
                                  border border-slate-300 dark:border-slate-700
                                  bg-slate-100 dark:bg-slate-800
                                  text-slate-500 dark:text-slate-400">
                </div>

            </div>

            {{-- BUTTONS --}}
            <div class="flex justify-end gap-4 mt-10">
                <a href="{{ auth()->user()->role->name === 'owner' 
                            ? route('owner.panel') 
                            : route('admin.panel') }}"
                   class="px-6 py-3 rounded-xl
                          bg-slate-200 dark:bg-slate-700
                          text-slate-700 dark:text-slate-200
                          hover:bg-slate-300 dark:hover:bg-slate-600
                          transition">
                    Batal
                </a>

                <button type="submit"
                        class="px-6 py-3 rounded-xl
                               bg-blue-600 text-white
                               hover:bg-blue-700
                               transition">
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>

</div>

@endsection