@extends('layouts.app')

@section('title', 'Tambah Cabang')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
<div class="space-y-8">
    <x-page-header 
        title="Tambah Cabang" 
        subtitle="Tambahkan cabang operasional agar admin dan kasir dapat dipetakan ke lokasi kerja yang sesuai." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Tambah Cabang">
    </x-page-header>

    @include('owner.branches.partials.form', [
        'title' => 'Cabang Baru',
        'action' => route('owner.branches.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Cabang',
        'branch' => $branch,
    ])
</div>
@endsection
