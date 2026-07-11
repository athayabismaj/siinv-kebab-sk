@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Arsip Menu')

@push('styles')
<style>
    .archive-filter-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: .75rem;
    }

    @media (min-width: 1024px) {
        .archive-filter-row {
            grid-template-columns: minmax(0, 4fr) minmax(220px, 1fr);
            align-items: center;
        }
    }
</style>
@endpush

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">
    @include('admin.menus.partials.archive.header')
    @include('admin.menus.partials.archive.alerts')
    @include('admin.menus.partials.archive.filters')
    @include('admin.menus.partials.archive.table')
</div>
@endsection
