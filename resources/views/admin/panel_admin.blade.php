@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('content')

<div class="space-y-8">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
            Dashboard Admin
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Ringkasan sistem manajemen Kebab SK
        </p>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="bg-white dark:bg-slate-900 
                    border border-slate-200 dark:border-slate-800
                    rounded-2xl p-6 shadow-sm">

            <h2 class="text-sm text-slate-500 dark:text-slate-400">
                Total Menu
            </h2>
            <p class="text-2xl font-bold text-slate-800 dark:text-white mt-2">
                0
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 
                    border border-slate-200 dark:border-slate-800
                    rounded-2xl p-6 shadow-sm">

            <h2 class="text-sm text-slate-500 dark:text-slate-400">
                Total Bahan
            </h2>
            <p class="text-2xl font-bold text-slate-800 dark:text-white mt-2">
                0
            </p>
        </div>

        <div class="bg-white dark:bg-slate-900 
                    border border-slate-200 dark:border-slate-800
                    rounded-2xl p-6 shadow-sm">

            <h2 class="text-sm text-slate-500 dark:text-slate-400">
                Transaksi Hari Ini
            </h2>
            <p class="text-2xl font-bold text-slate-800 dark:text-white mt-2">
                0
            </p>
        </div>

    </div>

</div>

@endsection