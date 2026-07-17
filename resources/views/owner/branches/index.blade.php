@extends('layouts.app')

@section('title', 'Manajemen Cabang')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
<div class="space-y-8" x-data="{
        toggleUrl: '',
        destroyUrl: '',
        branchName: '',
        openBranchDeactivate(url, name) {
            this.toggleUrl = url;
            this.branchName = name;
            $dispatch('open-modal', 'branch-deactivate-modal');
        },
        openBranchActivate(url, name) {
            this.toggleUrl = url;
            this.branchName = name;
            $dispatch('open-modal', 'branch-activate-modal');
        },
        openBranchDestroy(url, name) {
            this.destroyUrl = url;
            this.branchName = name;
            $dispatch('open-modal', 'branch-destroy-modal');
        }
    }">
    <x-page-header 
        title="Manajemen Cabang" 
        subtitle="Kelola daftar cabang yang digunakan untuk memetakan admin, kasir, transaksi, sesi stok harian, dan laporan operasional." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Cabang">
    </x-page-header>

    @if($migrationMissing ?? false)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="text-sm font-black text-amber-700 dark:text-amber-300">Migration cabang belum dijalankan.</p>
            <p class="mt-1 text-sm font-medium text-amber-700/80 dark:text-amber-200/80">
                Jalankan <span class="font-mono font-bold">php artisan migrate</span> agar halaman cabang dan pilihan cabang pengguna aktif.
            </p>
        </div>
    @else
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <nav class="grid grid-cols-3 gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 dark:border-slate-800 dark:bg-slate-900 sm:inline-grid sm:min-w-[420px]" aria-label="Filter status cabang">
                @foreach([
                    'active' => ['label' => 'Aktif', 'count' => $activeCount ?? 0],
                    'inactive' => ['label' => 'Nonaktif', 'count' => $inactiveCount ?? 0],
                    'all' => ['label' => 'Semua', 'count' => $totalCount ?? 0],
                ] as $filter => $item)
                    <a href="{{ route('owner.branches.index', ['status' => $filter]) }}"
                       @class([
                           'inline-flex h-9 items-center justify-center gap-2 rounded-lg px-3 text-xs font-bold transition-colors',
                           'bg-white text-blue-600 shadow-sm dark:bg-slate-800 dark:text-blue-400' => ($status ?? 'all') === $filter,
                           'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' => ($status ?? 'all') !== $filter,
                       ])>
                        {{ $item['label'] }}
                        <span @class([
                            'rounded-md px-1.5 py-0.5 text-[10px] tabular-nums',
                            'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300' => ($status ?? 'all') === $filter,
                            'bg-slate-200/70 text-slate-500 dark:bg-slate-700 dark:text-slate-300' => ($status ?? 'all') !== $filter,
                        ])>{{ $item['count'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="flex w-full flex-col sm:flex-row sm:items-center justify-end gap-3 sm:w-auto">
                <div class="inline-flex h-10 items-center gap-2 px-3.5 bg-slate-100 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 rounded-xl shadow-sm">
                    <span class="text-[11px] sm:text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wide flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        Total: <span class="text-slate-900 dark:text-white normal-case tracking-normal ml-0.5">{{ $branches->total() ?? $branches->count() }} Cabang</span>
                    </span>
                </div>
                
                <a href="{{ route('owner.branches.create') }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-[13px] font-bold text-white shadow-sm transition-all hover:bg-blue-700 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Cabang
                </a>
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
                                        @if($branch->is_active)
                                            <button type="button"
                                                    @click="openBranchDeactivate('{{ route('owner.branches.toggle', $branch) }}', '{{ addslashes($branch->name) }}')"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 dark:border-slate-700 dark:text-slate-400 dark:hover:border-rose-500/30 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                                                    title="Nonaktifkan Cabang">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                            </button>
                                        @else
                                            <button type="button"
                                                    @click="openBranchActivate('{{ route('owner.branches.toggle', $branch) }}', '{{ addslashes($branch->name) }}')"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-600 dark:border-slate-700 dark:text-slate-400 dark:hover:border-emerald-500/30 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-300"
                                                    title="Aktifkan Cabang">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button>
                                        @endif
                                        <button type="button"
                                                @click="openBranchDestroy('{{ route('owner.branches.destroy', $branch) }}', '{{ addslashes($branch->name) }}')"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-500/10 dark:border-rose-900/60 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:bg-rose-500/15"
                                                title="Hapus cabang">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 7h12m-9 0V5.75A1.75 1.75 0 0110.75 4h2.5A1.75 1.75 0 0115 5.75V7m2 0-.72 11.02A2 2 0 0114.28 20H9.72a2 2 0 01-2-1.98L7 7m3 4v5m4-5v5"></path></svg>
                                        </button>
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
                            @if($branch->is_active)
                                <button type="button" 
                                        @click="openBranchDeactivate('{{ route('owner.branches.toggle', $branch) }}', '{{ addslashes($branch->name) }}')"
                                        class="flex-1 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    Nonaktifkan
                                </button>
                            @else
                                <button type="button" 
                                        @click="openBranchActivate('{{ route('owner.branches.toggle', $branch) }}', '{{ addslashes($branch->name) }}')"
                                        class="flex-1 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                    Aktifkan
                                </button>
                            @endif
                            <button type="button"
                                    @click="openBranchDestroy('{{ route('owner.branches.destroy', $branch) }}', '{{ addslashes($branch->name) }}')"
                                    class="inline-flex w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 7h12m-9 0V5.75A1.75 1.75 0 0110.75 4h2.5A1.75 1.75 0 0115 5.75V7m2 0-.72 11.02A2 2 0 0114.28 20H9.72a2 2 0 01-2-1.98L7 7m3 4v5m4-5v5"></path></svg>
                            </button>
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

    <x-modal id="branch-deactivate-modal" maxWidth="md" type="danger">
        <x-slot name="title">Nonaktifkan Cabang</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </x-slot>
        <x-slot name="description">
            Anda yakin ingin menonaktifkan cabang <span class="font-bold text-slate-900 dark:text-white" x-text="branchName"></span>? Cabang ini tidak akan muncul pada pilihan form. Cabang dapat diaktifkan kembali melalui filter Nonaktif.
        </x-slot>

        <form x-bind:action="toggleUrl" method="POST" x-data="{ input: '' }" @open-modal.window="if($event.detail === 'branch-deactivate-modal') input = ''">
            @csrf
            @method('PATCH')
            <div class="pt-2">
                <label class="sr-only" for="branch_deactivate_confirmation">Konfirmasi</label>
                <input type="text" name="deactivate_confirmation" id="branch_deactivate_confirmation" 
                       x-model="input"
                       class="block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-rose-500 dark:focus:ring-rose-500" 
                       autocomplete="off"
                       x-bind:placeholder="'Ketik \'' + branchName + '\' untuk konfirmasi'"
                       @keydown.enter.prevent="if(input.toLowerCase() === branchName.toLowerCase()) $el.closest('form').submit()" />
            </div>
            
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'branch-deactivate-modal')"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                        :disabled="input.toLowerCase() !== branchName.toLowerCase()"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                    Ya, Nonaktifkan
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal id="branch-activate-modal" maxWidth="md" type="success">
        <x-slot name="title">Aktifkan Cabang</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </x-slot>
        <x-slot name="description">
            Anda yakin ingin mengaktifkan cabang <span class="font-bold text-slate-900 dark:text-white" x-text="branchName"></span>? Cabang ini akan tersedia kembali pada pilihan form dan operasional harian.
        </x-slot>

        <form x-bind:action="toggleUrl" method="POST" x-data="{ input: '' }" @open-modal.window="if($event.detail === 'branch-activate-modal') input = ''">
            @csrf
            @method('PATCH')
            <div class="pt-2">
                <label class="sr-only" for="branch_activate_confirmation">Konfirmasi</label>
                <input type="text" name="activate_confirmation" id="branch_activate_confirmation" 
                       x-model="input"
                       class="block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500 dark:focus:ring-emerald-500" 
                       autocomplete="off"
                       x-bind:placeholder="'Ketik \'' + branchName + '\' untuk konfirmasi'"
                       @keydown.enter.prevent="if(input.toLowerCase() === branchName.toLowerCase()) $el.closest('form').submit()" />
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'branch-activate-modal')"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                        :disabled="input.toLowerCase() !== branchName.toLowerCase()"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                    Ya, Aktifkan
                </button>
            </div>
        </form>
    </x-modal>

    <x-modal id="branch-destroy-modal" maxWidth="md" type="danger">
        <x-slot name="title">Hapus Cabang</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </x-slot>
        <x-slot name="description">
            Anda yakin ingin menghapus cabang <span class="font-bold text-slate-900 dark:text-white" x-text="branchName"></span>? Cabang tidak dapat dihapus jika sudah digunakan untuk transaksi atau data pengguna.
        </x-slot>

        <form x-bind:action="destroyUrl" method="POST" x-data="{ input: '' }" @open-modal.window="if($event.detail === 'branch-destroy-modal') input = ''">
            @csrf
            @method('DELETE')
            <div class="pt-2">
                <label class="sr-only" for="branch_destroy_confirmation">Konfirmasi</label>
                <input type="text" name="destroy_confirmation" id="branch_destroy_confirmation" 
                       x-model="input"
                       placeholder="Ketik 'hapus' untuk konfirmasi"
                       class="block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-rose-500 dark:focus:ring-rose-500" 
                       autocomplete="off"
                       @keydown.enter.prevent="if(input.toLowerCase() === 'hapus') $el.closest('form').submit()" />
            </div>
            
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'branch-destroy-modal')"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                        :disabled="input.toLowerCase() !== 'hapus'"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                    Ya, Hapus
                </button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
