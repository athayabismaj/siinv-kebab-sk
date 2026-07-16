@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Manajemen Bahan')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10" x-data="{
        destroyUrl: '',
        ingredientName: '',
        restoreUrl: '',
        openIngredientDestroy(url, name) {
            this.destroyUrl = url;
            this.ingredientName = name;
            document.getElementById('ingredient_destroy_confirmation').value = '';
            $dispatch('open-modal', 'ingredient-destroy-modal');
        },
        openIngredientRestore(url, name) {
            this.restoreUrl = url;
            this.ingredientName = name;
            document.getElementById('ingredient_restore_confirmation').value = '';
            $dispatch('open-modal', 'ingredient-restore-modal');
        }
    }">
    @include('admin.ingredients.partials.index.header')
    @include('admin.ingredients.partials.index.alerts')
    @include('admin.ingredients.partials.index.lifecycle-tabs')
    @include('admin.ingredients.partials.index.filters')
    @include('admin.ingredients.partials.index.table')
    @include('admin.ingredients.partials.index.pagination')

    {{-- Modal Nonaktifkan Bahan Baku --}}
    <x-modal id="ingredient-destroy-modal" maxWidth="md" type="danger">
        <x-slot name="title">Nonaktifkan Bahan Baku</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </x-slot>
        <x-slot name="description">
            Anda yakin ingin menonaktifkan bahan baku <span class="font-bold text-slate-900 dark:text-white" x-text="ingredientName"></span>? Data akan berstatus diarsipkan dan tidak dapat dipilih pada resep baru.
        </x-slot>

        <form x-bind:action="destroyUrl" method="POST">
            @csrf
            @method('DELETE')
            <div class="pt-2">
                <label class="sr-only" for="ingredient_destroy_confirmation">Konfirmasi</label>
                <input type="text" name="destroy_confirmation" id="ingredient_destroy_confirmation" required pattern="NONAKTIF" title="Ketik NONAKTIF" placeholder="Ketik NONAKTIF"
                       data-uppercase-input
                       class="uppercase block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 placeholder:normal-case focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-rose-500 dark:focus:ring-rose-500" />
            </div>
            
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'ingredient-destroy-modal')"
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

    <x-modal id="ingredient-restore-modal" maxWidth="md" type="success">
        <x-slot name="title">Pulihkan Bahan Baku</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        </x-slot>
        <x-slot name="description">
            Pulihkan <span class="font-bold text-slate-900 dark:text-white" x-text="ingredientName"></span> agar kembali tersedia pada pengelolaan bahan dan resep.
        </x-slot>

        <form x-bind:action="restoreUrl" method="POST">
            @csrf
            @method('PATCH')
            <div class="pt-2">
                <label class="sr-only" for="ingredient_restore_confirmation">Konfirmasi</label>
                <input type="text" name="restore_confirmation" id="ingredient_restore_confirmation" required pattern="AKTIFKAN" title="Ketik AKTIFKAN" placeholder="Ketik AKTIFKAN"
                       data-uppercase-input
                       class="uppercase block w-full rounded-xl border-slate-300 px-4 py-2.5 text-sm shadow-sm placeholder:text-slate-400 placeholder:normal-case focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
            </div>
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'ingredient-restore-modal')" class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:w-auto dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">Batal</button>
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 sm:w-auto">Pulihkan Bahan</button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
