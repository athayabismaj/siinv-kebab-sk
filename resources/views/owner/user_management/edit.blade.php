@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
{{-- Perbaikan: max-w-3xl mx-auto dihapus, diganti w-full biar merentang ke samping --}}
<div class="space-y-8 w-full">

    <div class="mb-8">
        <nav class="mb-3 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 sm:text-[11px]">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <a href="{{ route('owner.users.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Pengguna</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Edit</span>
        </nav>

        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white mb-2">
            Edit Pengguna
        </h1>

        <p class="text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400 max-w-3xl">
            Perbarui informasi profil atau ganti hak akses (role) pengguna ini. <br class="hidden sm:block mt-1">Untuk mengganti kata sandi, silakan gunakan fitur Atur Ulang Kata Sandi di halaman sebelumnya.
        </p>
    </div>

    @include('owner.user_management.partials.form', [
        'action' => route('owner.users.update', $user->id),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan',
        'showPassword' => false,
        'showConfirmPassword' => false,
        'showRole' => true,
        'roles' => $roles,
        'user' => $user
    ])

</div>
@endsection
