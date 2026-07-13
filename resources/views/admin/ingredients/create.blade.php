@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Tambah Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Tambah Bahan Baku" 
        subtitle="Tambahkan bahan baku baru ke dalam sistem inventory lengkap dengan aturan satuan dan minimum stok." 
        breadcrumb-parent="Manajemen Bahan" 
        breadcrumb-child="Tambah Bahan">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.ingredients.partials.form', [
        'action' => route('admin.ingredients.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Bahan',
        'ingredient' => null,
        'categories' => $categories
    ])

</div>
@endsection