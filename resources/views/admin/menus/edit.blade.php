@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Menu')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    <x-page-header 
        title="Edit Menu" 
        subtitle="Perbarui informasi nama, kategori, atau status menu yang ada di sistem." 
        breadcrumb-parent="Manajemen Menu" 
        breadcrumb-child="Edit Menu">
    </x-page-header>

    {{-- Render Form Partial --}}
    @include('admin.menus.partials.form', [
        'action' => route('admin.menus.update', $menu->id),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan'
    ])

</div>
@endsection
