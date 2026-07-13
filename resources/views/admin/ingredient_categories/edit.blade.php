@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Kategori Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Edit Kategori" 
        subtitle="Perbarui informasi nama kategori bahan baku." 
        breadcrumb-parent="Kategori Bahan" 
        breadcrumb-child="Edit">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.ingredient_categories.partials.form', [
        'action' => route('admin.ingredient-categories.update', $ingredientCategory->id),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan',
        'ingredientCategory' => $ingredientCategory
    ])

</div>
@endsection