@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Manajemen Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.ingredients.partials.index.header')
    @include('admin.ingredients.partials.index.alerts')
    @include('admin.ingredients.partials.index.filters')
    @include('admin.ingredients.partials.index.table')
    @include('admin.ingredients.partials.index.pagination')
</div>
@endsection
