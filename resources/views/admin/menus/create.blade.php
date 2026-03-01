@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
        Tambah Menu
    </h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
        Tambahkan menu baru ke sistem
    </p>
</div>

@include('admin.menus.partials.form', [
    'action' => route('admin.menus.store'),
    'method' => 'POST',
    'buttonText' => 'Simpan Menu'
])

@endsection