@extends('layouts.app')

@section('title', 'Manajemen Owner')

@push('styles')
@vite('resources/css/pages/developer-owners.css')
@endpush

@section('content')
@php
    $totalOwners = $owners->count();
    $latestOwner = $owners->first();
@endphp

<div
    x-data="{
        createOwnerOpen: @js($errors->any()),
        showPassword: false,
        restoreUrl: '',
        destroyUrl: '',
        openOwnerRestore(url) {
            this.restoreUrl = url;
            document.getElementById('owner_restore_confirmation').value = '';
            $dispatch('open-modal', 'owner-restore-modal');
        },
        openOwnerDestroy(url) {
            this.destroyUrl = url;
            document.getElementById('owner_destroy_confirmation').value = '';
            $dispatch('open-modal', 'owner-destroy-modal');
        }
    }"
    @keydown.escape.window="createOwnerOpen = false"
    class="w-full space-y-4 overflow-x-hidden pb-10">

    <x-page-header 
        title="Manajemen Owner" 
        subtitle="Kelola akun pemilik usaha, akses panel owner, dan status pembuatan akun dari satu halaman." 
        breadcrumb-parent="Super Admin" 
        breadcrumb-child="Manajemen Owner">
        
        <span class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 px-3 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:text-slate-400">
            {{ number_format($totalOwners) }} Owner
        </span>
        <button type="button"
                @click="createOwnerOpen = true"
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 text-xs font-black text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-500/15 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"></path></svg>
            Tambah Owner
        </button>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
        <!-- Total Owner Card -->
        <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m8-4.13a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                TOTAL OWNER
            </div>
            <div class="mt-5 flex flex-1 flex-col justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($totalOwners) }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Akun aktif terdaftar di sistem.</p>
                </div>
            </div>
        </div>

        <!-- Owner Terbaru Card -->
        <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0118.5 14.5c0 2.485-2.91 4.5-6.5 4.5s-6.5-2.015-6.5-4.5c0-1.362.286-2.69.84-3.922L12 14z"></path></svg>
                OWNER TERBARU
            </div>
            <div class="mt-5 flex flex-1 flex-col justify-between">
                <div>
                    <h2 class="truncate text-xl font-bold text-slate-900 dark:text-white" title="{{ $latestOwner->name ?? 'Belum ada owner' }}">
                        {{ $latestOwner->name ?? 'Belum ada owner' }}
                    </h2>
                    <div class="mt-3 flex items-center text-sm text-slate-500 dark:text-slate-400">
                        <svg class="mr-2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ optional(optional($latestOwner)->created_at)->format('d M Y, H:i') ?? 'Belum ada data' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Akses Role Card -->
        <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3.75l7.5 3.75v4.75c0 4.55-3.075 7.412-7.5 8.5-4.425-1.088-7.5-3.95-7.5-8.5V7.5L12 3.75z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 12l1.5 1.5 3.25-3.25"></path></svg>
                    AKSES ROLE
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Aktif
                </span>
            </div>
            <div class="mt-5 flex flex-1 flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Owner Panel</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Akses penuh ke dashboard, laporan laba rugi, dan kelola pengguna.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Daftar Akun Owner</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Daftar {{ number_format($totalOwners) }} akun owner yang terdaftar.</p>
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="border-b border-slate-100 bg-slate-50/50 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Nama</th>
                        <th class="px-6 py-4 font-semibold">Username</th>
                        <th class="px-6 py-4 font-semibold">Email</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 font-semibold">Dibuat Pada</th>
                        <th class="px-6 py-4 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($owners as $owner)
                        <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-black text-white shadow-sm">
                                        {{ strtoupper(substr($owner->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-900 dark:text-slate-200">{{ $owner->name }}</p>
                                        <p class="mt-0.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400">Role Owner</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">{{ '@' . $owner->username }}</td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $owner->email }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($owner->trashed())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Nonaktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">{{ $owner->created_at->format('d M Y, H:i') }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($owner->trashed())
                                        <button type="button" @click="openOwnerRestore('{{ route('developer.owners.restore', $owner->id) }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 dark:text-slate-500 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400" title="Aktifkan Owner">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </button>
                                    @else
                                        <button type="button" @click="openOwnerDestroy('{{ route('developer.owners.destroy', $owner->id) }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-slate-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400" title="Nonaktifkan Owner">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                Belum ada akun owner.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="flex flex-col gap-3 p-4 md:hidden">
            @forelse ($owners as $owner)
                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-sm font-black text-white shadow-sm">
                                {{ strtoupper(substr($owner->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-slate-900 dark:text-slate-200" title="{{ $owner->name }}">{{ $owner->name }}</h3>
                                <p class="mt-0.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ '@' . $owner->username }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-200/60 pt-3 dark:border-slate-700/60">
                        <div class="flex flex-col gap-0.5 text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $owner->email }}</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="font-medium text-slate-500">{{ $owner->created_at->format('d M Y, H:i') }}</span>
                                <span class="text-slate-300 dark:text-slate-600">•</span>
                                @if($owner->trashed())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-rose-700 dark:bg-rose-500/10 dark:text-rose-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Nonaktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($owner->trashed())
                                <button type="button" @click="openOwnerRestore('{{ route('developer.owners.restore', $owner->id) }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 shadow-sm transition-colors hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20" title="Aktifkan Owner">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                            @else
                                <button type="button" @click="openOwnerDestroy('{{ route('developer.owners.destroy', $owner->id) }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 shadow-sm transition-colors hover:bg-rose-100 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20" title="Nonaktifkan Owner">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                    Belum ada akun owner.
                </div>
            @endforelse
        </div>
    </div>

    <div x-show="createOwnerOpen" x-cloak class="owner-create-modal-shell">
        <div @click.outside="createOwnerOpen = false" class="owner-create-modal-panel rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Tambah Owner</h2>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                        Buat akun owner baru untuk mengakses panel pemilik.
                    </p>
                </div>
                <button type="button" @click="createOwnerOpen = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 dark:border-rose-900/60 dark:bg-rose-500/10 dark:text-rose-300">
                    <p class="font-black">Periksa kembali input berikut:</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('developer.owners.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Nama Lengkap</span>
                        <input type="text" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Contoh: Budi Santoso"
                               class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @error('name')
                            <span class="mt-1 block text-[11px] font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Username</span>
                        <input type="text" name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="Contoh: budiowner"
                               class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @error('username')
                            <span class="mt-1 block text-[11px] font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <label class="block">
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Contoh: budi@owner.com"
                           class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    @error('email')
                        <span class="mt-1 block text-[11px] font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Password</span>
                    <span class="relative block">
                        <input :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter"
                               class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 pr-11 text-sm font-semibold text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <button type="button" @click="showPassword = ! showPassword" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-slate-400 transition hover:text-slate-700 dark:hover:text-slate-200" aria-label="Tampilkan atau sembunyikan password">
                            <svg x-show="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c1.658 0 3.223-.386 4.614-1.072M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l12.544 12.544M21 21l-3.228-3.228"></path></svg>
                            <svg x-show="showPassword" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.43 0 .638C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </button>
                    </span>
                    @error('password')
                        <span class="mt-1 block text-[11px] font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</span>
                    @enderror
                </label>

                <div class="grid grid-cols-2 gap-2 pt-1 sm:flex sm:justify-end">
                    <button type="button" @click="createOwnerOpen = false" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 px-3 text-[11px] font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 sm:px-4 sm:text-xs">
                        Batal
                    </button>
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 text-[11px] font-black text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 sm:px-4 sm:text-xs">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Simpan Owner
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Modals -->
    <x-modal id="owner-restore-modal" maxWidth="md" type="success">
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </x-slot>
        <x-slot name="title">
            Aktifkan Owner
        </x-slot>
        <x-slot name="description">
            Ketik <b class="text-slate-900 dark:text-white">AKTIFKAN</b> untuk mengonfirmasi pengaktifan kembali akun owner ini.
        </x-slot>

        <form :action="restoreUrl" method="POST" id="form-owner-restore">
            @csrf
            @method('PATCH')
            <div class="pt-2">
                <label class="sr-only" for="owner_restore_confirmation">Konfirmasi</label>
                <input type="text" name="restore_confirmation" id="owner_restore_confirmation" required pattern="AKTIFKAN" title="Ketik AKTIFKAN" placeholder="Ketik AKTIFKAN"
                       data-uppercase-input
                       class="block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm uppercase shadow-sm placeholder:text-slate-400 placeholder:normal-case focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500 dark:focus:ring-emerald-500" />
            </div>
        </form>

        <x-slot name="footer">
            <button type="button" @click="$dispatch('close-modal', 'owner-restore-modal')" class="inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                Batal
            </button>
            <button type="submit" form="form-owner-restore" class="inline-flex w-full justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:w-auto">
                Ya, Aktifkan
            </button>
        </x-slot>
    </x-modal>

    <x-modal id="owner-destroy-modal" maxWidth="md" type="danger">
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
        </x-slot>
        <x-slot name="title">
            Nonaktifkan Owner
        </x-slot>
        <x-slot name="description">
            Ketik <b class="text-slate-900 dark:text-white">NONAKTIF</b> untuk mengonfirmasi penonaktifan akun owner ini.
        </x-slot>

        <form :action="destroyUrl" method="POST" id="form-owner-destroy">
            @csrf
            @method('DELETE')
            <div class="pt-2">
                <label class="sr-only" for="owner_destroy_confirmation">Konfirmasi</label>
                <input type="text" name="destroy_confirmation" id="owner_destroy_confirmation" required pattern="NONAKTIF" title="Ketik NONAKTIF" placeholder="Ketik NONAKTIF"
                       data-uppercase-input
                       class="block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm uppercase shadow-sm placeholder:text-slate-400 placeholder:normal-case focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-rose-500 dark:focus:ring-rose-500" />
            </div>
        </form>

        <x-slot name="footer">
            <button type="button" @click="$dispatch('close-modal', 'owner-destroy-modal')" class="inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                Batal
            </button>
            <button type="submit" form="form-owner-destroy" class="inline-flex w-full justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 sm:w-auto">
                Ya, Nonaktifkan
            </button>
        </x-slot>
    </x-modal>

</div>
@endsection
