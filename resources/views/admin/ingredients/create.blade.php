@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Tambah Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
            <a href="{{ route('admin.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <a href="{{ route('admin.ingredients.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Manajemen Bahan</a>
            <span class="text-slate-300 dark:text-slate-600">/</span>
            <span class="text-blue-600 dark:text-blue-400">Tambah Bahan</span>
        </nav>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Tambah Bahan Baku
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Tambahkan bahan baku baru ke dalam sistem inventory lengkap dengan aturan satuan dan minimum stok.
            </p>
        </div>
    </div>

    {{-- Render Form Partial --}}
    @include('admin.ingredients.partials.form', [
        'action' => route('admin.ingredients.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Bahan',
        'ingredient' => null,
        'categories' => $categories
    ])

</div>
@endsection