@php
    $showPassword = $showPassword ?? false;
    $showConfirmPassword = $showConfirmPassword ?? false;
    $showRole = $showRole ?? true;
    $selectedBranchIds = collect(old('branch_ids', $selectedBranchIds ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();
    $adminBranchGridClass = match (true) {
        ($branches ?? collect())->count() <= 1 => 'sm:grid-cols-1 lg:grid-cols-1',
        ($branches ?? collect())->count() === 2 => 'sm:grid-cols-2 lg:grid-cols-2',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
    $roleNames = ($roles ?? collect())
        ->mapWithKeys(fn ($role) => [(string) $role->id => strtolower(trim((string) $role->name))])
        ->all();
    $initialRoleId = (string) old('role_id', $user->role_id ?? optional(($roles ?? collect())->first())->id ?? '');
@endphp

<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
    
    {{-- Header Form Card --}}
    <div class="px-6 sm:px-8 py-5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20 flex items-center gap-3">
        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
        <h2 class="text-sm font-bold text-slate-800 dark:text-white">Formulir Data Pengguna</h2>
    </div>

    <div class="p-6 sm:px-8 sm:py-8">
        @if($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
                <p class="font-bold">Data belum bisa disimpan.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ $action }}"
            x-data="{
                roleId: @js($initialRoleId),
                roleNames: @js($roleNames),
                get roleName() {
                    return this.roleNames[String(this.roleId)] || '';
                }
            }">
            @csrf
            @if($method === 'PUT')
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- NAMA --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">Nama Lengkap</label>
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all overflow-hidden">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required placeholder="Contoh: Budi Santoso"
                               class="flex-1 w-full border-none p-0 text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 outline-none bg-white dark:bg-slate-900 dark:[color-scheme:dark]">
                    </div>
                    @error('name')
                        <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- USERNAME --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">Username</label>
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all overflow-hidden">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                        <input type="text" name="username" value="{{ old('username', $user->username ?? '') }}" required placeholder="Contoh: budi_s"
                               class="flex-1 w-full border-none p-0 text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 outline-none bg-white dark:bg-slate-900 dark:[color-scheme:dark]">
                    </div>
                    @error('username')
                        <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- EMAIL --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">Alamat Email</label>
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all overflow-hidden">
                        <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required placeholder="Contoh: budi@gmail.com"
                               class="flex-1 w-full border-none p-0 text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 outline-none bg-white dark:bg-slate-900 dark:[color-scheme:dark]">
                    </div>
                    @error('email')
                        <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ROLE --}}
                @if($showRole)
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">Hak Akses (Role)</label>
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all overflow-hidden pr-2">
                            <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <select name="role_id" required x-model="roleId" class="flex-1 w-full border-none p-0 text-sm font-medium text-slate-900 dark:text-white focus:ring-0 outline-none bg-white dark:bg-slate-900 cursor-pointer">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id ?? '') == $role->id ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('role_id')
                            <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- CABANG --}}
                @if(($usesBranches ?? false) && ($branches ?? collect())->isNotEmpty())
                    <div class="md:col-span-2" x-show="roleName === 'kasir'" x-cloak>
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">Cabang Kasir</label>
                                <p class="mb-3 text-[11px] font-medium text-slate-400">Kasir hanya dapat mengelola satu cabang operasional.</p>
                            </div>
                        </div>
                        <div class="flex min-h-[46px] items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                                <svg class="block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h1m-1 4h1m-1 4h1m5-4h1m-1 4h1"></path></svg>
                            </span>
                            <select name="branch_id" :required="roleName === 'kasir'" :disabled="roleName !== 'kasir'" class="flex-1 w-full border-none p-0 text-sm font-medium text-slate-900 dark:text-white focus:ring-0 outline-none bg-white dark:bg-slate-900 cursor-pointer">
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ (string) old('branch_id', $user->branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('branch_id')
                            <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($usesBranchAssignments ?? false)
                        <div class="md:col-span-2" x-show="roleName === 'admin'" x-cloak>
                            <div class="mb-3 flex items-start justify-between gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">Akses Cabang Admin</label>
                                    <p class="text-[11px] font-medium text-slate-400">Admin dapat mengelola lebih dari satu cabang. Centang cabang yang boleh diakses.</p>
                                </div>
                                <span class="hidden rounded-full bg-blue-50 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-blue-600 dark:bg-blue-500/10 dark:text-blue-300 sm:inline-flex">
                                    Multi Cabang
                                </span>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                                <div class="grid grid-cols-1 gap-2 {{ $adminBranchGridClass }}">
                                    @foreach($branches as $branch)
                                        <label class="group flex min-h-[52px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50/70 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-200 dark:hover:border-blue-500/50 dark:hover:bg-blue-500/10">
                                            <input
                                                type="checkbox"
                                                name="branch_ids[]"
                                                value="{{ $branch->id }}"
                                                :disabled="roleName !== 'admin'"
                                                class="peer sr-only"
                                                @checked(in_array((int) $branch->id, $selectedBranchIds, true))>
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white text-transparent transition peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white dark:border-slate-600 dark:bg-slate-900">
                                                <svg class="block h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            </span>
                                            <span class="min-w-0 truncate transition peer-checked:text-blue-700 dark:peer-checked:text-blue-300">{{ $branch->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @error('branch_ids')
                                <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                            @enderror
                            @error('branch_ids.*')
                                <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-[11px] font-medium text-slate-400">Centang beberapa cabang hanya untuk role admin. Jika role kasir dipilih, daftar ini akan diabaikan.</p>
                        </div>
                    @endif
                @endif

                {{-- PASSWORD --}}
                @if($showPassword)
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-widest">Kata Sandi</label>
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all overflow-hidden">
                            <svg class="w-5 h-5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <input type="password" id="create_password" name="password" required autocomplete="new-password" placeholder="Masukkan password kuat..."
                                   class="flex-1 w-full border-none p-0 text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-0 outline-none bg-white dark:bg-slate-900 dark:[color-scheme:dark]">
                            
                            {{-- Tombol Mata --}}
                            <button type="button" onclick="togglePassword('create_password', 'icon_create_pwd')" class="shrink-0 text-slate-400 hover:text-blue-500 transition-colors focus:outline-none">
                                <svg id="icon_create_pwd" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

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
                    {{ $buttonText }}
                </button>
            </div>

        </form>
    </div>
</div>

@if($showPassword)
{{-- Script untuk fungsi Hide/Show Password (Hanya dimuat jika input password muncul) --}}
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
        } else {
            input.type = 'password';
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
        }
    }
</script>
@endif
