@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Manajemen Menu')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10" x-data="{
        destroyUrl: '',
        menuName: '',
        openMenuDestroy(url, name) {
            this.destroyUrl = url;
            this.menuName = name;
            document.getElementById('menu_destroy_confirmation').value = '';
            $dispatch('open-modal', 'menu-destroy-modal');
        }
    }">
    @include('admin.menus.partials.index.header')
    @include('admin.menus.partials.index.alerts')
    @include('admin.menus.partials.index.table')

    {{-- Modal Nonaktifkan Menu --}}
    <x-modal id="menu-destroy-modal" maxWidth="md" type="danger">
        <x-slot name="title">Nonaktifkan Menu</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </x-slot>
        <x-slot name="description">
            Anda yakin ingin menonaktifkan menu <span class="font-bold text-slate-900 dark:text-white" x-text="menuName"></span>? Menu ini akan dipindahkan ke Arsip dan tidak dapat dibeli oleh pelanggan. Anda dapat mengaktifkannya kembali nanti.
        </x-slot>

        <form x-bind:action="destroyUrl" method="POST">
            @csrf
            @method('DELETE')
            <div class="pt-2">
                <label class="sr-only" for="menu_destroy_confirmation">Konfirmasi</label>
                <input type="text" name="destroy_confirmation" id="menu_destroy_confirmation" required pattern="NONAKTIF" title="Ketik NONAKTIF" placeholder="Ketik NONAKTIF"
                       data-uppercase-input
                       class="uppercase block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 placeholder:normal-case focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-rose-500 dark:focus:ring-rose-500" />
            </div>
            
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'menu-destroy-modal')"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 sm:w-auto">
                    Ya, Nonaktifkan
                </button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
