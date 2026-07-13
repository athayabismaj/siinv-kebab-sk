@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Edit Bahan Baku" 
        subtitle="Perbarui informasi bahan baku, kategori, satuan, dan parameter stok minimum." 
        breadcrumb-parent="Manajemen Bahan" 
        breadcrumb-child="Edit Bahan">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.ingredients.partials.form', [
        'action' => route('admin.ingredients.update', $ingredient->id),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan',
        'ingredient' => $ingredient,
        'categories' => $categories
    ])

</div>
@endsection