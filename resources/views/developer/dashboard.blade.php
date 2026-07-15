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
    
    $systemStatusLabel = $systemNeedsAttention ? 'Sistem Perlu Perhatian' : 'Sistem Optimal';
    $systemStatusText = $systemNeedsAttention
        ? 'Lingkungan tidak aman untuk produksi (Debug Aktif / Env bukan Production).'
        : 'Lingkungan aman dan siap untuk operasi berskala penuh.';
@endphp

<div class="w-full space-y-8 pb-16">

    <x-page-header 
        title="Dashboard Super Admin" 
        subtitle="Ringkasan teknis sistem, manajemen akun, dan riwayat pencadangan data." 
        breadcrumb-parent="Super Admin" 
        breadcrumb-child="Dashboard">
        
        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 21h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            {{ now()->translatedFormat('d F Y') }}
        </div>
    </x-page-header>

    <!-- Top Status Banner -->
    <div class="flex flex-col gap-4 rounded-3xl border border-slate-200/60 bg-white/60 p-6 shadow-sm backdrop-blur-xl md:flex-row md:items-center md:justify-between dark:border-slate-800 dark:bg-slate-900/50">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl shadow-inner {{ $systemNeedsAttention ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400' }}">
                @if($systemNeedsAttention)
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                @else
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                @endif
            </div>
            <div>
                <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">{{ $systemStatusLabel }}</h2>
                <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $systemStatusText }}</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold tracking-wide text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                <span class="h-2 w-2 rounded-full {{ $productionEnv ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                ENV: {{ strtoupper($appEnv) }}
            </span>
            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold tracking-wide text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                <span class="h-2 w-2 rounded-full {{ $debugActive ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                DEBUG: {{ strtoupper($debugMode) }}
            </span>
        </div>
    </div>

    <!-- KPIs Bento Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['label' => 'Total Akun', 'value' => number_format($totalUsers), 'note' => 'Pengguna aktif di sistem', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m8-4.13a4 4 0 11-8 0 4 4 0 018 0z"></path>', 'bg' => 'bg-blue-50 dark:bg-blue-500/10', 'text' => 'text-blue-600 dark:text-blue-400', 'border' => 'border-blue-200 dark:border-blue-900/50'],
            ['label' => 'Total Owner', 'value' => number_format($ownerCount), 'note' => 'Pemilik bisnis', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>', 'bg' => 'bg-indigo-50 dark:bg-indigo-500/10', 'text' => 'text-indigo-600 dark:text-indigo-400', 'border' => 'border-indigo-200 dark:border-indigo-900/50'],
            ['label' => 'Riwayat Backup', 'value' => number_format($totalBackups), 'note' => $backupSuccessRate . '% rasio sukses', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>', 'bg' => 'bg-emerald-50 dark:bg-emerald-500/10', 'text' => 'text-emerald-600 dark:text-emerald-400', 'border' => 'border-emerald-200 dark:border-emerald-900/50'],
            ['label' => 'Ruang Database', 'value' => $databaseSize, 'note' => 'Kapasitas terpakai saat ini', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>', 'bg' => 'bg-amber-50 dark:bg-amber-500/10', 'text' => 'text-amber-600 dark:text-amber-400', 'border' => 'border-amber-200 dark:border-amber-900/50'],
        ] as $metric)
            <div class="group relative overflow-hidden rounded-[1.5rem] border border-slate-200/60 bg-white/60 p-6 shadow-sm backdrop-blur-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900/50">
                <div class="absolute inset-x-0 -bottom-2 h-1 {{ $metric['bg'] }} opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $metric['label'] }}</p>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border {{ $metric['border'] }} {{ $metric['bg'] }} {{ $metric['text'] }} shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                         <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $metric['icon'] !!}</svg>
                    </div>
                </div>
                <div class="mt-6">
                    <h3 class="text-4xl font-black tracking-tight text-slate-900 dark:text-white">{{ $metric['value'] }}</h3>
                    <p class="mt-1.5 text-sm font-medium text-slate-500 dark:text-slate-400">{{ $metric['note'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Layout Utama Bento -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        
        <!-- Kolom Kiri Utama -->
        <div class="space-y-6 lg:col-span-2">
             
            <!-- Aksi Cepat (Bento Cards) -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <form action="{{ route('developer.clear-cache') }}" method="POST">
                    @csrf
                    <button type="submit" class="group relative flex w-full flex-col items-start justify-between gap-4 overflow-hidden rounded-[1.5rem] border border-slate-200/60 bg-white/60 p-6 text-left shadow-sm backdrop-blur-xl transition-all duration-300 hover:border-slate-300 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900/50 dark:hover:border-slate-700">
                        <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-gradient-to-br from-amber-400/20 to-rose-400/20 blur-2xl transition-all duration-500 group-hover:scale-150"></div>
                        
                        <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-md ring-1 ring-slate-100 dark:bg-slate-800 dark:ring-slate-700">
                            <svg class="h-7 w-7 text-amber-500 transition-transform duration-300 group-hover:-rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </div>
                        <div class="relative w-full">
                            <div class="flex items-center justify-between">
                                <p class="text-base font-bold text-slate-900 dark:text-white">Bersihkan Cache</p>
                                <svg class="h-5 w-5 text-slate-400 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </div>
                            <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Optimasi route, view, dan konfigurasi cache sistem.</p>
                        </div>
                    </button>
                </form>

                <a href="{{ route('developer.backups.index') }}" class="group relative flex w-full flex-col items-start justify-between gap-4 overflow-hidden rounded-[1.5rem] border border-slate-200/60 bg-white/60 p-6 text-left shadow-sm backdrop-blur-xl transition-all duration-300 hover:border-slate-300 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900/50 dark:hover:border-slate-700">
                    <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full bg-gradient-to-br from-blue-400/20 to-cyan-400/20 blur-2xl transition-all duration-500 group-hover:scale-150"></div>
                    
                    <div class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-md ring-1 ring-slate-100 dark:bg-slate-800 dark:ring-slate-700">
                        <svg class="h-7 w-7 text-blue-500 transition-transform duration-300 group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                    </div>
                    <div class="relative w-full">
                        <div class="flex items-center justify-between">
                            <p class="text-base font-bold text-slate-900 dark:text-white">Kelola Backup</p>
                            <svg class="h-5 w-5 text-slate-400 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                        </div>
                        <p class="mt-1 text-sm font-medium text-slate-500 dark:text-slate-400">Akses modul arsip dan pemulihan database.</p>
                    </div>
                </a>
            </div>

            <!-- Komposisi Role (Modern Chart Style) -->
            <div class="overflow-hidden rounded-[1.5rem] border border-slate-200/60 bg-white/60 shadow-sm backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/50">
                <div class="flex items-center justify-between border-b border-slate-100 px-8 py-6 dark:border-slate-800/80">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Komposisi Hak Akses</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Distribusi *role* pengguna aktif berdasarkan tingkat otorisasi.</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    </div>
                </div>
                <div class="p-8 space-y-6">
                    @foreach([
                        ['label' => 'Owner', 'value' => $ownerCount, 'tone' => 'from-blue-500 to-indigo-500'],
                        ['label' => 'Admin', 'value' => $adminCount, 'tone' => 'from-emerald-400 to-teal-500'],
                        ['label' => 'Kasir', 'value' => $cashierCount, 'tone' => 'from-amber-400 to-orange-500'],
                        ['label' => 'Super Admin', 'value' => $developerCount, 'tone' => 'from-slate-700 to-slate-900 dark:from-slate-600 dark:to-slate-800'],
                    ] as $role)
                        @php $width = max(2, round(($role['value'] / $maxRoleCount) * 100)); @endphp
                        <div class="group">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $role['label'] }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-sm font-black text-slate-900 dark:bg-slate-800 dark:text-white">{{ number_format($role['value']) }}</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100 shadow-inner dark:bg-slate-800/50">
                                <div class="h-full rounded-full bg-gradient-to-r {{ $role['tone'] }} transition-all duration-700 ease-out group-hover:opacity-80" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Tautan Navigasi (Pill Layout) -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach([
                    ['route' => route('developer.owners.index'), 'label' => 'Data Owner', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m8-4.13a4 4 0 11-8 0 4 4 0 018 0z"></path>'],
                    ['route' => route('developer.backups.index'), 'label' => 'Database', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>'],
                    ['route' => route('admin.panel'), 'label' => 'Admin Panel', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>'],
                    ['route' => route('owner.panel'), 'label' => 'Owner Panel', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>'],
                ] as $link)
                    <a href="{{ $link['route'] }}" class="group flex flex-col items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white/50 py-5 transition-all hover:bg-slate-50 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/30 dark:hover:bg-slate-800/80">
                        <span class="text-slate-400 transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $link['icon'] !!}</svg>
                        </span>
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Kolom Kanan (Informasi Teknis) -->
        <div class="space-y-6">
            
            <!-- Tech Stack Card -->
            <div class="overflow-hidden rounded-[1.5rem] border border-slate-200/60 bg-white/60 shadow-sm backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/50">
                <div class="border-b border-slate-100 bg-slate-50/50 px-6 py-5 dark:border-slate-800/80 dark:bg-slate-900/20">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Informasi Lingkungan</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Spesifikasi runtime web & framework.</p>
                </div>
                <div class="divide-y divide-slate-100 p-2 dark:divide-slate-800/80">
                    @foreach([
                        ['label' => 'Versi PHP', 'value' => $phpVersion],
                        ['label' => 'Versi Laravel', 'value' => $laravelVersion],
                        ['label' => 'Mode Lingkungan', 'value' => strtoupper($appEnv)],
                        ['label' => 'Status Debug', 'value' => $debugMode],
                    ] as $info)
                        <div class="flex items-center justify-between rounded-xl px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $info['label'] }}</span>
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-900 dark:bg-slate-800 dark:text-white">{{ $info['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Backups List -->
            <div class="overflow-hidden rounded-[1.5rem] border border-slate-200/60 bg-white/60 shadow-sm backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/50">
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-5 dark:border-slate-800/80 dark:bg-slate-900/20">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Arsip Pencadangan</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $latestBackups->count() }} proses pencadangan terakhir</p>
                    </div>
                    <a href="{{ route('developer.backups.index') }}" class="group flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-blue-100 hover:text-blue-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-blue-500/20 dark:hover:text-blue-400">
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                    @forelse($latestBackups as $backup)
                        <div class="flex flex-col gap-3 px-6 py-5 hover:bg-slate-50 dark:hover:bg-slate-800/30">
                            <div class="flex items-start justify-between gap-4">
                                <p class="line-clamp-1 text-sm font-bold text-slate-900 dark:text-white" title="{{ $backup->file_name }}">
                                    {{ $backup->file_name }}
                                </p>
                                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $backup->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400' }}">
                                    {{ $backup->status === 'success' ? 'Sukses' : 'Gagal' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] font-semibold text-slate-500 dark:text-slate-400">
                                <span class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $backup->created_at->format('d M Y H:i') }}
                                </span>
                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $formatSize($backup->file_size) }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800/50">
                                <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            </div>
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Belum Ada Cadangan</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Riwayat backup database akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
