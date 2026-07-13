@extends('layouts.app')

@section('title', 'Manajemen Cabang')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
<div class="space-y-8">
    <x-page-header 
        title="Manajemen Cabang" 
        subtitle="Kelola daftar cabang yang digunakan untuk memetakan admin, kasir, transaksi, sesi stok harian, dan laporan operasional." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Cabang">
        
        @if(!($migrationMissing ?? false))
            <a href="{{ route('owner.branches.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 shrink-0">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                Tambah Cabang
            </a>
        @endif
    </x-page-header>

    @if($migrationMissing ?? false)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="text-sm font-black text-amber-700 dark:text-amber-300">Migration cabang belum dijalankan.</p>
            <p class="mt-1 text-sm font-medium text-amber-700/80 dark:text-amber-200/80">
                Jalankan <span class="font-mono font-bold">php artisan migrate</span> agar halaman cabang dan pilihan cabang pengguna aktif.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            {{-- Total Cabang --}}
            <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Total Cabang</p>
                        <p class="mt-2 text-[28px] leading-none font-black text-slate-900 tabular-nums dark:text-white">{{ $summary['total'] ?? $branches->total() }}</p>
                    </div>
                    <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-blue-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </span>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                    <span>seluruh cabang terdaftar</span>
                </div>
            </div>

            {{-- Cabang Aktif --}}
            <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Cabang Aktif</p>
                        <p class="mt-2 text-[28px] leading-none font-black text-emerald-600 tabular-nums dark:text-emerald-400">{{ $summary['active'] ?? 0 }}</p>
                    </div>
                    <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-emerald-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </span>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                    <span>cabang beroperasi normal</span>
                </div>
            </div>

            {{-- Cabang Nonaktif --}}
            <div class="relative overflow-hidden border border-slate-200 rounded-2xl bg-white px-5 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.05)] hover:border-slate-300 transition-all dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[11px] font-bold tracking-widest text-slate-500 uppercase dark:text-slate-400">Cabang Nonaktif</p>
                        <p class="mt-2 text-[28px] leading-none font-black text-slate-600 tabular-nums dark:text-slate-400">{{ $summary['inactive'] ?? 0 }}</p>
                    </div>
                    <span class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500 shadow-[inset_0_0_0_1px_rgba(226,232,240,1)] dark:bg-slate-800 dark:shadow-[inset_0_0_0_1px_rgba(51,65,85,1)]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </span>
                </div>
                <div class="mt-4 flex items-center justify-between border-t border-dashed border-slate-200 pt-3 text-[11px] font-semibold text-slate-500 dark:border-slate-700/60 dark:text-slate-400">
                    <span>cabang sedang tidak beroperasi</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <div>
                    <p class="text-sm font-black text-slate-900 dark:text-white">Daftar Cabang</p>
                    <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">Cabang aktif akan muncul pada form tambah/edit pengguna.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black uppercase tracking-widest text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                    {{ $branches->total() }} data
                </span>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-400 dark:bg-slate-800/30">
                        <tr>
                            <th class="px-6 py-4">Cabang</th>
                            <th class="px-6 py-4">Kode</th>
                            <th class="px-6 py-4">Pengguna</th>
                            <th class="px-6 py-4">Aktivitas</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($branches as $branch)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-5">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $branch->name }}</p>
                                    <p class="mt-1 max-w-md truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ $branch->address ?: 'Alamat belum diisi' }}</p>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="rounded-lg bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $branch->code }}</span>
                                </td>
                                <td class="px-6 py-5 text-sm font-bold text-slate-700 dark:text-slate-300">{{ $branch->operational_users_count }} user</td>
                                <td class="px-6 py-5 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    {{ $branch->transactions_count }} transaksi, {{ $branch->daily_stock_sessions_count }} sesi
                                </td>
                                <td class="px-6 py-5 text-center">
                                    @if($branch->is_active)
                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-black uppercase tracking-widest text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-black uppercase tracking-widest text-slate-500 dark:bg-slate-800 dark:text-slate-400">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('owner.branches.edit', $branch) }}"
                                           class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 dark:border-slate-700 dark:text-slate-400 dark:hover:border-blue-500/30 dark:hover:bg-blue-500/10 dark:hover:text-blue-300"
                                           title="Edit cabang">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </a>
                                        <form action="{{ route('owner.branches.toggle', $branch) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex h-9 items-center justify-center rounded-xl border border-slate-200 px-3 text-xs font-black text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                                {{ $branch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-sm font-semibold text-slate-400">
                                    Belum ada cabang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
                @forelse($branches as $branch)
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-black text-slate-900 dark:text-white">{{ $branch->name }}</p>
                                <p class="mt-1 text-xs font-mono font-bold text-slate-500">{{ $branch->code }}</p>
                            </div>
                            @if($branch->is_active)
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Aktif</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:bg-slate-800 dark:text-slate-400">Nonaktif</span>
                            @endif
                        </div>
                        <p class="mt-3 text-xs font-medium leading-relaxed text-slate-500 dark:text-slate-400">{{ $branch->address ?: 'Alamat belum diisi' }}</p>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-xs font-bold text-slate-600 dark:text-slate-300">
                            <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/60">{{ $branch->operational_users_count }} user</div>
                            <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-800/60">{{ $branch->transactions_count }} transaksi</div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('owner.branches.edit', $branch) }}"
                               class="flex-1 rounded-xl bg-blue-600 px-4 py-2.5 text-center text-xs font-black text-white">Edit</a>
                            <form action="{{ route('owner.branches.toggle', $branch) }}" method="POST" class="flex-1">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-full rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    {{ $branch->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm font-semibold text-slate-400">Belum ada cabang.</div>
                @endforelse
            </div>
        </div>

        @if($branches->hasPages())
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-5 py-4 text-sm font-semibold text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400">
                <span>Halaman {{ $branches->currentPage() }} dari {{ $branches->lastPage() }} | Total {{ $branches->total() }} data</span>
                <div class="flex items-center gap-4">
                    @if($branches->onFirstPage())
                        <span class="text-slate-300">&lt; Prev</span>
                    @else
                        <a href="{{ $branches->previousPageUrl() }}" class="text-blue-600 hover:text-blue-700">&lt; Prev</a>
                    @endif

                    @if($branches->hasMorePages())
                        <a href="{{ $branches->nextPageUrl() }}" class="text-blue-600 hover:text-blue-700">Next &gt;</a>
                    @else
                        <span class="text-slate-300">Next &gt;</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
