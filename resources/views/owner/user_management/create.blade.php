@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

<div class="w-full">

    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            Tambah User
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Tambahkan pengguna baru ke dalam sistem
        </p>
    </div>

    @include('owner.user_management.partials.form', [
        'action' => route('owner.users.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan User',
        'showPassword' => true,
        'showConfirmPassword' => false
    ])

</div>

@endsection