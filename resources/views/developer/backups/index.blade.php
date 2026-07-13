@extends('layouts.app')

@section('title', 'Manajemen Backup Database')

@section('content')
@php
    $formatSize = static function ($bytes): string {
        $bytes = (float) ($bytes ?? 0);

        if ($bytes <= 0) {
            return '0 KB';
        }

        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 2) . ' MB'
            : number_format($bytes / 1024, 2) . ' KB';
    };

    $successRate = $totalBackups > 0 ? round(($successCount / $totalBackups) * 100) : 0;
@endphp

<div
    x-data="{
        restoreUploadOpen: false,
        restoreHistoryOpen: false,
        restoreAction: '',
        restoreName: '',
        restoreUploadFileName: '',
    }"
    @keydown.escape.window="restoreUploadOpen = false; restoreHistoryOpen = false"
    class="space-y-4">

    <x-page-header 
        title="Manajemen Backup" 
        subtitle="Kelola backup, unduh arsip, dan restore database dengan konfirmasi aman." 
        breadcrumb-parent="Super Admin" 
        breadcrumb-child="Backup Database">
        
        <button type="button"
                @click="restoreUploadOpen = true"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-xs font-black text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Restore Manual
        </button>

        <form action="{{ route('developer.backups.create') }}" method="POST">
            @csrf
            <button type="submit"
                    class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-xs font-black text-white shadow-sm shadow-blue-500/20 transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-500/15 sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v10m0 0l-4-4m4 4l4-4M5 20h14"></path></svg>
                Backup Baru
            </button>
        </form>
    </x-page-header>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total Backup', 'value' => number_format($totalBackups), 'note' => 'riwayat tersimpan', 'tone' => 'slate'],
            ['label' => 'Berhasil', 'value' => number_format($successCount), 'note' => $successRate . '% sukses', 'tone' => 'emerald'],
            ['label' => 'Gagal', 'value' => number_format($failedCount), 'note' => 'butuh pengecekan', 'tone' => 'rose'],
            ['label' => 'Ukuran Backup', 'value' => $formatSize($totalSize), 'note' => 'total file sukses', 'tone' => 'blue'],
        ] as $stat)
            @php
                $valueClass = match ($stat['tone']) {
                    'emerald' => 'text-emerald-600 dark:text-emerald-400',
                    'rose' => 'text-rose-600 dark:text-rose-400',
                    'blue' => 'text-blue-600 dark:text-blue-400',
                    default => 'text-slate-900 dark:text-white',
                };
            @endphp
            <article class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="truncate text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $stat['label'] }}</p>
                <p class="mt-2 truncate text-xl font-black leading-none {{ $valueClass }}">{{ $stat['value'] }}</p>
                <p class="mt-1 truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $stat['note'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="grid grid-cols-1 gap-3 xl:grid-cols-[minmax(0,1fr)_360px]">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Backup Terakhir</p>
                    @if($lastBackup)
                        <h2 class="mt-1 truncate text-base font-black text-slate-900 dark:text-white" title="{{ $lastBackup->file_name }}">
                            {{ $lastBackup->file_name }}
                        </h2>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                            <span>{{ $lastBackup->created_at->format('d M Y H:i') }}</span>
                            <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span>{{ $formatSize($lastBackup->file_size) }}</span>
                            <span class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span>{{ $lastBackup->user->name ?? 'Sistem' }}</span>
                        </div>
                    @else
                        <h2 class="mt-1 text-base font-black text-slate-900 dark:text-white">Belum ada backup</h2>
                        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Jalankan backup pertama untuk membuat arsip.</p>
                    @endif
                </div>

                @if($lastBackup)
                    <span class="inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-[9px] font-black uppercase tracking-wider {{ $lastBackup->status === 'success' ? 'border-emerald-200 text-emerald-700 dark:border-emerald-900/60 dark:text-emerald-300' : 'border-rose-200 text-rose-700 dark:border-rose-900/60 dark:text-rose-300' }}">
                        {{ $lastBackup->status === 'success' ? 'Sukses' : 'Gagal' }}
                    </span>
                @endif
            </div>

            @if($lastBackup && $lastBackup->status === 'failed' && $lastBackup->error_message)
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 p-3 dark:border-rose-900/60 dark:bg-rose-500/10">
                    <p class="break-all font-mono text-[11px] leading-relaxed text-rose-700 dark:text-rose-300">{{ Str::limit($lastBackup->error_message, 160) }}</p>
                </div>
            @endif
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Jadwal Otomatis</p>
                    <h2 class="mt-1 text-sm font-black text-slate-900 dark:text-white">Backup Aktif</h2>
                    <p class="mt-1 text-[11px] font-medium leading-relaxed text-slate-500 dark:text-slate-400">
                        Harian 01:00, mingguan Senin 02:00, bulanan tanggal 1 pukul 03:00.
                    </p>
                </div>
                <span class="mt-0.5 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-emerald-700 dark:border-emerald-900/60 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Aktif
                </span>
            </div>
        </article>
    </section>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Riwayat Backup</h2>
                <p class="mt-0.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ number_format($backups->total()) }} data backup</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:text-slate-400">
                PostgreSQL Archive
            </span>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 dark:bg-slate-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-black">Waktu</th>
                        <th class="px-4 py-3 text-left font-black">Nama File</th>
                        <th class="px-4 py-3 text-left font-black">Operator</th>
                        <th class="px-4 py-3 text-left font-black">Ukuran</th>
                        <th class="px-4 py-3 text-center font-black">Status</th>
                        <th class="px-4 py-3 text-right font-black">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($backups as $backup)
                        <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/30">
                            <td class="whitespace-nowrap px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $backup->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <p class="max-w-md truncate text-xs font-black text-slate-800 dark:text-slate-100" title="{{ $backup->file_name }}">{{ $backup->file_name }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $backup->user->name ?? '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs font-black text-slate-700 dark:text-slate-200">{{ $formatSize($backup->file_size) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $backup->status === 'success' ? 'border-emerald-200 text-emerald-700 dark:border-emerald-900/60 dark:text-emerald-300' : 'border-rose-200 text-rose-700 dark:border-rose-900/60 dark:text-rose-300' }}">
                                    {{ $backup->status === 'success' ? 'Sukses' : 'Gagal' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($backup->status === 'success')
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('developer.backups.download', $backup->id) }}"
                                           class="inline-flex h-8 items-center rounded-lg border border-slate-200 px-3 text-[11px] font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                            Unduh
                                        </a>
                                        <button type="button"
                                                @click="restoreHistoryOpen = true; restoreAction = @js(route('developer.backups.restore', $backup->id)); restoreName = @js($backup->file_name)"
                                                class="inline-flex h-8 items-center rounded-lg border border-amber-200 px-3 text-[11px] font-black text-amber-700 transition hover:bg-amber-50 dark:border-amber-900/60 dark:text-amber-300 dark:hover:bg-amber-500/10">
                                            Restore
                                        </button>
                                    </div>
                                @else
                                    <span class="block text-right text-xs font-bold text-slate-300 dark:text-slate-700">Tidak tersedia</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-14 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                                Belum ada riwayat backup database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800 lg:hidden">
            @forelse ($backups as $backup)
                <article class="space-y-3 px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-black text-slate-900 dark:text-white" title="{{ $backup->file_name }}">{{ $backup->file_name }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ $backup->created_at->format('d M Y H:i') }} - {{ $backup->user->name ?? 'Sistem' }}</p>
                        </div>
                        <span class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $backup->status === 'success' ? 'border-emerald-200 text-emerald-700 dark:border-emerald-900/60 dark:text-emerald-300' : 'border-rose-200 text-rose-700 dark:border-rose-900/60 dark:text-rose-300' }}">
                            {{ $backup->status === 'success' ? 'Sukses' : 'Gagal' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-black text-slate-700 dark:text-slate-200">{{ $formatSize($backup->file_size) }}</span>
                        @if($backup->status === 'success')
                            <div class="flex items-center gap-3 text-xs font-black">
                                <a href="{{ route('developer.backups.download', $backup->id) }}" class="text-slate-700 dark:text-slate-200">Unduh</a>
                                <button type="button"
                                        @click="restoreHistoryOpen = true; restoreAction = @js(route('developer.backups.restore', $backup->id)); restoreName = @js($backup->file_name)"
                                        class="text-amber-700 dark:text-amber-300">
                                    Restore
                                </button>
                            </div>
                        @else
                            <span class="text-xs font-bold text-slate-300 dark:text-slate-700">Tidak tersedia</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-4 py-12 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                    Belum ada riwayat backup database.
                </div>
            @endforelse
        </div>

        @include('partials.pagination_simple', [
            'paginator' => $backups,
            'label' => 'data',
        ])
    </section>

    <div x-show="restoreUploadOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/70 px-4 py-6">
        <div @click.outside="restoreUploadOpen = false" class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:max-w-2xl sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Restore Manual</h2>
                    <p class="mt-1 text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">Upload file .backup atau .dump, lalu ketik RESTORE untuk konfirmasi.</p>
                </div>
                <button type="button" @click="restoreUploadOpen = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('developer.backups.restore-upload') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <label class="block">
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">File Backup</span>
                    <input id="restore-upload-file" type="file" name="backup_file" required accept=".dump,.backup"
                           class="sr-only"
                           @change="restoreUploadFileName = $event.target.files.length ? $event.target.files[0].name : ''">
                    <span class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-lg border border-slate-300 bg-white px-3 py-2 transition hover:border-blue-400 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-blue-700 dark:hover:bg-blue-950/20">
                        <span class="min-w-0 flex-1 truncate text-xs font-black text-slate-800 dark:text-slate-100" x-text="restoreUploadFileName || 'Belum ada file dipilih'"></span>
                        <span class="inline-flex h-8 shrink-0 items-center justify-center rounded-md bg-slate-900 px-3 text-[11px] font-black text-white transition hover:bg-slate-800 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">
                            Pilih File
                        </span>
                    </span>
                    <span class="mt-1.5 block text-[11px] font-semibold text-slate-500 dark:text-slate-400">Format: .backup atau .dump</span>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">Konfirmasi</span>
                    <input type="text" name="restore_confirmation" required placeholder="Ketik RESTORE"
                           class="h-10 w-full rounded-lg border border-amber-300 bg-white px-3 text-xs font-black text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 dark:border-amber-800 dark:bg-slate-950 dark:text-slate-100">
                </label>
                <div class="grid grid-cols-2 gap-2 pt-1 sm:flex sm:justify-end">
                    <button type="button" @click="restoreUploadOpen = false" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 px-3 text-[11px] font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 sm:px-4 sm:text-xs">Batal</button>
                    <button type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-amber-600 px-3 text-[11px] font-black text-white shadow-sm shadow-amber-500/20 transition hover:brightness-95 sm:px-4 sm:text-xs"
                            style="background-color: #d97706; color: #ffffff;"
                            onclick="return confirm('PERHATIAN: Proses restore akan menimpa data yang ada saat ini di database.\n\nLanjutkan proses restore?')">
                        Upload & Restore
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="restoreHistoryOpen" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-slate-950/70 px-4 py-6">
        <div @click.outside="restoreHistoryOpen = false" class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Restore Backup</h2>
                    <p class="mt-1 truncate text-sm font-semibold text-slate-500 dark:text-slate-400" x-text="restoreName"></p>
                </div>
                <button type="button" @click="restoreHistoryOpen = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="restoreAction" method="POST" class="mt-5 space-y-4">
                @csrf
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold leading-relaxed text-amber-800 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-200">
                    Restore akan menimpa database saat ini. Ketik RESTORE untuk melanjutkan.
                </p>
                <input type="text" name="restore_confirmation" required placeholder="Ketik RESTORE"
                       class="h-10 w-full rounded-lg border border-amber-300 bg-white px-3 text-xs font-black text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 dark:border-amber-800 dark:bg-slate-950 dark:text-slate-100">
                <div class="grid grid-cols-2 gap-2 pt-1 sm:flex sm:justify-end">
                    <button type="button" @click="restoreHistoryOpen = false" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 px-3 text-[11px] font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 sm:px-4 sm:text-xs">Batal</button>
                    <button type="submit"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-amber-600 px-3 text-[11px] font-black text-white shadow-sm shadow-amber-500/20 transition hover:brightness-95 sm:px-4 sm:text-xs"
                            style="background-color: #d97706; color: #ffffff;"
                            onclick="return confirm('PERHATIAN: Proses restore akan menimpa data yang ada saat ini di database.\n\nApakah Anda yakin ingin me-restore dari backup ini?')">
                        Konfirmasi Restore
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
