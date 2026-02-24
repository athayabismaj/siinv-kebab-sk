<div class="space-y-6">

    {{-- Nama --}}
    <div>
        <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
            Nama
        </label>
        <input type="text"
               name="name"
               value="{{ old('name', $user->name ?? '') }}"
               placeholder="Masukkan nama"
               class="w-full px-4 py-2 rounded-xl
                      border border-slate-300 dark:border-slate-700
                      bg-white dark:bg-slate-800
                      text-slate-700 dark:text-white
                      focus:ring-2 focus:ring-blue-500
                      focus:border-blue-500 outline-none transition">
    </div>

    {{-- Username --}}
    <div>
        <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
            Username
        </label>
        <input type="text"
               name="username"
               value="{{ old('username', $user->username ?? '') }}"
               placeholder="Masukkan username"
               class="w-full px-4 py-2 rounded-xl
                      border border-slate-300 dark:border-slate-700
                      bg-white dark:bg-slate-800
                      text-slate-700 dark:text-white
                      focus:ring-2 focus:ring-blue-500
                      focus:border-blue-500 outline-none transition">
    </div>

    {{-- Password --}}
    <div>
        <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
            Password
        </label>
        <input type="password"
               name="password"
               placeholder="Kosongkan jika tidak ingin mengubah"
               class="w-full px-4 py-2 rounded-xl
                      border border-slate-300 dark:border-slate-700
                      bg-white dark:bg-slate-800
                      text-slate-700 dark:text-white
                      focus:ring-2 focus:ring-blue-500
                      focus:border-blue-500 outline-none transition">
    </div>

    {{-- Role --}}
    <div>
        <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
            Role
        </label>
        <select name="role_id"
                class="w-full px-4 py-2 rounded-xl
                       border border-slate-300 dark:border-slate-700
                       bg-white dark:bg-slate-800
                       text-slate-700 dark:text-white
                       focus:ring-2 focus:ring-blue-500
                       focus:border-blue-500 outline-none transition">

            @foreach($roles as $role)
                <option value="{{ $role->id }}"
                    {{ (old('role_id', $user->role_id ?? '') == $role->id) ? 'selected' : '' }}>
                    {{ ucfirst($role->name) }}
                </option>
            @endforeach

        </select>
    </div>

</div>