<div class="bg-white dark:bg-slate-900 
            shadow-lg rounded-2xl 
            border border-slate-200 dark:border-slate-800 
            p-8">

    <form method="POST" action="{{ $action }}">
        @csrf

        @if($method === 'PUT')
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Nama --}}
            <div>
                <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                    Nama
                </label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $user->name ?? '') }}"
                       required
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
                       required
                       class="w-full px-4 py-2 rounded-xl
                              border border-slate-300 dark:border-slate-700
                              bg-white dark:bg-slate-800
                              text-slate-700 dark:text-white
                              focus:ring-2 focus:ring-blue-500
                              focus:border-blue-500 outline-none transition">
            </div>

            {{-- Password --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                    Password
                </label>
                <input type="password"
                       name="password"
                       placeholder="{{ isset($user) ? 'Kosongkan jika tidak ingin mengubah' : 'Masukkan password' }}"
                       {{ isset($user) ? '' : 'required' }}
                       class="w-full px-4 py-2 rounded-xl
                              border border-slate-300 dark:border-slate-700
                              bg-white dark:bg-slate-800
                              text-slate-700 dark:text-white
                              focus:ring-2 focus:ring-blue-500
                              focus:border-blue-500 outline-none transition">
            </div>

            {{-- Role --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2 text-slate-700 dark:text-slate-300">
                    Role
                </label>
                <select name="role_id"
                        required
                        class="w-full px-4 py-2 rounded-xl
                               border border-slate-300 dark:border-slate-700
                               bg-white dark:bg-slate-800
                               text-slate-700 dark:text-white
                               focus:ring-2 focus:ring-blue-500
                               focus:border-blue-500 outline-none transition">

                    @foreach($roles as $role)
                        <option value="{{ $role->id }}"
                            {{ old('role_id', $user->role_id ?? '') == $role->id ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach

                </select>
            </div>

        </div>

        {{-- Button --}}
        <div class="flex justify-end gap-3 mt-8">

            <a href="{{ route('owner.users.index') }}"
               class="px-5 py-2 rounded-xl 
                      bg-slate-100 hover:bg-slate-200
                      dark:bg-slate-800 dark:hover:bg-slate-700
                      text-slate-600 dark:text-slate-300
                      text-sm transition">
                Batal
            </a>

            <button type="submit"
                    class="px-6 py-2 rounded-xl
                           bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-medium
                           shadow-md transition">
                {{ $buttonText }}
            </button>

        </div>

    </form>

</div>