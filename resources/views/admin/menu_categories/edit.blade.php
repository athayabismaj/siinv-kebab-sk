@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="w-full">

    <div class="mb-10">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
            Edit Kategori Menu
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            Perbarui informasi kategori menu
        </p>
    </div>

    @include('admin.menu_categories.partials.form', [
        'action' => route('admin.menu-categories.update', $menuCategory->id),
        'method' => 'PUT',
        'buttonText' => 'Update Kategori',
        'category' => $menuCategory
    ])

</div>

@endsection