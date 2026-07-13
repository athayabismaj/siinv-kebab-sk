@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Kategori Menu')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Edit Kategori Menu" 
        subtitle="Perbarui informasi nama kategori menu yang sudah ada di sistem." 
        breadcrumb-parent="Kategori Menu" 
        breadcrumb-child="Edit Kategori Menu">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.menu_categories.partials.form', [
        'action' => route('admin.menu-categories.update', $menuCategory->id),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan',
        'category' => $menuCategory
    ])

</div>
@endsection