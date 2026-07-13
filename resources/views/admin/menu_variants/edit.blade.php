@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Varian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Edit Varian" 
        subtitle="Perbarui informasi varian dari menu {{ $menu->name }}." 
        breadcrumb-parent="Varian Menu" 
        breadcrumb-child="Edit Varian">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.menu_variants.partials.form', [
        'action' => route('admin.menu-variants.update', [$menu->id, $menuVariant->id]),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan'
    ])

</div>
@endsection