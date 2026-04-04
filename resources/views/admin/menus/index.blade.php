@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Manajemen Menu')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.menus.partials.index.header')
    @include('admin.menus.partials.index.alerts')
    @include('admin.menus.partials.index.table')
</div>
@endsection
