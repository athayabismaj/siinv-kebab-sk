@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="w-full">

    <div class="mb-6">
        <h1 class="text-2xl font-bold">Edit Bahan</h1>
        <p class="text-sm text-slate-500">
            Perbarui informasi bahan
        </p>
    </div>

    @include('admin.ingredients.partials.form', [
        'action' => route('admin.ingredients.update', $ingredient->id),
        'method' => 'PUT',
        'buttonText' => 'Update Bahan',
        'ingredient' => $ingredient
    ])

</div>

@endsection