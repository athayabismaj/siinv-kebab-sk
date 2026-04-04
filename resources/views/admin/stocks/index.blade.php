@extends('layouts.app')

@section('title', 'Restok dan Penyesuaian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.stocks.partials.index.header')
    @include('admin.stocks.partials.index.alerts')
    @include('admin.stocks.partials.index.filters')
    @include('admin.stocks.partials.index.categories')
    @include('admin.stocks.partials.index.pagination')
</div>
@endsection
