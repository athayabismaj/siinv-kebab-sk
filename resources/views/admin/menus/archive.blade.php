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
    
    {{-- ================= TABS NAVIGATION ================= --}}
    <div class="flex rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 w-full mb-2">
        <a href="{{ route('admin.ingredients.archive') }}" class="flex-1 rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Arsip Bahan</a>
        <a href="{{ route('admin.menus.archive') }}" class="flex-1 rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400">Arsip Menu</a>
    </div>
    @include('admin.menus.partials.archive.alerts')
    @include('admin.menus.partials.archive.filters')
    @include('admin.menus.partials.archive.table')
</div>
@endsection
