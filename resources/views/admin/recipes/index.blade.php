@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Manajemen Resep')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.recipes.partials.index.header')
    @include('admin.recipes.partials.index.alerts')
    @include('admin.recipes.partials.index.filters')
    @include('admin.recipes.partials.index.accordion')
    @include('admin.recipes.partials.index.pagination')
</div>
@endsection
