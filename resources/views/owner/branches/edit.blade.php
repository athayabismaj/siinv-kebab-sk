@extends('layouts.app')

@section('title', 'Edit Cabang')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
<div class="space-y-8">
    <div>
        <nav class="mb-3 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 sm:text-[11px]">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <a href="{{ route('owner.branches.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Cabang</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-blue-600 dark:text-blue-400">Edit</span>
        </nav>

        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Edit Cabang</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
            Perbarui identitas cabang. Perubahan ini akan dipakai pada pilihan cabang pengguna.
        </p>
    </div>

    @include('owner.branches.partials.form', [
        'title' => $branch->name,
        'action' => route('owner.branches.update', $branch),
        'method' => 'PUT',
        'buttonText' => 'Simpan Perubahan',
        'branch' => $branch,
    ])
</div>
@endsection
