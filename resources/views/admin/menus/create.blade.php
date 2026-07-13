@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Tambah Menu')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Tambah Menu" 
        subtitle="Tambahkan menu utama baru ke sistem. Anda dapat menambahkan harga dan resep bahan pada bagian kelola Variant nanti." 
        breadcrumb-parent="Manajemen Menu" 
        breadcrumb-child="Tambah Menu">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.menus.partials.form', [
        'action' => route('admin.menus.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Menu',
        'menu' => null
    ])

</div>
@endsection