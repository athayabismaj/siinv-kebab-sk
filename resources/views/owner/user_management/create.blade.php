@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
{{-- Perbaikan: max-w-3xl mx-auto dihapus, diganti w-full biar merentang ke samping --}}
<div class="space-y-8 w-full">

    {{-- ════ HEADER ════ --}}
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <a href="{{ route('owner.users.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Pengguna</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Tambah</span>
        </nav>

        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
            Tambah Pengguna
        </h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
            Daftarkan pengguna atau kasir baru ke dalam sistem. Pastikan email yang dimasukkan <br class="hidden sm:block mt-1">aktif dan role yang dipilih sesuai dengan tugas mereka.
        </p>
    </div>

    {{-- ════ FORM PARTIAL ════ --}}
    @include('owner.user_management.partials.form', [
        'action' => route('owner.users.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Pengguna Baru',
        'showPassword' => true,
        'showConfirmPassword' => false
    ])

</div>
@endsection