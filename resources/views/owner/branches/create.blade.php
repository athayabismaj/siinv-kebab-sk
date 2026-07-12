@extends('layouts.app')

@section('title', 'Tambah Cabang')

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
            <span class="text-blue-600 dark:text-blue-400">Tambah</span>
        </nav>

        <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Tambah Cabang</h1>
        <p class="mt-2 max-w-3xl text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
            Tambahkan cabang operasional agar admin dan kasir dapat dipetakan ke lokasi kerja yang sesuai.
        </p>
    </div>

    @include('owner.branches.partials.form', [
        'title' => 'Cabang Baru',
        'action' => route('owner.branches.store'),
        'method' => 'POST',
        'buttonText' => 'Simpan Cabang',
        'branch' => $branch,
    ])
</div>
@endsection
