@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi Pengguna')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')
{{-- Perbaikan: max-w-3xl mx-auto dihapus, diganti w-full biar merentang ke samping --}}
<div class="w-full space-y-8">

    <x-page-header 
        title="Atur Ulang Kata Sandi" 
        subtitle="Perbarui kata sandi untuk akun pengguna ini. Pastikan Anda memberikan kata sandi baru yang kuat, lalu beritahukan kata sandi tersebut kepada yang bersangkutan." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Atur Ulang Kata Sandi">
    </x-page-header>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        {{-- Info User Target (Background menyatu bersih dengan tema) --}}
        <div class="px-6 sm:px-8 py-6 border-b border-slate-200 dark:border-slate-800 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0 ring-1 ring-blue-100 dark:ring-blue-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Mereset sandi untuk:</p>
                <p class="text-sm font-black text-slate-900 dark:text-white">{{ $user->name }}</p>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mt-0.5">{{ $user->email }} &bull; Role: <span class="uppercase">{{ $user->role->name }}</span></p>
            </div>
        </div>

        <div class="p-6 sm:px-8 sm:py-8">
            <form method="POST" action="{{ route('owner.users.resetPassword', $user->id) }}">
                @csrf

                <div class="grid grid-cols-1 gap-6">

                    {{-- Password Baru --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">
                            KATA SANDI BARU
                        </label>
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all overflow-hidden">
                            
                            {{-- Icon Kiri (Gembok) --}}
                            <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            
                            {{-- Input Text --}}
                            <input type="password"
                                   id="new_password"
                                   name="password"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Masukkan kata sandi baru..."
                                   class="flex-1 w-full border-none p-0 text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 outline-none bg-white dark:bg-slate-900 dark:[color-scheme:dark]">
                            
                            {{-- Tombol Mata Kanan (Hide/Show) --}}
                            <button type="button" data-password-toggle data-password-input="new_password" data-password-icon="icon_new_pwd" class="shrink-0 text-slate-400 hover:text-blue-500 transition-colors focus:outline-none">
                                <svg id="icon_new_pwd" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>

                        </div>
                        @error('password')
                            <p class="mt-2 text-xs text-red-500 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Konfirmasi Password --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2">
                            KONFIRMASI KATA SANDI
                        </label>
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all overflow-hidden">
                            
                            {{-- Icon Kiri (Centang Gembok) --}}
                            <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            
                            {{-- Input Text --}}
                            <input type="password"
                                   id="confirm_password"
                                   name="password_confirmation"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Ulangi kata sandi baru..."
                                   class="flex-1 w-full border-none p-0 text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 outline-none bg-white dark:bg-slate-900 dark:[color-scheme:dark]">
                            
                            {{-- Tombol Mata Kanan (Hide/Show) --}}
                            <button type="button" data-password-toggle data-password-input="confirm_password" data-password-icon="icon_confirm_pwd" class="shrink-0 text-slate-400 hover:text-blue-500 transition-colors focus:outline-none">
                                <svg id="icon_confirm_pwd" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>

                        </div>
                    </div>

                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('owner.users.index') }}"
                       class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[13px] font-bold hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        Batal
                    </a>

                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white text-[13px] font-bold shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Simpan Kata Sandi Baru
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>

{{-- Script untuk fungsi Hide/Show Password --}}
@endsection
