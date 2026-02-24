@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="max-w-2xl mx-auto">

    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            Edit User
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Perbarui informasi pengguna sistem
        </p>
    </div>

    {{-- Card --}}
    <div class="bg-white dark:bg-slate-900 
                shadow-lg rounded-2xl 
                border border-slate-200 dark:border-slate-800 
                p-8">

        <form method="POST" action="{{ route('owner.users.update', $user->id) }}">
            @csrf
            @method('PUT')

            @include('owner.user_management.partials.form')

            {{-- Button Section --}}
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
                    Update User
                </button>

            </div>
        </form>

    </div>

</div>

@endsection