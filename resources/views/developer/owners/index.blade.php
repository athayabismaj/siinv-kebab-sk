@extends('layouts.app')

@section('title', 'Manajemen Owner')

@push('styles')
<style>
    .owner-create-modal-shell {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        overflow-y: auto;
        background: rgba(2, 6, 23, 0.72);
        padding: 1rem;
    }

    .owner-create-modal-panel {
        width: 100%;
        max-width: 560px;
        max-height: calc(100dvh - 2rem);
        overflow-y: auto;
        margin: auto 0;
    }

    @media (min-width: 768px) {
        .owner-create-modal-shell {
            left: 16rem;
            padding: 1.5rem;
        }

        .owner-create-modal-panel {
            max-height: calc(100dvh - 3rem);
        }
    }

    .owner-management-hero,
    .owner-management-stat,
    .owner-management-panel {
        border: 1px solid rgb(226 232 240);
        background: rgb(255 255 255);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .dark .owner-management-hero,
    .dark .owner-management-stat,
    .dark .owner-management-panel {
        border-color: rgb(30 41 59);
        background: rgb(15 23 42);
        box-shadow: none;
    }

    .owner-management-stats {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
    }

    .owner-management-stat {
        display: flex;
        min-height: 88px;
        align-items: center;
        gap: 14px;
        border-radius: 12px;
        padding: 14px;
    }

    .owner-management-stat-icon,
    .owner-management-empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        box-shadow: inset 0 0 0 1px currentColor;
    }

    .owner-management-stat-icon {
        width: 42px;
        height: 42px;
        flex-shrink: 0;
    }

    .owner-management-empty {
        display: flex;
        min-height: 240px;
        align-items: center;
        justify-content: center;
        padding: 32px 18px;
        text-align: center;
    }

    .owner-management-empty-icon {
        width: 46px;
        height: 46px;
        margin: 0 auto;
    }

    .owner-tone-blue {
        background: rgb(239 246 255);
        color: rgb(37 99 235);
    }

    .owner-tone-emerald {
        background: rgb(236 253 245);
        color: rgb(5 150 105);
    }

    .owner-tone-amber {
        background: rgb(255 251 235);
        color: rgb(217 119 6);
    }

    .dark .owner-tone-blue {
        background: rgba(37, 99, 235, 0.12);
        color: rgb(96 165 250);
    }

    .dark .owner-tone-emerald {
        background: rgba(16, 185, 129, 0.12);
        color: rgb(52 211 153);
    }

    .dark .owner-tone-amber {
        background: rgba(245, 158, 11, 0.12);
        color: rgb(251 191 36);
    }

    @media (min-width: 768px) {
        .owner-management-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
</style>
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
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-xs font-black text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/15 sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"></path></svg>
            Tambah Owner
        </button>
    </x-page-header>

    <section class="owner-management-stats">
        <article class="owner-management-stat">
            <span class="owner-management-stat-icon owner-tone-blue">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m8-4.13a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </span>
            <div class="min-w-0">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Total Owner</p>
                <p class="mt-1 text-2xl font-black leading-none text-slate-900 dark:text-white">{{ number_format($totalOwners) }}</p>
                <p class="mt-1 truncate text-[10px] font-semibold text-slate-500 dark:text-slate-400">akun aktif terdaftar</p>
            </div>
        </article>

        <article class="owner-management-stat">
            <span class="owner-management-stat-icon owner-tone-emerald">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0118.5 14.5c0 2.485-2.91 4.5-6.5 4.5s-6.5-2.015-6.5-4.5c0-1.362.286-2.69.84-3.922L12 14z"></path></svg>
            </span>
            <div class="min-w-0">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Owner Terbaru</p>
                <p class="mt-1 truncate text-sm font-black text-slate-900 dark:text-white">{{ $latestOwner->name ?? 'Belum ada owner' }}</p>
                <p class="mt-1 truncate text-[10px] font-semibold text-slate-500 dark:text-slate-400">{{ optional(optional($latestOwner)->created_at)->format('d M Y H:i') ?? 'belum ada data' }}</p>
            </div>
        </article>

        <article class="owner-management-stat">
            <span class="owner-management-stat-icon owner-tone-amber">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3.75l7.5 3.75v4.75c0 4.55-3.075 7.412-7.5 8.5-4.425-1.088-7.5-3.95-7.5-8.5V7.5L12 3.75z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 12l1.5 1.5 3.25-3.25"></path></svg>
            </span>
            <div class="min-w-0">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Akses Role</p>
                <p class="mt-1 truncate text-sm font-black text-slate-900 dark:text-white">Owner Panel</p>
                <p class="mt-1 truncate text-[10px] font-semibold text-slate-500 dark:text-slate-400">dashboard, laporan, pengguna</p>
            </div>
        </article>
    </section>

    <section class="owner-management-panel overflow-hidden rounded-xl">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Daftar Akun Owner</h2>
                <p class="mt-0.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ number_format($totalOwners) }} data owner</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:text-slate-400">
                Role Owner
            </span>
        </div>

        @if ($owners->isEmpty())
            <div class="owner-management-empty">
                <div class="max-w-sm">
                    <span class="owner-management-empty-icon owner-tone-blue">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 20.25a7.5 7.5 0 0115 0"></path></svg>
                    </span>
                    <h3 class="mt-4 text-sm font-black text-slate-900 dark:text-white">Belum ada akun owner</h3>
                    <p class="mt-1 text-xs font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                        Tambahkan akun owner pertama agar pemilik usaha bisa mengakses laporan, pengguna, dan dashboard owner.
                    </p>
                    <button type="button"
                            @click="createOwnerOpen = true"
                            class="mt-4 inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-xs font-black text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"></path></svg>
                        Tambah Owner
                    </button>
                </div>
            </div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 dark:bg-slate-800/60">
                        <tr>
                            <th class="w-16 px-4 py-3 text-left font-black">No</th>
                            <th class="px-4 py-3 text-left font-black">Nama</th>
                            <th class="px-4 py-3 text-left font-black">Username</th>
                            <th class="px-4 py-3 text-left font-black">Email</th>
                            <th class="px-4 py-3 text-right font-black">Dibuat Pada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($owners as $index => $owner)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/30">
                                <td class="px-4 py-3 text-xs font-black tabular-nums text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-black text-white">
                                            {{ strtoupper(substr($owner->name, 0, 1)) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ $owner->name }}</p>
                                            <p class="mt-0.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400">Owner</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-slate-600 dark:text-slate-300">{{ '@' . $owner->username }}</td>
                                <td class="px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $owner->email }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $owner->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
                @foreach ($owners as $owner)
                    <article class="space-y-3 px-4 py-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-black text-white">
                                {{ strtoupper(substr($owner->name, 0, 1)) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ $owner->name }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ '@' . $owner->username }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-1 pl-12 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <p class="truncate">{{ $owner->email }}</p>
                            <p>{{ $owner->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

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
</div>
@endsection
