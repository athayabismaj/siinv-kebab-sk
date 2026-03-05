@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="w-full">

    <div class="mb-10">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
            Tambah Kategori
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Tambahkan kategori baru untuk bahan
        </p>
    </div>

    @include('admin.ingredient_categories.partials.form', [
        'action' => route('admin.ingredient-categories.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Kategori'
    ])

</div>

@endsection