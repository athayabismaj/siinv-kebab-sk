@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Edit Varian')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto hide-scrollbar pb-1">
            <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Beranda
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            
            <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                Menu & Resep
            </span>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            
            <a href="{{ route('admin.menus.index') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Manajemen Menu
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>

            <a href="{{ route('admin.menu-variants.index', $menu->id) }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                Varian Menu
            </a>
            <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
            
            <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                Edit Varian
            </span>
        </nav>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Edit Varian
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Perbarui informasi varian dari menu <strong class="text-slate-700 dark:text-slate-300">{{ $menu->name }}</strong>.
            </p>
        </div>
    </div>

    {{-- Render Form Partial --}}
    @include('admin.menu_variants.partials.form', [
        'action' => route('admin.menu-variants.update', [$menu->id, $menuVariant->id]),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan'
    ])

</div>
@endsection