@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

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

    $ownerCount = (int) ($roleCounts->get('owner') ?? 0);
    $adminCount = (int) ($roleCounts->get('admin') ?? 0);
    $cashierCount = (int) ($roleCounts->get('kasir') ?? 0);
    $developerCount = (int) ($roleCounts->get('developer') ?? 0);
    $backupSuccessRate = $totalBackups > 0 ? round(($successfulBackups / $totalBackups) * 100) : 0;
    $maxRoleCount = max($ownerCount, $adminCount, $cashierCount, $developerCount, 1);
    $debugActive = $debugMode === 'Aktif';
    $productionEnv = strtolower((string) $appEnv) === 'production';
    $systemNeedsAttention = $debugActive || ! $productionEnv;
    $systemStatusLabel = $systemNeedsAttention ? 'Perlu Perhatian' : 'Stabil';
    $systemStatusText = $systemNeedsAttention
        ? 'Cek environment dan debug sebelum aplikasi dipakai produksi.'
        : 'Konfigurasi inti sistem berada pada kondisi aman.';
    $systemDotClass = $systemNeedsAttention ? 'bg-amber-500' : 'bg-emerald-500';
    $systemBadgeClass = $systemNeedsAttention
        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300'
        : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300';
@endphp

@push('styles')
<style>
    .developer-dashboard-status,
    .developer-dashboard-kpis,
    .developer-dashboard-main,
    .developer-dashboard-bottom {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
    }

    .developer-dashboard-role-row,
    .developer-dashboard-backup-row {
        display: grid;
        align-items: center;
        gap: 12px;
    }

    .developer-dashboard-role-row {
        grid-template-columns: minmax(120px, .82fr) minmax(0, 1fr) 54px;
    }

    .developer-dashboard-backup-row {
        grid-template-columns: minmax(0, 1fr) 86px auto;
        min-height: 44px;
    }

    .developer-dashboard-technical-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .developer-dashboard-note {
        border: 1px solid rgb(226 232 240);
        background: rgb(248 250 252);
    }

    .dark .developer-dashboard-note {
        border-color: rgb(51 65 85);
        background: rgb(15 23 42);
    }

    .developer-dashboard-note-icon {
        background: rgb(255 255 255);
        color: rgb(100 116 139);
        box-shadow: inset 0 0 0 1px rgb(226 232 240);
    }

    .dark .developer-dashboard-note-icon {
        background: rgb(30 41 59);
        color: rgb(148 163 184);
        box-shadow: inset 0 0 0 1px rgb(51 65 85);
    }

    @media (max-width: 639px) {
        .developer-dashboard-role-row {
            grid-template-columns: minmax(0, 1fr) 48px;
        }

        .developer-dashboard-role-row > :nth-child(2) {
            display: none;
        }

        .developer-dashboard-backup-row {
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .developer-dashboard-backup-row > :nth-child(2) {
            display: none;
        }
    }

    @media (min-width: 640px) {
        .developer-dashboard-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .developer-dashboard-status {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: stretch;
        }

        .developer-dashboard-kpis {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .developer-dashboard-main {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: stretch;
        }

        .developer-dashboard-bottom {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: start;
        }

        .developer-dashboard-technical-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .developer-dashboard-main > *,
        .developer-dashboard-bottom > * {
            min-width: 0;
        }
    }
</style>
@endpush

<div class="developer-dashboard space-y-3">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <nav class="flex items-center gap-2 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-400">
                <span class="text-blue-600 dark:text-blue-400">Super Admin</span>
                <span>/</span>
                <span>Ringkasan Sistem</span>
            </nav>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-white">Dashboard Super Admin</h1>
            <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                Pantau akun, backup, dan status teknis sistem dari satu tempat.
            </p>
        </div>

        <div class="inline-flex h-9 w-fit items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-[11px] font-bold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
            <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            {{ now()->translatedFormat('d F Y') }}
        </div>
    </header>

    <section class="developer-dashboard-status gap-2.5">
        <article class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Status Sistem</p>
                    <div class="mt-1.5 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full {{ $systemDotClass }}"></span>
                        <h2 class="truncate text-base font-black text-slate-900 dark:text-white">{{ $systemStatusLabel }}</h2>
                    </div>
                    <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $systemStatusText }}</p>
                </div>

                <span class="inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-[9px] font-black uppercase tracking-wider {{ $systemBadgeClass }}">
                    {{ strtoupper($appEnv) }}
                </span>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full {{ $productionEnv ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    ENV {{ strtoupper($appEnv) }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full {{ $debugActive ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                    DEBUG {{ strtoupper($debugMode) }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    {{ $latestBackups->count() }} backup terbaru
                </span>
            </div>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Aksi Sistem</p>
                    <h2 class="mt-1 text-base font-black text-slate-900 dark:text-white">Kontrol Cepat</h2>
                    <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Aksi teknis yang paling sering dipakai superadmin.</p>
                </div>
                <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    Tools
                </span>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <form action="{{ route('developer.clear-cache') }}" method="POST" class="flex">
                    @csrf
                    <button type="submit" class="flex min-h-[74px] w-full items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-left text-amber-700 transition hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/15">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/80 ring-1 ring-amber-200 dark:bg-slate-950/30 dark:ring-amber-900/60">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-black">Bersihkan Cache</span>
                            <span class="mt-0.5 block truncate text-[10px] font-semibold opacity-80">route, view, config</span>
                        </span>
                    </button>
                </form>

                <a href="{{ route('developer.backups.index') }}" class="flex min-h-[74px] items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2.5 text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/15">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/80 ring-1 ring-blue-200 dark:bg-slate-950/30 dark:ring-blue-900/60">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-black">Kelola Backup</span>
                        <span class="mt-0.5 block truncate text-[10px] font-semibold opacity-80">backup & restore</span>
                    </span>
                </a>
            </div>
        </article>
    </section>

    <section class="developer-dashboard-kpis gap-2.5">
        @foreach([
            ['label' => 'Total User', 'value' => number_format($totalUsers), 'note' => 'akun aktif sistem', 'tone' => 'slate', 'icon' => 'users'],
            ['label' => 'Owner', 'value' => number_format($ownerCount), 'note' => 'akun pemilik', 'tone' => 'blue', 'icon' => 'owner'],
            ['label' => 'Backup', 'value' => number_format($totalBackups), 'note' => $backupSuccessRate . '% sukses', 'tone' => 'emerald', 'icon' => 'backup'],
            ['label' => 'Database', 'value' => $databaseSize, 'note' => 'ukuran saat ini', 'tone' => 'amber', 'icon' => 'database'],
        ] as $metric)
            @php
                $toneClass = match ($metric['tone']) {
                    'blue' => 'border-blue-200 bg-blue-50 text-blue-600 dark:border-blue-900/60 dark:bg-blue-500/10 dark:text-blue-300',
                    'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300',
                    'amber' => 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300',
                    default => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                };
            @endphp
            <article class="flex min-h-[70px] items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border {{ $toneClass }}">
                    @if($metric['icon'] === 'users')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m8-4.13a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    @elseif($metric['icon'] === 'owner')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    @elseif($metric['icon'] === 'backup')
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                    @else
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                    @endif
                </span>
                <div class="min-w-0">
                    <p class="truncate text-[9px] font-bold uppercase tracking-wider text-slate-400">{{ $metric['label'] }}</p>
                    <p class="mt-1 truncate text-lg font-black leading-none text-slate-900 dark:text-white">{{ $metric['value'] }}</p>
                    <p class="mt-1 truncate text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ $metric['note'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <div class="developer-dashboard-main gap-3">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Komposisi Akun</h2>
                <p class="mt-0.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">Distribusi role aktif yang terdaftar di sistem.</p>
            </div>

            <div class="space-y-2 p-4">
                @foreach([
                    ['label' => 'Owner', 'value' => $ownerCount, 'tone' => 'bg-blue-600 dark:bg-blue-500'],
                    ['label' => 'Admin', 'value' => $adminCount, 'tone' => 'bg-emerald-600 dark:bg-emerald-500'],
                    ['label' => 'Kasir', 'value' => $cashierCount, 'tone' => 'bg-amber-500 dark:bg-amber-400'],
                    ['label' => 'Super Admin', 'value' => $developerCount, 'tone' => 'bg-slate-500 dark:bg-slate-400'],
                ] as $role)
                    @php $width = max(6, round(($role['value'] / $maxRoleCount) * 100)); @endphp
                    <div class="developer-dashboard-role-row rounded-lg border border-slate-100 px-3 py-2.5 dark:border-slate-800">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-black text-slate-900 dark:text-white">{{ $role['label'] }}</p>
                            <p class="mt-0.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400">Role sistem</p>
                        </div>
                        <div class="h-2 overflow-hidden rounded-sm bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-sm {{ $role['tone'] }}" style="width: {{ $width }}%"></div>
                        </div>
                        <p class="text-right text-sm font-black text-slate-900 dark:text-white">{{ number_format($role['value']) }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="flex flex-col rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Backup Database</p>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full {{ $lastBackup && $lastBackup->status === 'success' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <h2 class="text-base font-black {{ $lastBackup && $lastBackup->status === 'success' ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">
                            {{ $lastBackup ? 'Backup Tersedia' : 'Belum Ada Backup' }}
                        </h2>
                    </div>
                    <p class="mt-1 text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ number_format($successfulBackups) }} sukses, {{ number_format($failedBackups) }} gagal.</p>
                </div>
                <span class="rounded-md border px-2 py-1 text-[9px] font-bold uppercase tracking-wider {{ $lastBackup && $lastBackup->status === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-500/10 dark:text-amber-300' }}">
                    {{ $lastBackup && $lastBackup->status === 'success' ? 'Normal' : 'Periksa' }}
                </span>
            </div>

            <div class="mt-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-widest text-slate-400">Tingkat Sukses</p>
                        <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">{{ $backupSuccessRate }}%</p>
                    </div>
                    <a href="{{ route('developer.backups.index') }}" class="rounded-md border border-slate-200 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-blue-600 transition hover:bg-blue-50 dark:border-slate-700 dark:text-blue-300 dark:hover:bg-blue-950/20">Kelola</a>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-sm bg-slate-100 dark:bg-slate-800">
                    <div class="h-full rounded-sm bg-emerald-600 dark:bg-emerald-500" style="width: {{ $backupSuccessRate }}%"></div>
                </div>
                <p class="mt-2 truncate text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                    @if($lastBackup)
                        Terakhir {{ $lastBackup->created_at->format('d M Y H:i') }} &middot; {{ $formatSize($lastBackup->file_size) }}
                    @else
                        Jalankan backup pertama dari menu Manajemen Backup.
                    @endif
                </p>
            </div>

            <nav class="mt-3 grid flex-1 grid-cols-2 gap-2" aria-label="Aksi cepat superadmin">
                @foreach([
                    [
                        'route' => route('developer.owners.index'),
                        'label' => 'Owner',
                        'tone' => 'bg-blue-50 text-blue-600 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-300 dark:ring-blue-900/60',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 20.25a7.5 7.5 0 0115 0"></path>',
                    ],
                    [
                        'route' => route('developer.backups.index'),
                        'label' => 'Backup',
                        'tone' => 'bg-emerald-50 text-emerald-600 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-900/60',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 7.5c0-1.657 3.358-3 7.5-3s7.5 1.343 7.5 3-3.358 3-7.5 3-7.5-1.343-7.5-3z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 7.5v9c0 1.657 3.358 3 7.5 3s7.5-1.343 7.5-3v-9"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12c0 1.657 3.358 3 7.5 3s7.5-1.343 7.5-3"></path>',
                    ],
                    [
                        'route' => route('admin.panel'),
                        'label' => 'Admin',
                        'tone' => 'bg-amber-50 text-amber-600 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-900/60',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3.75l7.5 3.75v4.75c0 4.55-3.075 7.412-7.5 8.5-4.425-1.088-7.5-3.95-7.5-8.5V7.5L12 3.75z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 12l1.5 1.5 3.25-3.25"></path>',
                    ],
                    [
                        'route' => route('owner.panel'),
                        'label' => 'Owner Panel',
                        'tone' => 'bg-violet-50 text-violet-600 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-900/60',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.75 20.25h14.5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 20.25V8.75L12 4.5l5.5 4.25v11.5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20.25v-5.5h4v5.5"></path>',
                    ],
                ] as $shortcut)
                    <a href="{{ $shortcut['route'] }}" class="group flex min-h-[54px] items-center gap-3 rounded-lg border border-slate-200 px-3 text-[10px] font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-800 dark:hover:bg-blue-950/20">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 transition group-hover:scale-[1.03] {{ $shortcut['tone'] }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $shortcut['icon'] !!}</svg>
                        </span>
                        <span class="truncate">{{ $shortcut['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </section>
    </div>

    <div class="developer-dashboard-bottom gap-3">
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Riwayat Backup Terbaru</h2>
                    <p class="mt-0.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ number_format($latestBackups->count()) }} backup terakhir</p>
                </div>
                <a href="{{ route('developer.backups.index') }}" class="text-[9px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Semua</a>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($latestBackups as $backup)
                    <div class="developer-dashboard-backup-row px-4 py-2.5">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-black text-slate-900 dark:text-white" title="{{ $backup->file_name }}">{{ $backup->file_name }}</p>
                            <p class="mt-0.5 text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                                {{ $backup->created_at->format('d M Y H:i') }} &middot; {{ $backup->user->name ?? 'Sistem' }}
                            </p>
                        </div>
                        <p class="text-right text-[10px] font-black text-slate-600 dark:text-slate-300">{{ $formatSize($backup->file_size) }}</p>
                        <span class="inline-flex rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $backup->status === 'success' ? 'border-emerald-200 text-emerald-700 dark:border-emerald-900/60 dark:text-emerald-300' : 'border-rose-200 text-rose-700 dark:border-rose-900/60 dark:text-rose-300' }}">
                            {{ $backup->status === 'success' ? 'Sukses' : 'Gagal' }}
                        </span>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                        Belum ada riwayat backup.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Informasi Teknis</h2>
                <p class="mt-0.5 text-[10px] font-medium text-slate-500 dark:text-slate-400">Versi runtime dan konfigurasi aplikasi.</p>
            </div>

            <div class="developer-dashboard-technical-grid divide-x divide-y divide-slate-100 dark:divide-slate-800">
                @foreach([
                    ['label' => 'PHP', 'value' => $phpVersion],
                    ['label' => 'Laravel', 'value' => $laravelVersion],
                    ['label' => 'Environment', 'value' => strtoupper($appEnv)],
                    ['label' => 'Debug', 'value' => $debugMode],
                ] as $system)
                    <div class="px-4 py-3.5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ $system['label'] }}</p>
                        <p class="mt-1 truncate text-sm font-black text-slate-900 dark:text-white">{{ $system['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-slate-100 p-3 dark:border-slate-800">
                <div class="developer-dashboard-note flex flex-col gap-3 rounded-lg px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="developer-dashboard-note-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-lg">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Catatan Operasional</p>
                            <p class="mt-0.5 text-[11px] font-semibold leading-relaxed text-slate-500 dark:text-slate-400">
                                Pastikan debug nonaktif dan environment produksi aktif sebelum sistem digunakan client.
                            </p>
                        </div>
                    </div>

                    <span class="inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-[9px] font-black uppercase tracking-wider {{ $systemBadgeClass }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $systemDotClass }}"></span>
                        {{ $systemStatusLabel }}
                    </span>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
