@extends('layouts.app')

@section('title', 'Developer Dashboard')

@section('content')
<div class="mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Super Admin (Developer) Panel</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Mengelola konfigurasi sistem, optimasi, dan pencadangan (backup) database.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-start">
            <svg class="w-5 h-5 mr-2 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="whitespace-pre-line">{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Informasi Sistem -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 flex flex-col">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Informasi Sistem
            </h2>
            <ul class="space-y-4 flex-1">
                <li class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span class="text-slate-500 dark:text-slate-400 text-sm">Versi PHP</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $phpVersion }}</span>
                </li>
                <li class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span class="text-slate-500 dark:text-slate-400 text-sm">Versi Laravel</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $laravelVersion }}</span>
                </li>
                <li class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span class="text-slate-500 dark:text-slate-400 text-sm">Ukuran Database</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $databaseSize }}</span>
                </li>
                <li class="flex justify-between items-center pb-2">
                    <span class="text-slate-500 dark:text-slate-400 text-sm">Lingkungan (Env)</span>
                    <span class="font-medium px-2.5 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-lg text-xs">{{ env('APP_ENV') }}</span>
                </li>
            </ul>
        </div>

        <!-- Optimasi Sistem -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 flex flex-col">
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Optimasi Sistem
            </h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 flex-1">
                Membersihkan cache aplikasi, view yang dikompilasi, dan cache rute. Berguna jika ada pembaruan kode yang tidak langsung muncul (stuck).
            </p>
            <form action="{{ route('developer.clear-cache') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex justify-center items-center bg-white border-2 border-slate-200 dark:border-slate-700 hover:border-amber-500 hover:text-amber-600 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold py-2.5 px-4 rounded-xl transition duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Bersihkan Cache Aplikasi
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
