@extends('layouts.app')

@section('title', 'Manajemen Backup Database')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- HEADER + BREADCRUMB --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
        <div class="flex-1">
            <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                <a href="{{ route('developer.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Developer</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-blue-600 dark:text-blue-400">Manajemen Backup</span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">Manajemen Backup Database</h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Mengelola, menjalankan secara manual, dan mengunduh riwayat backup database.
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

    @if (session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 px-4 py-3 rounded-xl flex items-start text-sm">
            <svg class="w-5 h-5 mr-2.5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="whitespace-pre-line">{{ session('error') }}</span>
        </div>
    @endif

    {{-- STAT CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <h2 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Total Backup</h2>
                <p class="text-xl font-bold text-slate-800 dark:text-white">{{ number_format($totalBackups) }}</p>
            </div>
            <div class="p-2 bg-blue-50 dark:bg-blue-500/10 text-blue-500 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg></div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <h2 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Berhasil</h2>
                <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($successCount) }}</p>
            </div>
            <div class="p-2 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg></div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <h2 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Gagal</h2>
                <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ number_format($failedCount) }}</p>
            </div>
            <div class="p-2 bg-red-50 dark:bg-red-500/10 text-red-500 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></div>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm flex items-center justify-between">
            <div>
                <h2 class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Total Ukuran</h2>
                @php
                    $sizeDisplay = $totalSize >= 1048576 
                        ? number_format($totalSize / 1048576, 2) . ' MB'
                        : number_format($totalSize / 1024, 2) . ' KB';
                @endphp
                <p class="text-xl font-bold text-slate-800 dark:text-white">{{ $totalSize > 0 ? $sizeDisplay : '0 KB' }}</p>
            </div>
            <div class="p-2 bg-amber-50 dark:bg-amber-500/10 text-amber-500 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg></div>
        </div>
    </div>

    {{-- JADWAL BACKUP OTOMATIS --}}
    <div class="bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/50 rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="p-2 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-lg shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                    Jadwal Backup Otomatis
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Aktif
                    </span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Berjalan otomatis via sistem setiap: <strong>Harian (01:00)</strong>, <strong>Mingguan (Senin 02:00)</strong>, dan <strong>Bulanan (Tgl 1 03:00)</strong>. File berumur lebih dari 30 hari akan dihapus otomatis.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- BACKUP TERAKHIR --}}
        @if($lastBackup)
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Backup Terakhir
                </h2>
                @if($lastBackup->status === 'success')
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 uppercase tracking-widest">Sukses</span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold bg-red-50 text-red-600 border border-red-200 dark:bg-red-900/30 dark:border-red-800/50 dark:text-red-400 uppercase tracking-widest">Gagal</span>
                @endif
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-3 border border-slate-100 dark:border-slate-800">
                <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate" title="{{ $lastBackup->file_name }}">{{ $lastBackup->file_name }}</p>
                <div class="flex flex-wrap items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> {{ $lastBackup->created_at->format('d M Y, H:i') }}</span>
                    @if($lastBackup->file_size)
                        <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg> {{ $lastBackup->file_size >= 1048576 ? number_format($lastBackup->file_size / 1048576, 2) . ' MB' : number_format($lastBackup->file_size / 1024, 2) . ' KB' }}</span>
                    @endif
                    @if($lastBackup->user)
                        <span class="flex items-center gap-1 ml-auto"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg> {{ $lastBackup->user->name }}</span>
                    @endif
                </div>
            </div>

            @if($lastBackup->status === 'failed' && $lastBackup->error_message)
                <div class="mt-3 rounded-lg border border-red-200 dark:border-red-900/40 bg-red-50 dark:bg-red-900/10 p-3">
                    <p class="text-[11px] text-red-600 dark:text-red-400 whitespace-pre-line break-all font-mono leading-relaxed">{{ Str::limit($lastBackup->error_message, 150) }}</p>
                </div>
            @endif
        </div>
        @else
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm flex items-center justify-center text-slate-400 text-sm">
            Belum ada data backup terakhir.
        </div>
        @endif

        {{-- RESTORE / IMPORT DARI FILE --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-5 shadow-sm">
            <h2 class="text-sm font-bold text-slate-800 dark:text-white mb-1 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Restore Manual
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Upload file backup PostgreSQL (.backup atau .dump) untuk memulihkan database.</p>
            
            <form action="{{ route('developer.backups.restore-upload') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3">
                @csrf
                <input type="file" name="backup_file" required accept=".dump,.backup"
                       class="w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-600 dark:file:bg-blue-500/10 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-500/20 file:transition file:cursor-pointer bg-slate-50 dark:bg-slate-800/50 rounded-lg border border-slate-200 dark:border-slate-700">
                <input type="text" name="restore_confirmation" required placeholder="Ketik RESTORE untuk konfirmasi"
                       class="w-full rounded-lg border border-amber-200 bg-amber-50/60 px-3 py-2 text-xs font-semibold text-slate-700 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-slate-200">
                
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold rounded-lg transition-all shadow-sm"
                        style="background-color: #d97706; color: #fff;"
                        onclick="return confirm('PERHATIAN: Proses restore akan menimpa data yang ada saat ini di database.\n\nLanjutkan proses restore?')">
                    Upload & Restore
                </button>
            </form>
        </div>
    </div>

    {{-- TABEL RIWAYAT --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-800/20">
            <div class="flex items-center gap-3">
                <h2 class="text-sm font-bold text-slate-800 dark:text-white">Riwayat Backup</h2>
                <span class="inline-flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $totalBackups }}</span>
            </div>
            <form action="{{ route('developer.backups.create') }}" method="POST">
                @csrf
                <button type="submit"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-bold rounded-lg transition-all shadow-sm"
                   style="background-color: #059669; color: #fff;">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Proses Backup Baru
                </button>
            </form>
        </div>

        {{-- Desktop Table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400 bg-slate-50 dark:bg-slate-800/60">
                    <tr>
                        <th class="px-6 py-3 text-left">Waktu</th>
                        <th class="px-6 py-3 text-left">Nama File</th>
                        <th class="px-6 py-3 text-left">Operator</th>
                        <th class="px-6 py-3 text-left">Ukuran</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr class="border-t border-slate-200 dark:border-slate-800">
                            <td class="px-6 py-4 text-slate-500 whitespace-nowrap">{{ $backup->created_at->format('d M Y H:i') }}</td>
                            <td class="px-6 py-4 font-medium text-slate-800 dark:text-slate-100">
                                <span class="truncate block max-w-xs" title="{{ $backup->file_name }}">{{ $backup->file_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ $backup->user->name ?? '-' }}</td>
                            <td class="px-6 py-4 font-semibold {{ $backup->file_size ? 'text-slate-700 dark:text-slate-200' : 'text-slate-400' }} whitespace-nowrap">
                                @if($backup->file_size)
                                    {{ $backup->file_size >= 1048576 
                                        ? number_format($backup->file_size / 1048576, 2) . ' MB'
                                        : number_format($backup->file_size / 1024, 2) . ' KB' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($backup->status === 'success')
                                    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider border bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400">
                                        Sukses
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[10px] font-bold uppercase tracking-wider border bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:border-red-800/50 dark:text-red-400" title="{{ $backup->error_message }}">
                                        Gagal
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($backup->status === 'success')
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('developer.backups.download', $backup->id) }}" 
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 text-[12px] font-bold text-emerald-600 transition hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20"
                                           title="Unduh file backup">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                            Unduh
                                        </a>
                                        <form action="{{ route('developer.backups.restore', $backup->id) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="text" name="restore_confirmation" required placeholder="RESTORE"
                                                   class="w-24 rounded-lg border border-amber-200 bg-amber-50/60 px-2 py-1.5 text-[11px] font-semibold text-slate-700 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-slate-200">
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-[12px] font-bold text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                                    title="Restore database dari file ini"
                                                    onclick="return confirm('PERHATIAN: Proses restore akan menimpa data yang ada saat ini di database.\n\nApakah Anda yakin ingin me-restore dari backup ini?')">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                Restore
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-slate-300 dark:text-slate-600">—</span>
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

        {{-- Mobile Cards --}}
        <div class="md:hidden divide-y divide-slate-200 dark:divide-slate-800">
            @forelse ($backups as $backup)
                <div class="px-4 py-3 space-y-1.5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 break-words">
                            {{ $backup->file_name }}
                        </p>
                        @if($backup->status === 'success')
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Sukses</span>
                        @else
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">Gagal</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500">{{ $backup->created_at->format('d M Y H:i') }}</p>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-slate-600 dark:text-slate-300">
                            @if($backup->file_size)
                                {{ $backup->file_size >= 1048576 ? number_format($backup->file_size / 1048576, 2) . ' MB' : number_format($backup->file_size / 1024, 2) . ' KB' }}
                            @else
                                —
                            @endif
                        </p>
                        @if($backup->status === 'success')
                            <a href="{{ route('developer.backups.download', $backup->id) }}" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 hover:text-emerald-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Unduh
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    Belum ada riwayat backup database.
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
