@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Resep Varian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-24">

    <x-page-header 
        title="Edit Resep Varian - {{ $variant->menu?->name }} - {{ $variant->name }}" 
        subtitle="Tentukan komposisi bahan baku untuk varian ini. Biarkan kosong '0' jika bahan tidak digunakan." 
        breadcrumb-parent="Manajemen Resep" 
        breadcrumb-child="Edit Resep">
        
        <a href="{{ route('admin.recipes.index') }}"
           class="inline-flex shrink-0 items-center justify-center gap-2 px-5 py-2.5 h-10 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-[13px] font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all shadow-sm">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Resep
        </a>
    </x-page-header>



    {{-- Render Form Partial --}}
    @include('admin.recipes.partials.form', [
        'variant' => $variant,
        'ingredientCategories' => $ingredientCategories
    ])

</div>

@endsection
