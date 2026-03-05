@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="w-full">

    {{-- HEADER --}}
    <div class="mb-10">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
            Tambah Bahan
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Tambahkan bahan baru ke dalam sistem inventory
        </p>
    </div>

    @include('admin.ingredients.partials.form', [
        'action' => route('admin.ingredients.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Bahan',
        'ingredient' => null,
        'categories' => $categories
    ])

</div>

@endsection