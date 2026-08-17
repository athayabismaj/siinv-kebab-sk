@extends('layouts.app')

@section('title', 'Manajemen Backup Database')

@section('content')
@php
    $formatSize = static function ($bytes): string {
        $bytes = (float) ($bytes ?? 0);
        if ($bytes <= 0) return '0 KB';
        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 2) . ' MB'
            : number_format($bytes / 1024, 2) . ' KB';
    };

    $successRate = $totalBackups > 0 ? round(($successCount / $totalBackups) * 100) : 0;
@endphp

<div
    x-data="{
        restoreUrl: '',
        fileName: '',
        openRestoreRiwayat(url, name) {
            this.restoreUrl = url;
            this.fileName = name;
            document.getElementById('restore_riwayat_confirmation').value = '';
            $dispatch('open-modal', 'restore-riwayat-modal');
        },
        openRestoreManual() {
            document.getElementById('form-restore-manual').reset();
            $dispatch('open-modal', 'restore-manual-modal');
        }
    }"
    class="w-full space-y-8 pb-16">

    <!-- Header Section -->
    <x-page-header 
        title="Manajemen Backup" 
        subtitle="Kelola arsip database, lakukan pemulihan sistem, dan pantau jadwal pencadangan otomatis." 
        breadcrumb-parent="Super Admin" 
        breadcrumb-child="Backup Database">
        
        <div class="flex items-center gap-3">
            <button type="button"
                    @click="openRestoreManual()"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-xs font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white dark:focus:ring-slate-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Restore Manual
            </button>

            <form action="{{ route('developer.backups.create') }}" method="POST">
                @csrf
                <button type="submit"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-200 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 dark:focus:ring-slate-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v10m0 0l-4-4m4 4l4-4M5 20h14"></path></svg>
                    Buat Backup
                </button>
            </form>
        </div>
    </x-page-header>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['label' => 'Total Backup', 'value' => number_format($totalBackups), 'note' => 'Arsip tersimpan', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>', 'color' => 'text-slate-700 dark:text-slate-300'],
            ['label' => 'Berhasil', 'value' => number_format($successCount), 'note' => $successRate . '% Tingkat sukses', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>', 'color' => 'text-emerald-600 dark:text-emerald-400'],
            ['label' => 'Gagal', 'value' => number_format($failedCount), 'note' => 'Membutuhkan pengecekan', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>', 'color' => 'text-rose-600 dark:text-rose-400'],
            ['label' => 'Total Ukuran', 'value' => $formatSize($totalSize), 'note' => 'Kapasitas penyimpanan', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>', 'color' => 'text-blue-600 dark:text-blue-400'],
        ] as $metric)
            <div class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $metric['label'] }}</p>
                    <span class="{{ $metric['color'] }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $metric['icon'] !!}</svg>
                    </span>
                </div>
                <div class="mt-4">
                    <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $metric['value'] }}</h3>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $metric['note'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Status Overview -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Last Backup Details -->
        <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Status Backup Terakhir
                </div>
                @if($lastBackup)
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $lastBackup->status === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $lastBackup->status === 'success' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        {{ $lastBackup->status === 'success' ? 'Berhasil' : 'Gagal' }}
                    </span>
                @endif
            </div>
            
            @if($lastBackup)
                <div class="mt-5 flex flex-1 flex-col justify-between">
                    <div>
                        <h2 class="truncate text-xl font-bold text-slate-900 dark:text-white" title="{{ $lastBackup->file_name }}">
                            {{ $lastBackup->file_name }}
                        </h2>
                        <div class="mt-3 flex flex-wrap items-center text-sm text-slate-500 dark:text-slate-400 gap-y-2">
                            <span class="flex items-center" style="margin-right: 1.5rem;">
                                <svg class="h-4 w-4 text-slate-400" style="margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                {{ $lastBackup->created_at->format('d M Y, H:i') }}
                            </span>
                            <span class="flex items-center" style="margin-right: 1.5rem;">
                                <svg class="h-4 w-4 text-slate-400" style="margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                                {{ $formatSize($lastBackup->file_size) }}
                            </span>
                            <span class="flex items-center">
                                <svg class="h-4 w-4 text-slate-400" style="margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                {{ $lastBackup->user->name ?? 'Sistem Otomatis' }}
                            </span>
                        </div>
                    </div>

                    @if($lastBackup->status === 'success')
                        <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5 dark:border-slate-800/80">
                            <a href="{{ route('developer.backups.download', $lastBackup->id) }}" class="inline-flex flex-1 items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition-all hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700 sm:flex-none">
                                <svg class="mr-2 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Unduh File
                            </a>
                            <button type="button" @click="openRestoreRiwayat('{{ route('developer.backups.restore', $lastBackup->id) }}', '{{ addslashes($lastBackup->file_name) }}')" class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 sm:flex-none">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Pulihkan
                            </button>
                        </div>
                    @elseif($lastBackup->status === 'failed' && $lastBackup->error_message)
                        <div class="mt-4 rounded-xl border border-rose-100 bg-rose-50/50 p-4 dark:border-rose-900/30 dark:bg-rose-500/5">
                            <p class="font-mono text-xs text-rose-600 dark:text-rose-400">{{ $lastBackup->error_message }}</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-5 flex flex-1 flex-col justify-center">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">Belum Ada Backup</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Jalankan proses backup pertama Anda untuk mengamankan data.</p>
                </div>
            @endif
        </div>

        <!-- Automation Settings -->
        <div class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Otomatisasi
                </div>
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Aktif
                </span>
            </div>
            
            <div class="mt-5 flex flex-1 flex-col justify-between">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Jadwal Backup</h2>
                <div class="mt-4 flex flex-col gap-2.5">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2.5 dark:bg-slate-800/50">
                        <div class="flex items-center">
                            <span class="w-24 text-sm font-bold text-slate-700 dark:text-slate-300">Harian</span>
                        </div>
                        <span class="rounded-md bg-white px-2 py-1 text-xs font-black tracking-wider text-slate-500 shadow-sm ring-1 ring-slate-200/50 dark:bg-slate-900 dark:ring-slate-700/50">01:00</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2.5 dark:bg-slate-800/50">
                        <div class="flex items-center">
                            <span class="w-24 text-sm font-bold text-slate-700 dark:text-slate-300">Mingguan</span>
                            <div x-data="{ open: false }" @click.away="open = false" class="flex items-center gap-1.5 text-slate-400 dark:text-slate-500">
                                <button type="button" @click="open = !open" @mouseenter="open = true" @mouseleave="open = false" class="cursor-help transition hover:text-slate-600 focus:outline-none dark:hover:text-slate-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <span x-show="open" x-transition.opacity style="display: none;" class="text-xs font-medium">Senin</span>
                            </div>
                        </div>
                        <span class="rounded-md bg-white px-2 py-1 text-xs font-black tracking-wider text-slate-500 shadow-sm ring-1 ring-slate-200/50 dark:bg-slate-900 dark:ring-slate-700/50">02:00</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2.5 dark:bg-slate-800/50">
                        <div class="flex items-center">
                            <span class="w-24 text-sm font-bold text-slate-700 dark:text-slate-300">Bulanan</span>
                            <div x-data="{ open: false }" @click.away="open = false" class="flex items-center gap-1.5 text-slate-400 dark:text-slate-500">
                                <button type="button" @click="open = !open" @mouseenter="open = true" @mouseleave="open = false" class="cursor-help transition hover:text-slate-600 focus:outline-none dark:hover:text-slate-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </button>
                                <span x-show="open" x-transition.opacity style="display: none;" class="text-xs font-medium">Tgl 1</span>
                            </div>
                        </div>
                        <span class="rounded-md bg-white px-2 py-1 text-xs font-black tracking-wider text-slate-500 shadow-sm ring-1 ring-slate-200/50 dark:bg-slate-900 dark:ring-slate-700/50">03:00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col border-b border-slate-100 px-6 py-5 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Riwayat Pengarsipan</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Daftar {{ number_format($backups->total()) }} file backup yang tersedia.</p>
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="border-b border-slate-100 bg-slate-50/50 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Waktu</th>
                        <th class="px-6 py-4 font-semibold">Nama File</th>
                        <th class="px-6 py-4 font-semibold">Operator</th>
                        <th class="px-6 py-4 font-semibold">Ukuran</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($backups as $backup)
                        <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-6 py-4">{{ $backup->created_at->format('d M Y, H:i') }}</td>
                            <td class="px-6 py-4">
                                <span class="font-medium text-slate-900 dark:text-slate-200" title="{{ $backup->file_name }}">{{ Str::limit($backup->file_name, 40) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">{{ $backup->user->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium">{{ $formatSize($backup->file_size) }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $backup->status === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $backup->status === 'success' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ $backup->status === 'success' ? 'Berhasil' : 'Gagal' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                @if($backup->status === 'success')
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('developer.backups.download', $backup->id) }}"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:text-slate-500 dark:hover:bg-blue-500/10 dark:hover:text-blue-400" title="Unduh File">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        </a>
                                        <button type="button"
                                                @click="openRestoreRiwayat('{{ route('developer.backups.restore', $backup->id) }}', '{{ addslashes($backup->file_name) }}')"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-slate-500 dark:hover:bg-rose-500/10 dark:hover:text-rose-400" title="Pulihkan Data">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 dark:text-slate-500">Tidak tersedia</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                Belum ada riwayat backup database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="flex flex-col gap-3 p-4 md:hidden">
            @forelse ($backups as $backup)
                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-800 dark:bg-slate-800/30">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate font-bold text-slate-900 dark:text-slate-200" title="{{ $backup->file_name }}">{{ $backup->file_name }}</h3>
                            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $backup->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-black uppercase tracking-wider {{ $backup->status === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' }}">
                            {{ $backup->status === 'success' ? 'Berhasil' : 'Gagal' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-200/60 pt-3 dark:border-slate-700/60">
                        <div class="flex flex-col gap-0.5 text-xs">
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $formatSize($backup->file_size) }}</span>
                            <span class="font-medium text-slate-500">Oleh {{ $backup->user->name ?? 'Sistem' }}</span>
                        </div>
                        @if($backup->status === 'success')
                            <div class="flex items-center gap-2">
                                <a href="{{ route('developer.backups.download', $backup->id) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white text-slate-500 shadow-sm ring-1 ring-slate-200/80 transition-colors hover:text-blue-600 dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700/80 dark:hover:text-blue-400" title="Unduh File">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </a>
                                <button type="button" @click="openRestoreRiwayat('{{ route('developer.backups.restore', $backup->id) }}', '{{ addslashes($backup->file_name) }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-white shadow-sm transition-colors hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600" title="Pulihkan Data">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                </button>
                            </div>
                        @else
                            <span class="text-xs font-medium text-slate-400">Tidak tersedia</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                    Belum ada riwayat backup database.
                </div>
            @endforelse
        </div>

    </div>

    @if(method_exists($backups, 'hasPages') && $backups->hasPages())
        <div class="mt-8">
            {{ $backups->links() }}
        </div>
    @endif

    <!-- Modals -->
    <x-modal id="restore-manual-modal" maxWidth="lg" type="warning">
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
        </x-slot>
        <x-slot name="title">
            Restore Database Manual
        </x-slot>
        <x-slot name="description">
            Pulihkan data aplikasi dari file backup PostgreSQL.
        </x-slot>

        <form action="{{ route('developer.backups.restore-upload') }}" method="POST" enctype="multipart/form-data" id="form-restore-manual"
              x-data="{ input: '', schema: 'laravel', fileName: '' }"
              @open-modal.window="if ($event.detail === 'restore-manual-modal') { input = ''; schema = 'laravel'; fileName = ''; $nextTick(() => $refs.file.value = '') }">
            @csrf
            <div class="space-y-5 pt-1">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200" for="backup_file">File backup</label>
                    <input x-ref="file" type="file" name="backup_file" id="backup_file" required accept=".dump,.backup" class="sr-only"
                           @change="fileName = $event.target.files[0]?.name || ''">
                    <label for="backup_file" class="group flex min-h-20 cursor-pointer items-center gap-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 px-4 py-3 transition hover:border-amber-400 hover:bg-amber-50/50 focus-within:ring-4 focus-within:ring-amber-100 dark:border-slate-700 dark:bg-slate-800/50 dark:hover:border-amber-500/70 dark:hover:bg-amber-500/5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm ring-1 ring-slate-200 transition group-hover:ring-amber-200 dark:bg-slate-900 dark:text-amber-400 dark:ring-slate-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="fileName || 'Pilih file backup'"></span>
                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400" x-text="fileName ? 'Klik untuk mengganti file' : 'Format .dump atau .backup, maksimal 100 MB'"></span>
                        </span>
                        <span class="hidden shrink-0 rounded-lg bg-white px-2.5 py-1 text-[11px] font-bold text-slate-500 ring-1 ring-slate-200 sm:inline-flex dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-700">PILIH FILE</span>
                    </label>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">Schema aplikasi</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Pilih sesuai sumber backup</span>
                    </div>
                    <div class="grid grid-cols-1 auto-rows-fr gap-2.5 sm:grid-cols-2">
                        <label class="relative block h-full cursor-pointer">
                            <input type="radio" name="backup_schema" value="laravel" x-model="schema" class="peer sr-only" required>
                            <span class="flex h-full min-h-[4.75rem] items-center gap-3 rounded-xl border border-slate-200 px-3.5 py-3 transition peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:ring-1 peer-checked:ring-blue-500 dark:border-slate-700 dark:peer-checked:border-blue-400 dark:peer-checked:bg-blue-500/10 dark:peer-checked:ring-blue-400">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-sm font-black text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">L</span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-900 dark:text-white">laravel</span>
                                    <span class="block whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">Supabase &amp; lokal</span>
                                </span>
                                <svg x-show="schema === 'laravel'" x-cloak class="ml-auto h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.296-7.293a1 1 0 011.408 0z" clip-rule="evenodd"></path></svg>
                            </span>
                        </label>
                        <label class="relative block h-full cursor-pointer">
                            <input type="radio" name="backup_schema" value="public" x-model="schema" class="peer sr-only" required>
                            <span class="flex h-full min-h-[4.75rem] items-center gap-3 rounded-xl border border-slate-200 px-3.5 py-3 transition peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:ring-1 peer-checked:ring-blue-500 dark:border-slate-700 dark:peer-checked:border-blue-400 dark:peer-checked:bg-blue-500/10 dark:peer-checked:ring-blue-400">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">P</span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-900 dark:text-white">public</span>
                                    <span class="block whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">Backup lokal lama</span>
                                </span>
                                <svg x-show="schema === 'public'" x-cloak class="ml-auto h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.296-7.293a1 1 0 011.408 0z" clip-rule="evenodd"></path></svg>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="flex items-start gap-3 rounded-xl bg-blue-50 px-3.5 py-3 text-xs leading-relaxed text-blue-800 dark:bg-blue-500/10 dark:text-blue-300">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p>Hanya schema aplikasi yang dipulihkan. Schema internal Supabase seperti <b>auth</b>, <b>storage</b>, dan <b>realtime</b> tetap aman.</p>
                </div>
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="text-sm font-semibold text-slate-800 dark:text-slate-200" for="restore_confirmation">Konfirmasi restore</label>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Ketik <b class="text-slate-700 dark:text-slate-200">RESTORE</b></span>
                    </div>
                    <input type="text" name="restore_confirmation" id="restore_confirmation" 
                           x-model="input"
                           placeholder="RESTORE"
                           class="block w-full rounded-xl border-slate-300 px-4 py-3 text-sm font-semibold tracking-wide shadow-sm placeholder:font-normal placeholder:tracking-normal placeholder:text-slate-400 focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-rose-500 dark:focus:ring-rose-500"
                           autocomplete="off"
                           @keydown.enter.prevent="if(input.toLowerCase() === 'restore') $el.closest('form').submit()" />
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'restore-manual-modal')" class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit" 
                        :disabled="input.toLowerCase() !== 'restore'"
                        class="inline-flex w-full justify-center rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 sm:w-auto disabled:cursor-not-allowed disabled:opacity-40">
                    Mulai Restore
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal id="restore-riwayat-modal" maxWidth="md" type="warning">
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </x-slot>
        <x-slot name="title">
            Restore dari Riwayat
        </x-slot>
        <x-slot name="description">
            Pulihkan data aplikasi dari snapshot yang tersimpan.
        </x-slot>

        <form :action="restoreUrl" method="POST" id="form-restore-riwayat" x-data="{ input: '' }" @open-modal.window="if($event.detail === 'restore-riwayat-modal') input = ''">
            @csrf
            <div class="space-y-5 pt-1">
                <div>
                    <span class="mb-2 block text-sm font-semibold text-slate-800 dark:text-slate-200">File backup</span>
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3.5 dark:border-slate-700 dark:bg-slate-800/50">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:text-amber-400 dark:ring-slate-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <b class="block truncate text-sm text-slate-900 dark:text-white" x-text="fileName"></b>
                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Snapshot database terverifikasi</span>
                        </span>
                        <span class="shrink-0 rounded-lg bg-emerald-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">Siap</span>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-xl bg-amber-50 px-3.5 py-3 text-xs leading-relaxed text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z"></path></svg>
                    <p>Data pada schema tujuan akan diganti dengan isi snapshot ini. Sistem membuat backup pengaman sebelum restore dimulai.</p>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="text-sm font-semibold text-slate-800 dark:text-slate-200" for="restore_riwayat_confirmation">Konfirmasi restore</label>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Ketik <b class="text-slate-700 dark:text-slate-200">RESTORE</b></span>
                    </div>
                <input type="text" name="restore_confirmation" id="restore_riwayat_confirmation" 
                       x-model="input"
                       placeholder="RESTORE"
                       class="block w-full rounded-xl border-slate-300 px-4 py-3 text-sm font-semibold tracking-wide shadow-sm placeholder:font-normal placeholder:tracking-normal placeholder:text-slate-400 focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-rose-500 dark:focus:ring-rose-500"
                       autocomplete="off"
                       @keydown.enter.prevent="if(input.toLowerCase() === 'restore') $el.closest('form').submit()" />
                </div>
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end dark:border-slate-800">
                <button type="button" @click="$dispatch('close-modal', 'restore-riwayat-modal')" class="inline-flex w-full justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 transition hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit" 
                        :disabled="input.toLowerCase() !== 'restore'"
                        class="inline-flex w-full justify-center rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500 sm:w-auto disabled:cursor-not-allowed disabled:opacity-40">
                    Mulai Restore
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Hidden form for Swal file upload -->
    <form id="hidden-upload-form" action="{{ route('developer.backups.restore-upload') }}" method="POST" enctype="multipart/form-data" class="hidden">
        @csrf
        <input type="hidden" name="backup_schema" value="laravel">
    </form>

</div>
@endsection
