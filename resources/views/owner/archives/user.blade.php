@extends('layouts.app')

@section('title', 'Arsip Pengguna')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
<div class="space-y-8 max-w-full overflow-x-hidden">

    <div class="mb-8">
        
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <a href="{{ route('owner.users.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Pengguna</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Arsip</span>
        </nav>

        {{-- Judul & Tombol Aksi Utama --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-3">
            <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none">
                Arsip Pengguna
            </h1>

            <a href="{{ route('owner.users.index') }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 text-[13px] font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 transition-all shadow-sm shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Daftar
            </a>
        </div>

        {{-- Deskripsi Halaman --}}
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
            Daftar seluruh akun pengguna dan kasir yang telah dinonaktifkan dari sistem.<br class="hidden sm:block mt-1">
            Anda dapat memulihkan (mengaktifkan kembali) akun di bawah ini jika diperlukan.
        </p>

        {{-- Indikator Total (FIX Kontras Dark Mode) --}}
        <div class="inline-flex items-center gap-2.5 px-3 py-1.5 bg-red-50 dark:bg-red-900 dark:bg-opacity-30 border border-red-200 dark:border-red-800/50 rounded-lg shadow-sm">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
            </span>
            <span class="text-[11px] sm:text-xs font-bold text-red-700 dark:text-red-400 uppercase tracking-wide">
                Total Arsip:
                <span class="text-slate-900 dark:text-red-100 normal-case tracking-normal ml-1">{{ $users->total() ?? $users->count() }} Akun Nonaktif</span>
            </span>
        </div>
        
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-emerald-50 dark:bg-emerald-900 dark:bg-opacity-30 border border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-400 text-sm font-medium shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:hidden">
        @forelse($users as $user)
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm relative overflow-hidden flex flex-col">
                
                {{-- Decorative Line (Selalu Merah karena Nonaktif) --}}
                <div class="absolute top-0 left-0 w-1.5 h-full bg-red-500"></div>

                {{-- Card Content --}}
                <div class="p-5 pl-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $user->name }}</h3>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">{{ $user->email }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            {{ $user->role->name }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Username</p>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mt-0.5">{{ $user->username }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Dinonaktifkan</p>
                            <p class="text-xs font-bold text-red-600 dark:text-red-400 mt-0.5">{{ optional($user->deleted_at)->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Native App-like Bottom Action Bar --}}
                <div class="flex border-t border-slate-100 dark:border-slate-800 mt-auto bg-slate-50 dark:bg-slate-800 dark:bg-opacity-50">
                    <form action="{{ route('owner.users.restore', $user->id) }}" method="POST" class="flex-1 flex">
                        @csrf @method('PATCH')
                        <button type="submit" onclick="return confirm('Apakah Anda yakin ingin mengaktifkan kembali akun pengguna ini?')" 
                                class="w-full flex items-center justify-center gap-2 py-3.5 hover:bg-emerald-50 dark:hover:bg-emerald-900 dark:hover:bg-opacity-30 text-slate-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span class="text-[11px] font-black uppercase tracking-[0.15em]">Aktifkan Kembali</span>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-10 text-center shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
                <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Belum ada pengguna yang diarsipkan.</p>
            </div>
        @endforelse
    </div>

    <div class="hidden sm:block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                
                {{-- FIX Kontras Header Tabel di Dark Mode (Menggunakan dark:bg-slate-800 solid) --}}
                <thead class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-6 py-4">Informasi User</th>
                        <th class="px-6 py-4">Username</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Dinonaktifkan Tgl</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            
                            {{-- Info User (Nama + Email) --}}
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900 dark:text-white">{{ $user->name }}</p>
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5">{{ $user->email }}</p>
                            </td>

                            {{-- Username --}}
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-mono text-xs">
                                    {{ $user->username }}
                                </span>
                            </td>

                            {{-- Role --}}
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                    {{ $user->role->name }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-red-100 dark:bg-red-900 dark:bg-opacity-40 text-red-700 dark:text-red-400">Nonaktif</span>
                            </td>

                            {{-- Dinonaktifkan Tgl --}}
                            <td class="px-6 py-4 text-center text-xs text-red-600 dark:text-red-400 font-semibold tabular-nums">
                                {{ optional($user->deleted_at)->format('d M Y') }}
                            </td>

                            {{-- Aksi (Restore Button) --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end">
                                    <form action="{{ route('owner.users.restore', $user->id) }}" method="POST" class="inline-block">
                                        @csrf @method('PATCH')
                                        <button type="submit" title="Aktifkan Kembali"
                                                onclick="return confirm('Apakah Anda yakin ingin mengaktifkan kembali akun pengguna ini?')"
                                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900 dark:hover:bg-opacity-40 rounded-lg transition-all border border-transparent hover:border-emerald-200 dark:hover:border-emerald-800/50">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                            Aktifkan
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                    </div>
                                    <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Belum ada pengguna yang diarsipkan.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(method_exists($users, 'links') && $users->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection