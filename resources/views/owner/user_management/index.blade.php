@extends('layouts.app')

@section('title', 'Daftar Pengguna')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
<div class="space-y-8 max-w-full overflow-x-hidden" x-data="{
        destroyUrl: '',
        restoreUrl: '',
        userName: '',
        openUserDestroy(url, name) {
            this.destroyUrl = url;
            this.userName = name;
            document.getElementById('user_destroy_confirmation').value = '';
            $dispatch('open-modal', 'user-destroy-modal');
        },
        openUserRestore(url, name) {
            this.restoreUrl = url;
            this.userName = name;
            document.getElementById('user_restore_confirmation').value = '';
            $dispatch('open-modal', 'user-restore-modal');
        }
    }">

    <x-page-header
        title="Daftar Pengguna"
        subtitle="Kelola akun admin dan kasir, termasuk akses cabang serta status aktifnya."
        breadcrumb-parent="Owner" 
        breadcrumb-child="Pengguna">
    </x-page-header>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <nav class="grid grid-cols-3 gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-900 sm:inline-grid sm:min-w-[420px]" aria-label="Filter status pengguna">
            @foreach([
                'active' => ['label' => 'Aktif', 'count' => $activeCount],
                'inactive' => ['label' => 'Nonaktif', 'count' => $inactiveCount],
                'all' => ['label' => 'Semua', 'count' => $allCount],
            ] as $filter => $item)
                <a href="{{ route('owner.users.index', ['status' => $filter]) }}"
                   @class([
                       'inline-flex h-9 items-center justify-center gap-2 rounded-lg px-3 text-xs font-bold transition-colors',
                       'bg-white text-blue-600 shadow-sm dark:bg-slate-800 dark:text-blue-400' => $status === $filter,
                       'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => $status !== $filter,
                   ])>
                    {{ $item['label'] }}
                    <span @class([
                        'rounded-md px-1.5 py-0.5 text-[10px] tabular-nums',
                        'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300' => $status === $filter,
                        'bg-slate-200/70 text-slate-500 dark:bg-slate-700 dark:text-slate-300' => $status !== $filter,
                    ])>{{ $item['count'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="flex w-full flex-col sm:flex-row sm:items-center justify-end gap-3 sm:w-auto">
            <div class="inline-flex h-10 items-center gap-2 px-3.5 bg-slate-100 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl shadow-sm">
                <span class="text-[11px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wide flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Total: <span class="text-slate-900 dark:text-white normal-case tracking-normal ml-0.5">{{ $users->total() ?? $users->count() }} Akun</span>
                </span>
            </div>
            
            <a href="{{ route('owner.branches.index') }}"
               class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-[13px] font-bold text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-95 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h1m-1 4h1m-1 4h1m5-4h1m-1 4h1"></path></svg>
                Kelola Cabang
            </a>
            
            <a href="{{ route('owner.users.create') }}"
               class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-[13px] font-bold text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Tambah Pengguna
            </a>
        </div>
    </div>

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
                            <div class="mt-1 flex items-center gap-1.5">
                                @if($user->deleted_at)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Nonaktif</span>
                                    <div class="group relative flex items-center justify-center">
                                        <svg class="h-4 w-4 text-slate-400 hover:text-slate-500 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <div class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max opacity-0 transition-opacity group-hover:opacity-100 z-10">
                                            <div class="rounded bg-slate-800 px-2 py-1 text-[10px] font-medium text-white shadow-sm dark:bg-slate-700 whitespace-nowrap">Sejak {{ $user->deleted_at->format('d M Y') }}</div>
                                            <div class="mx-auto h-0 w-0 border-x-[4px] border-t-[4px] border-x-transparent border-t-slate-800 dark:border-t-slate-700"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Aktif</span>
                                @endif
                            </div>
                        </div>
                        @if($usesBranches ?? false)
                            <div class="col-span-2">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cabang</p>
                                @php
                                    $branchNames = ($usesBranchAssignments ?? false) && strtolower($user->role->name ?? '') === 'admin'
                                        ? $user->assignedBranches->pluck('name')->filter()->values()
                                        : collect([$user->branch->name ?? 'Kebab SK']);
                                @endphp
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mt-0.5">
                                    {{ $branchNames->isNotEmpty() ? $branchNames->join(', ') : ($user->branch->name ?? 'Kebab SK') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

{{-- Native App-like Bottom Action Bar (Iconless & Clean) --}}
                <div class="flex border-t border-slate-100 dark:border-slate-800 mt-auto divide-x divide-slate-100 dark:divide-slate-800">
                    
                    @if($user->trashed())
                        <button type="button"
                                @click="openUserRestore('{{ route('owner.users.restore', $user->id) }}', '{{ addslashes($user->name) }}')"
                                class="flex-1 py-3.5 text-[11px] font-black uppercase tracking-[0.15em] text-emerald-600 transition-colors hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-500/10">
                            Aktifkan Kembali
                        </button>
                    @else
                        <a href="{{ route('owner.users.edit', $user->id) }}"
                           class="flex flex-1 items-center justify-center py-3.5 text-slate-500 transition-colors hover:bg-slate-50 hover:text-blue-600 dark:hover:bg-slate-800/50 dark:hover:text-blue-400">
                            <span class="text-[11px] font-black uppercase tracking-[0.15em]">Edit</span>
                        </a>
                        <a href="{{ route('owner.users.reset.form', $user->id) }}"
                           class="flex flex-1 items-center justify-center py-3.5 text-slate-500 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-500/10 dark:hover:text-amber-400">
                            <span class="text-[11px] font-black uppercase tracking-[0.15em]">Atur Ulang</span>
                        </a>
                        <button type="button"
                                @click="openUserDestroy('{{ route('owner.users.destroy', $user->id) }}', '{{ addslashes($user->name) }}')"
                                class="flex-1 py-3.5 text-[11px] font-black uppercase tracking-[0.15em] text-slate-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                            Nonaktifkan
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-10 text-center shadow-sm">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">
                    {{ $status === 'inactive' ? 'Belum ada pengguna nonaktif.' : ($status === 'all' ? 'Belum ada pengguna terdaftar.' : 'Belum ada pengguna aktif.') }}
                </p>
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
                        @if($usesBranches ?? false)
                            <th class="px-6 py-4">Cabang</th>
                        @endif
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

                            {{-- Cabang --}}
                            @if($usesBranches ?? false)
                                <td class="px-6 py-4">
                                    @php
                                        $branchNames = ($usesBranchAssignments ?? false) && strtolower($user->role->name ?? '') === 'admin'
                                            ? $user->assignedBranches->pluck('name')->filter()->values()
                                            : collect([$user->branch->name ?? 'Kebab SK']);
                                    @endphp
                                    <div class="flex max-w-xs flex-wrap gap-1.5">
                                        @forelse($branchNames as $branchName)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                                {{ $branchName }}
                                            </span>
                                        @empty
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-widest bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                                {{ $user->branch->name ?? 'Kebab SK' }}
                                            </span>
                                        @endforelse
                                    </div>
                                </td>
                            @endif

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                @if($user->deleted_at)
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Nonaktif</span>
                                        <div class="group relative flex items-center justify-center">
                                            <svg class="h-4 w-4 text-slate-400 hover:text-slate-500 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <div class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-max opacity-0 transition-opacity group-hover:opacity-100 z-10">
                                                <div class="rounded bg-slate-800 px-2 py-1 text-[10px] font-medium text-white shadow-sm dark:bg-slate-700 whitespace-nowrap">Sejak {{ $user->deleted_at->format('d M Y') }}</div>
                                                <div class="mx-auto h-0 w-0 border-x-[4px] border-t-[4px] border-x-transparent border-t-slate-800 dark:border-t-slate-700"></div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Aktif</span>
                                @endif
                            </td>

                            {{-- Bergabung --}}
                            <td class="px-6 py-4 text-center text-xs text-slate-400 dark:text-slate-500 font-medium tabular-nums">
                                {{ $user->created_at->format('d M Y') }}
                            </td>

                            {{-- Aksi (Icon Buttons) --}}
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($user->trashed())
                                        <button type="button" title="Aktifkan Kembali"
                                                @click="openUserRestore('{{ route('owner.users.restore', $user->id) }}', '{{ addslashes($user->name) }}')"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 dark:text-slate-500 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M4.582 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2M19.419 15H15"></path></svg>
                                        </button>
                                    @else
                                        <a href="{{ route('owner.users.edit', $user->id) }}" title="Edit Pengguna"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:text-slate-500 dark:hover:bg-blue-500/10 dark:hover:text-blue-400">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </a>
                                        <a href="{{ route('owner.users.reset.form', $user->id) }}" title="Atur Ulang Kata Sandi"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:text-slate-500 dark:hover:bg-amber-500/10 dark:hover:text-amber-400">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                        </a>
                                        <button type="button" title="Nonaktifkan Pengguna"
                                                @click="openUserDestroy('{{ route('owner.users.destroy', $user->id) }}', '{{ addslashes($user->name) }}')"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-slate-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($usesBranches ?? false) ? 7 : 6 }}" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center mb-3">
                                        <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <p class="text-slate-400 dark:text-slate-500 text-sm font-medium">
                                        {{ $status === 'inactive' ? 'Belum ada pengguna nonaktif.' : ($status === 'all' ? 'Belum ada pengguna terdaftar.' : 'Belum ada pengguna aktif.') }}
                                    </p>
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

    {{-- Modal Nonaktifkan User --}}
    <x-modal id="user-destroy-modal" maxWidth="md" type="danger">
        <x-slot name="title">Nonaktifkan Pengguna</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </x-slot>
        <x-slot name="description">
            Anda yakin ingin menonaktifkan akun pengguna <span class="font-bold text-slate-900 dark:text-white" x-text="userName"></span>? Akun ini tidak akan dapat login atau melakukan transaksi. Akun dapat diaktifkan kembali melalui filter Nonaktif.
        </x-slot>

        <form x-bind:action="destroyUrl" method="POST" x-data="{ input: '' }" @open-modal.window="if($event.detail === 'user-destroy-modal') input = ''">
            @csrf
            @method('DELETE')
            <div class="pt-2">
                <label class="sr-only" for="user_destroy_confirmation">Konfirmasi</label>
                <input type="text" name="destroy_confirmation" id="user_destroy_confirmation" 
                       x-model="input"
                       placeholder="Ketik 'nonaktif' untuk konfirmasi"
                       class="block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-rose-500 dark:focus:ring-rose-500" 
                       autocomplete="off"
                       @keydown.enter.prevent="if(input.toLowerCase() === 'nonaktif') $el.closest('form').submit()" />
            </div>
            
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'user-destroy-modal')"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                        :disabled="input.toLowerCase() !== 'nonaktif'"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                    Ya, Nonaktifkan
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal id="user-restore-modal" maxWidth="md" type="success">
        <x-slot name="title">Aktifkan Kembali Pengguna</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9M4.582 9H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2M19.419 15H15"></path></svg>
        </x-slot>
        <x-slot name="description">
            Akun <span class="font-bold text-slate-900 dark:text-white" x-text="userName"></span> akan dapat login kembali dengan kata sandi dan akses cabang sebelumnya.
        </x-slot>

        <form x-bind:action="restoreUrl" method="POST" x-data="{ input: '' }" @open-modal.window="if($event.detail === 'user-restore-modal') input = ''">
            @csrf
            @method('PATCH')
            <div class="pt-2">
                <label class="sr-only" for="user_restore_confirmation">Konfirmasi</label>
                <input type="text" name="restore_confirmation" id="user_restore_confirmation" 
                       x-model="input"
                       placeholder="Ketik 'aktifkan' untuk konfirmasi"
                       class="block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500 dark:focus:ring-emerald-500" 
                       autocomplete="off"
                       @keydown.enter.prevent="if(input.toLowerCase() === 'aktifkan') $el.closest('form').submit()" />
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'user-restore-modal')"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                        :disabled="input.toLowerCase() !== 'aktifkan'"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                    Ya, Aktifkan
                </button>
            </div>
        </form>
    </x-modal>

</div>
@endsection
