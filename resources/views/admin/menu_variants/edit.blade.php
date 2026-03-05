@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="w-full">

    <div class="mb-10">
        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
            Edit Variant
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {{ $menu->name }}
        </p>
    </div>

    @include('admin.menu_variants.partials.form', [
        'action' => route('admin.menu-variants.update', [$menu->id, $menuVariant->id]),
        'method' => 'PUT',
        'buttonText' => 'Update Variant',
        'menuVariant' => $menuVariant
    ])

</div>

@endsection