@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
{{-- Perbaikan: max-w-3xl mx-auto dihapus, diganti w-full biar merentang ke samping --}}
<div class="space-y-8 w-full">

    <x-page-header 
        title="Tambah Pengguna" 
        subtitle="Daftarkan pengguna atau kasir baru ke dalam sistem. Pastikan email yang dimasukkan aktif dan role yang dipilih sesuai dengan tugas mereka." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Tambah Pengguna">
    </x-page-header>

    @include('owner.user_management.partials.form', [
        'action' => route('owner.users.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Pengguna Baru',
        'showPassword' => true,
        'showConfirmPassword' => false
    ])

</div>
@endsection
