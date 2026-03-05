<div class="bg-white dark:bg-slate-900
            border border-slate-200 dark:border-slate-800
            rounded-2xl shadow-sm">

<form method="POST" action="{{ $action }}">
@csrf
@if($method === 'PUT')
    @method('PUT')
@endif

<div class="p-8 space-y-8">

    <div class="border-b border-slate-200 dark:border-slate-800 pb-4">
        <h2 class="text-sm font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
            Informasi Kategori
        </h2>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-600 dark:text-slate-300 mb-2">
            Nama Kategori
        </label>

        <input type="text"
               name="name"
               value="{{ old('name', $ingredientCategory->name ?? '') }}"
               required
               class="w-full px-4 py-3 rounded-xl
                      border border-slate-300 dark:border-slate-700
                      bg-white dark:bg-slate-800
                      text-slate-800 dark:text-white
                      focus:ring-2 focus:ring-blue-500">

        @error('name')
            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
        @enderror
    </div>

</div>

<div class="flex justify-between items-center
            px-8 py-6
            bg-slate-50 dark:bg-slate-800
            border-t border-slate-200 dark:border-slate-700
            rounded-b-2xl">

    <a href="{{ route('admin.ingredient-categories.index') }}"
       class="text-sm text-slate-500 hover:text-blue-600 transition">
        ← Kembali
    </a>

    <button type="submit"
            class="px-6 py-2.5 rounded-xl
                   bg-blue-600 text-white
                   text-sm font-medium
                   hover:bg-blue-700
                   active:scale-[0.98]
                   transition">
        {{ $buttonText }}
    </button>

</div>

</form>
</div>