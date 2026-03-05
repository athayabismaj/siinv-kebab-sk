@extends('layouts.app')

@section('title', 'Penyesuaian Stok')

@section('content')

<div class="space-y-8">


    {{-- ================= HEADER ================= --}}
    <div>

        <h1 class="text-2xl font-semibold text-slate-800 dark:text-white">
            Penyesuaian Stok
        </h1>

        <p class="text-sm text-slate-500 dark:text-slate-400">
            Perbarui jumlah stok bahan secara manual
        </p>

    </div>



    {{-- ================= CARD ================= --}}
    <div class="bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800
                rounded-2xl shadow-sm p-8">



        {{-- ================= ERROR ALERT ================= --}}
        @if(session('error'))

        <div class="mb-6 px-4 py-3 rounded-xl
                    border border-red-200 bg-red-50
                    text-sm text-red-700">

            {{ session('error') }}

        </div>

        @endif



        {{-- ================= INFO BAHAN ================= --}}
        <div class="mb-8 p-4 rounded-xl
                    bg-slate-50 dark:bg-slate-800
                    border border-slate-200 dark:border-slate-700">

            <p class="text-xs text-slate-500 mb-1">
                Bahan
            </p>

            <p class="font-semibold text-slate-800 dark:text-white">
                {{ $ingredient->name }}
            </p>

            <p class="text-sm text-slate-500 mt-1">
                Stok saat ini :

                <span class="font-medium text-slate-700 dark:text-slate-200">
                    {{ $ingredient->converted_stock }}
                    {{ $ingredient->display_unit }}
                </span>

            </p>

        </div>



        {{-- ================= FORM ================= --}}
        <form method="POST"
              x-data="{ submitting: false }"
              @submit="submitting = true"
              class="space-y-6">

            @csrf



            {{-- INPUT NEW STOCK --}}
            <div>

                <label class="block text-sm font-medium mb-2">
                    Stok Baru
                </label>

                <p class="mb-2 text-xs text-slate-500">
                    Input dalam satuan {{ $ingredient->display_unit }}
                </p>

                <input
                    type="number"
                    step="0.01"
                    name="new_stock"
                    value="{{ old('new_stock') }}"

                    class="w-full px-4 py-2.5 rounded-xl
                           border border-slate-300 dark:border-slate-700
                           bg-white dark:bg-slate-800
                           text-sm
                           focus:ring-2 focus:ring-blue-500
                           focus:outline-none">

                @error('new_stock')

                <p class="mt-1 text-xs text-red-600">
                    {{ $message }}
                </p>

                @enderror

            </div>



            {{-- INPUT NOTE --}}
            <div>

                <label class="block text-sm font-medium mb-2">
                    Alasan Penyesuaian
                </label>

                <textarea
                    name="note"
                    rows="3"

                    class="w-full px-4 py-2.5 rounded-xl
                           border border-slate-300 dark:border-slate-700
                           bg-white dark:bg-slate-800
                           text-sm
                           focus:ring-2 focus:ring-blue-500
                           focus:outline-none">{{ old('note') }}</textarea>

                @error('note')

                <p class="mt-1 text-xs text-red-600">
                    {{ $message }}
                </p>

                @enderror

            </div>



            {{-- ================= ACTION BUTTONS ================= --}}
            <div class="flex justify-end gap-3 pt-6
                        border-t border-slate-200 dark:border-slate-800">

                <a href="{{ route('admin.stocks.index') }}"
                   class="px-5 py-2.5 rounded-xl
                          bg-slate-200 dark:bg-slate-700
                          text-sm
                          hover:bg-slate-300 dark:hover:bg-slate-600
                          transition">

                    Batal

                </a>


                <button
                    type="submit"
                    :disabled="submitting"
                    class="px-6 py-2.5 rounded-xl
                           bg-yellow-500 text-white
                           text-sm font-medium
                           hover:bg-yellow-600
                           disabled:opacity-60 disabled:cursor-not-allowed
                           transition shadow-sm">

                    <span x-show="!submitting">Simpan Penyesuaian</span>
                    <span x-show="submitting" x-cloak>Menyimpan...</span>

                </button>

            </div>

        </form>

    </div>

</div>

@endsection
