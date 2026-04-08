@extends('layouts.app')

@section('title', 'User Management')

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
            <span class="text-slate-600 dark:text-slate-300">Pengguna</span>
        </nav>

        {{-- Judul --}}
        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white tracking-tight mb-3">
            Manajemen Pengguna
        </h1>

        {{-- Deskripsi Halaman --}}
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
            Kelola hak akses pengguna, tambahkan kasir baru, atau nonaktifkan akun yang sudah tidak bertugas.<br class="hidden sm:block mt-1">
            Pastikan setiap pengguna memiliki akses (role) yang sesuai dengan tanggung jawabnya.
        </p>

        {{-- Indikator Total --}}
        <div class="inline-flex items-center gap-2.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-lg shadow-sm mb-4">
            <span class="text-[11px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wide flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Total Terdaftar:
                <span class="text-slate-900 dark:text-white normal-case tracking-normal ml-1">{{ $users->total() ?? $users->count() }} Akun</span>
            </span>
        </div>

        {{-- Tombol Aksi --}}
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('owner.users.create') }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-[13px] font-bold rounded-xl active:scale-95 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Tambah Pengguna
            </a>
        </div>
        
    </div>

    @if(session('success'))
        <div class="flex items-center gap-3 p-4 mb-6 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 text-emerald-700 dark:text-emerald-400 text-sm font-medium shadow-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:hidden">
        @forelse($users as $user)
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm relative overflow-hidden flex flex-col">
                
                {{-- Decorative Line --}}
                <div class="absolute top-0 left-0 w-1.5 h-full {{ $user->deleted_at ? 'bg-red-500' : 'bg-emerald-500' }}"></div>

                {{-- Card Content --}}
                <div class="p-5 pl-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">{{ $user->name }}</h3>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-0.5">{{ $user->email }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ $user->role->name }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Username</p>
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mt-0.5">{{ $user->username }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</p>
                            <div class="mt-1">
                                @if($user->deleted_at)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Nonaktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Aktif</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

{{-- Native App-like Bottom Action Bar (Iconless & Clean) --}}
                <div class="flex border-t border-slate-100 dark:border-slate-800 mt-auto divide-x divide-slate-100 dark:divide-slate-800">
                    
                    {{-- Edit Button --}}
                    <a href="{{ route('owner.users.edit', $user->id) }}" 
                       class="flex-1 flex items-center justify-center py-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <span class="text-[11px] font-black uppercase tracking-[0.15em]">Edit</span>
                    </a>
                    
                    {{-- Reset Button --}}
                    <a href="{{ route('owner.users.reset.form', $user->id) }}" 
                       class="flex-1 flex items-center justify-center py-3.5 hover:bg-amber-50 dark:hover:bg-amber-500/10 text-slate-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                        <span class="text-[11px] font-black uppercase tracking-[0.15em]">Reset</span>
                    </a>
                    
                    {{-- Disable Button --}}
                    @if(!$user->deleted_at)
                    <form action="{{ route('owner.users.destroy', $user->id) }}" method="POST" class="flex-1 flex">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Yakin ingin menonaktifkan user ini?')" 
                                class="w-full flex items-center justify-center py-3.5 hover:bg-red-50 dark:hover:bg-red-500/10 text-slate-500 hover:text-red-600 dark:hover:text-red-400 transition-colors outline-none">
                            <span class="text-[11px] font-black uppercase tracking-[0.15em]">Matikan</span>
                        </button>
                    </form>
                    @endif

                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-10 text-center shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Belum ada data user.</p>
            </div>
        @endforelse
    </div>

    <div class="hidden sm:block bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                    <tr>
                        <th class="px-6 py-4">Informasi User</th>
                        <th class="px-6 py-4">Username</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Bergabung</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            
                            {{-- Info User (Nama + Email) --}}
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900 dark:text-white">{{ $user->name }}</p>
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mt-0.5">{{ $user->email }}</p>
                            </td>

                            {{-- Username --}}
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono text-xs">
                                    {{ $user->username }}
                                </span>
                            </td>

                            {{-- Role --}}
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $user->role->name }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                @if($user->deleted_at)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Nonaktif</span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Aktif</span>
                                @endif
                            </td>

                            {{-- Dibuat --}}
                            <td class="px-6 py-4 text-center text-xs text-slate-400 dark:text-slate-500 font-medium tabular-nums">
                                {{ $user->created_at->format('d M Y') }}
                            </td>

                            {{-- Aksi (Icon Buttons) --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('owner.users.edit', $user->id) }}" title="Edit User"
                                       class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-500/10 rounded-lg transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </a>

                                    <a href="{{ route('owner.users.reset.form', $user->id) }}" title="Reset Password"
                                       class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-lg transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                    </a>

                                    @if(!$user->deleted_at)
                                    <form action="{{ route('owner.users.destroy', $user->id) }}" method="POST" class="inline-block">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Nonaktifkan User"
                                                onclick="return confirm('Yakin ingin menonaktifkan user ini?')"
                                                class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">Belum ada data user terdaftar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (Sama persis stylingnya) --}}
        @if(method_exists($users, 'links') && $users->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/10">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
