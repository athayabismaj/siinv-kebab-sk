@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Arsip Menu')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.menus.partials.archive.header')
    @include('admin.menus.partials.archive.alerts')
    @include('admin.menus.partials.archive.filters')
    @include('admin.menus.partials.archive.table')
</div>
@endsection
