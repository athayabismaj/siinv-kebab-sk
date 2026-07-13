@extends('layouts.app')

@section('title', 'Edit Cabang')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
<div class="space-y-8">
    <x-page-header 
        title="Edit Cabang" 
        subtitle="Perbarui identitas cabang. Perubahan ini akan dipakai pada pilihan cabang pengguna." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Edit Cabang">
    </x-page-header>

    @include('owner.branches.partials.form', [
        'title' => $branch->name,
        'action' => route('owner.branches.update', $branch),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan',
        'branch' => $branch,
    ])
</div>
@endsection
