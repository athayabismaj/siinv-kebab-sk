@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-full overflow-x-hidden">
    @include('admin.transactions.partials.index.header')
    @include('admin.transactions.partials.index.filters')
    @include('admin.transactions.partials.index.groups')
    @include('admin.transactions.partials.index.pagination')
</div>
@endsection
