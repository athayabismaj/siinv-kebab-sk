@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Tambah Kategori Menu')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Tambah Kategori Menu" 
        subtitle="Tambahkan kategori baru untuk mengelompokkan menu agar memudahkan kasir saat transaksi." 
        breadcrumb-parent="Kategori Menu" 
        breadcrumb-child="Tambah Kategori Menu">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.menu_categories.partials.form', [
        'action' => route('admin.menu-categories.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Kategori',
        'category' => null
    ])

</div>
@endsection