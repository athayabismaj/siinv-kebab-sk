@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Tambah Kategori Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Tambah Kategori" 
        subtitle="Tambahkan kategori baru untuk mengelompokkan bahan baku Anda dengan rapi." 
        breadcrumb-parent="Kategori Bahan" 
        breadcrumb-child="Tambah">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.ingredient_categories.partials.form', [
        'action' => route('admin.ingredient-categories.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Kategori'
    ])

</div>
@endsection