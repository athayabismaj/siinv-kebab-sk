@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Tambah Varian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Tambah Varian" 
        subtitle="Tambahkan pilihan varian baru untuk menu {{ $menu->name }}." 
        breadcrumb-parent="Varian Menu" 
        breadcrumb-child="Tambah Varian">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.menu_variants.partials.form', [
        'action' => route('admin.menu-variants.store', $menu->id),
        'method' => 'POST',
        'buttonText' => 'Simpan Varian',
        'menuVariant' => null
    ])

</div>
@endsection