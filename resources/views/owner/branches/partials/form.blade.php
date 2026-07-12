<div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Data Cabang</p>
        <h2 class="mt-1 text-lg font-black text-slate-900 dark:text-white">{{ $title }}</h2>
    </div>

    <form method="POST" action="{{ $action }}" class="p-6">
        @csrf
        @if(($method ?? 'POST') !== 'POST')
            @method($method)
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Nama Cabang</label>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $branch->name) }}"
                    required
                    placeholder="Contoh: Kebab SK Cabang B"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @error('name')
                    <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Kode Cabang</label>
                <input
                    type="text"
                    name="code"
                    value="{{ old('code', $branch->code) }}"
                    placeholder="Contoh: cabang-b"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <p class="mt-2 text-[11px] font-medium text-slate-400">Kosongkan jika ingin dibuat otomatis dari nama cabang.</p>
                @error('code')
                    <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Alamat</label>
                <textarea
                    name="address"
                    rows="3"
                    placeholder="Alamat cabang atau keterangan lokasi"
                    class="w-full resize-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('address', $branch->address) }}</textarea>
                @error('address')
                    <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/60">
                    <span>
                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Cabang Aktif</span>
                        <span class="block text-xs font-medium text-slate-500 dark:text-slate-400">Cabang aktif akan muncul pada pilihan cabang pengguna.</span>
                    </span>
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="h-5 w-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        {{ old('is_active', $branch->is_active ?? true) ? 'checked' : '' }}>
                </label>
            </div>
        </div>

        <div class="mt-8 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
            <a href="{{ route('owner.branches.index') }}"
               class="inline-flex items-center justify-center rounded-xl bg-slate-100 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                Batal
            </a>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                {{ $buttonText }}
            </button>
        </div>
    </form>
</div>
