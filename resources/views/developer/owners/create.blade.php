@extends('layouts.app')

@section('title', 'Tambah Akun Owner')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- HEADER + BREADCRUMB --}}
    <x-page-header 
        title="Tambah Akun Owner" 
        subtitle="Buat akun baru dengan role Owner untuk mengakses panel owner." 
        breadcrumb-parent="Developer" 
        breadcrumb-child="Tambah Akun Owner">
        
        <a href="{{ route('developer.owners.index') }}"
           class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-slate-900 text-xs font-bold rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all text-slate-600 dark:text-slate-300 shadow-sm">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </x-page-header>

    {{-- VALIDATION ERRORS --}}
    @if ($errors->any())
        <div class="max-w-3xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/50 text-red-600 dark:text-red-400 px-4 py-3 rounded-xl text-xs">
            <div class="flex items-center mb-1.5">
                <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-bold">Terdapat kesalahan pada input:</span>
            </div>
            <ul class="list-disc list-inside ml-6 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM --}}
    <div class="max-w-5xl">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-800/20">
                <h2 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Informasi Akun
                </h2>
            </div>

            <form action="{{ route('developer.owners.store') }}" method="POST" class="p-6 sm:p-8">
                @csrf
                <div class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-sm py-2.5 px-4 focus:border-blue-500 focus:ring-blue-500 placeholder:text-slate-400 transition-colors"
                                   placeholder="Contoh: Budi Santoso">
                        </div>
                        
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Username</label>
                            <input type="text" name="username" value="{{ old('username') }}" required
                                   class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-sm py-2.5 px-4 focus:border-blue-500 focus:ring-blue-500 placeholder:text-slate-400 transition-colors"
                                   placeholder="Contoh: budiowner">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-sm py-2.5 px-4 focus:border-blue-500 focus:ring-blue-500 placeholder:text-slate-400 transition-colors"
                                   placeholder="Contoh: budi@owner.com">
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Password</label>
                            <input type="password" name="password" required
                                   class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-sm py-2.5 px-4 focus:border-blue-500 focus:ring-blue-500 placeholder:text-slate-400 transition-colors"
                                   placeholder="Minimal 8 karakter">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800/60">
                    <a href="{{ route('developer.owners.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2 text-xs font-bold rounded-lg transition-all shadow-sm shadow-blue-500/20"
                            style="background-color: #2563eb; color: #fff;">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
