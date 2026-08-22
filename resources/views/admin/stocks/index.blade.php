@extends('layouts.app')

@section('title', 'Restok dan Penyesuaian Stok')

@section('content')
<div class="w-full space-y-4 overflow-x-hidden pb-10">
    @include('admin.stocks.partials.index.header')
    @include('admin.stocks.partials.index.alerts')
    @include('admin.stocks.partials.index.filters')
    @include('admin.stocks.partials.index.ingredients')
    @include('admin.stocks.partials.index.pagination')
</div>
@endsection
