@extends('layouts.app')

@section('title', 'Manajemen Owner')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- HEADER + BREADCRUMB --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
        <div class="flex-1">
            <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                <a href="{{ route('developer.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Developer</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-blue-600 dark:text-blue-400">Manajemen Owner</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">Manajemen Owner</h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Kelola daftar akun Owner yang terdaftar di sistem.
            </p>
        </div>
    </div>

    {{-- FLASH MESSAGES --}}
    @if (session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl flex items-center text-sm">
            <svg class="w-5 h-5 mr-2.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- TABEL DAFTAR OWNER --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 md:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center gap-3 justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-semibold text-slate-800 dark:text-white">Daftar Akun Owner</h2>
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 dark:bg-blue-500/10 border border-blue-100/50 dark:border-blue-800/30 shadow-sm">
                    <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ $owners->count() }}</span>
                    <span class="text-[10px] font-semibold text-blue-500/70 dark:text-blue-400/70 uppercase tracking-widest">Data</span>
                </span>
            </div>
            <a href="{{ route('developer.owners.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-semibold rounded-xl transition-all shadow-sm shadow-blue-500/20"
               style="background-color: #2563eb; color: #fff;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Owner
            </a>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="px-6 py-3 text-left w-12">#</th>
                        <th class="px-6 py-3 text-left">Nama</th>
                        <th class="px-6 py-3 text-left">Username</th>
                        <th class="px-6 py-3 text-left">Email</th>
                        <th class="px-6 py-3 text-left">Dibuat Pada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($owners as $index => $owner)
                        <tr class="border-t border-slate-200 dark:border-slate-800">
                            <td class="px-6 py-4 text-slate-400 tabular-nums">{{ $index + 1 }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0" style="background-color: #6366f1;">
                                        {{ strtoupper(substr($owner->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-slate-800 dark:text-slate-100">{{ $owner->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-300 font-mono text-xs">{{ $owner->username }}</td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $owner->email }}</td>
                            <td class="px-6 py-4 text-slate-500 whitespace-nowrap">{{ $owner->created_at->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                Belum ada akun owner yang dibuat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="md:hidden divide-y divide-slate-200 dark:divide-slate-800">
            @forelse ($owners as $owner)
                <div class="px-4 py-3 space-y-1.5">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0" style="background-color: #6366f1;">
                            {{ strtoupper(substr($owner->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $owner->name }}</p>
                            <p class="text-xs text-slate-500">{{ '@' . $owner->username }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 pl-11">
                        <p class="text-xs text-slate-600 dark:text-slate-300 truncate">{{ $owner->email }}</p>
                        <p class="text-xs text-slate-500 whitespace-nowrap">{{ $owner->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    Belum ada akun owner yang dibuat.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
