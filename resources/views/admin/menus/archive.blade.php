@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Arsip Menu')

@push('styles')
@vite('resources/css/pages/admin-archive.css')
@endpush

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10" x-data="{
        restoreUrl: '',
        menuName: '',
        openMenuRestore(url, name) {
            this.restoreUrl = url;
            this.menuName = name;
            document.getElementById('menu_restore_confirmation').value = '';
            $dispatch('open-modal', 'menu-restore-modal');
        }
    }">
    @include('admin.menus.partials.archive.header')
    
    {{-- ================= TABS NAVIGATION ================= --}}
    <div class="flex rounded-xl bg-white p-1 border border-slate-200 shadow-sm dark:bg-slate-900 dark:border-slate-800 w-full mb-2">
        <a href="{{ route('admin.ingredients.archive') }}" class="flex-1 rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">Arsip Bahan</a>
        <a href="{{ route('admin.menus.archive') }}" class="flex-1 rounded-lg px-4 py-1.5 text-[13px] font-semibold transition-all text-center bg-slate-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400">Arsip Menu</a>
    </div>
    @include('admin.menus.partials.archive.alerts')
    @include('admin.menus.partials.archive.filters')
    @include('admin.menus.partials.archive.table')

    {{-- Modal Aktifkan Kembali Menu --}}
    <x-modal id="menu-restore-modal" maxWidth="md" type="success">
        <x-slot name="title">Aktifkan Kembali Menu</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </x-slot>
        <x-slot name="description">
            Anda yakin ingin mengaktifkan kembali menu <span class="font-bold text-slate-900 dark:text-white" x-text="menuName"></span>? Menu ini akan tersedia kembali dan dapat dibeli oleh pelanggan.
        </x-slot>

        <form x-bind:action="restoreUrl" method="POST">
            @csrf
            @method('PATCH')
            <div class="pt-2">
                <label class="sr-only" for="menu_restore_confirmation">Konfirmasi</label>
                <input type="text" name="restore_confirmation" id="menu_restore_confirmation" required pattern="AKTIFKAN" title="Ketik AKTIFKAN" placeholder="Ketik AKTIFKAN"
                       data-uppercase-input
                       class="uppercase block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 placeholder:normal-case focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-emerald-500 dark:focus:ring-emerald-500" />
            </div>
            
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'menu-restore-modal')"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:w-auto">
                    Ya, Aktifkan
                </button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
