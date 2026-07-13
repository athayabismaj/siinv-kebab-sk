@extends('layouts.app')

@section('title', 'Edit Pengguna')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
{{-- Perbaikan: max-w-3xl mx-auto dihapus, diganti w-full biar merentang ke samping --}}
<div class="space-y-8 w-full">

    <x-page-header 
        title="Edit Pengguna" 
        subtitle="Perbarui informasi profil atau ganti hak akses (role) pengguna ini. Untuk mengganti kata sandi, silakan gunakan fitur Atur Ulang Kata Sandi di halaman sebelumnya." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Edit Pengguna">
    </x-page-header>

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
