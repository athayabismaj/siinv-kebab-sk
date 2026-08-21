@extends('layouts.app')

@section('title', 'Pengaturan QRIS')

@section('content')
<div
    class="w-full space-y-5 overflow-x-hidden pb-10"
    x-data="{}"
    @if($errors->any()) x-init="$nextTick(() => $dispatch('open-modal', 'qris-input-modal'))" @endif
>
    <x-page-header
        title="Pengaturan QRIS"
        subtitle="Atur QRIS merchant yang digunakan untuk pembayaran pada setiap cabang."
        :breadcrumb-parent="$isOwner ? 'Owner' : 'Admin'"
        breadcrumb-child="QRIS">
        <button
            type="button"
            @click="$dispatch('open-modal', 'qris-input-modal')"
            class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-bold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-950 sm:w-auto"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5" />
            </svg>
            {{ $activeConfig ? 'Ganti QRIS' : 'Tambahkan QRIS' }}
        </button>
    </x-page-header>

    @if($isOwner)
        <form method="GET" action="{{ route('owner.qris.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 sm:px-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-900 dark:text-white">Cabang yang dikelola</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Pilih cabang untuk melihat atau memperbarui konfigurasi QRIS.</p>
                </div>
                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18m6 0V11l-6-4M9 9h1m-1 4h1m-1 4h1m5-4h1m-1 4h1" />
                    </svg>
                    <select
                        id="branch_id_filter"
                        name="branch_id"
                        aria-label="Pilih cabang"
                        onchange="this.form.submit()"
                        class="h-10 w-full rounded-lg border-slate-300 bg-white pl-9 pr-9 text-sm font-semibold text-slate-800 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) $selectedBranch->id === (int) $branch->id)>{{ $branch->name }} ({{ $branch->code }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    @endif

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <header class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:flex-row sm:items-start sm:justify-between sm:px-6">
            <div class="flex min-w-0 items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4v18m6 0V11l-6-4M9 9h1m-1 4h1m-1 4h1m5-4h1m-1 4h1" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="truncate text-base font-bold text-slate-900 dark:text-white">{{ $selectedBranch->name }}</h2>
                        <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[10px] font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $selectedBranch->code }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $selectedBranch->address ?: 'Alamat cabang belum tersedia.' }}</p>
                </div>
            </div>
            <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold {{ $activeConfig ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                <span class="h-2 w-2 rounded-full {{ $activeConfig ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                {{ $activeConfig ? 'Aktif' : 'Belum dikonfigurasi' }}
            </span>
        </header>

        @if($activeConfig)
            <div class="px-5 py-2 sm:px-6">
                <dl class="divide-y divide-slate-100 dark:divide-slate-800 lg:grid lg:grid-cols-2 lg:divide-y-0">
                    @foreach([
                        ['label' => 'Nama merchant', 'value' => $activeConfig->merchant_display_name ?: $activeConfig->merchant_name],
                        ['label' => 'Kota merchant', 'value' => $activeConfig->merchant_city ?: '-'],
                        ['label' => 'Terakhir diperbarui', 'value' => optional($activeConfig->updated_at)->translatedFormat('d M Y, H:i')],
                        ['label' => 'Diperbarui oleh', 'value' => $activeConfig->updatedBy?->name ?: '-'],
                    ] as $index => $detail)
                        <div class="grid grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] gap-4 py-4 lg:px-5 {{ $index % 2 === 0 ? 'lg:border-r lg:border-slate-100 lg:pl-0 dark:lg:border-slate-800' : 'lg:pr-0' }} {{ $index > 1 ? 'lg:border-t lg:border-slate-100 dark:lg:border-slate-800' : '' }}">
                            <dt class="text-sm text-slate-500 dark:text-slate-400">{{ $detail['label'] }}</dt>
                            <dd class="min-w-0 text-right">
                                <span
                                    class="block whitespace-normal text-sm font-semibold leading-5 text-slate-900 [overflow-wrap:anywhere] dark:text-white"
                                    title="{{ $detail['value'] }}"
                                >{{ $detail['value'] }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <footer class="flex flex-col gap-4 border-t border-slate-200 bg-slate-50/60 px-5 py-4 dark:border-slate-800 dark:bg-slate-950/30 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="flex max-w-2xl items-start gap-2 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Payload QRIS disimpan terenkripsi dan tidak ditampilkan kembali setelah tersimpan.
                </p>
                <form method="POST" action="{{ route($routePrefix.'.qris.deactivate', $activeConfig) }}" onsubmit="return confirm('Nonaktifkan QRIS untuk cabang ini?')">
                    @csrf
                    @method('PATCH')
                    <button class="inline-flex h-9 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-3.5 text-xs font-bold text-rose-600 transition hover:border-rose-300 hover:bg-rose-50 dark:border-slate-700 dark:bg-slate-900 dark:text-rose-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 sm:w-auto">Nonaktifkan</button>
                </form>
            </footer>
        @else
            <div class="flex items-start gap-4 px-5 py-7 sm:px-6">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400 dark:bg-slate-800">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h2v2h-2v-2zm4 0h2v6h-6v-2h4v-4zm-4 4h2v2h-2v-2z" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Belum ada QRIS aktif</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">Tambahkan QRIS merchant melalui tombol di bagian atas agar cabang ini dapat menerima pembayaran QRIS dinamis.</p>
                </div>
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <header class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800 sm:px-6">
            <div>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Riwayat konfigurasi</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Catatan perubahan QRIS untuk kebutuhan audit.</p>
            </div>
            <span class="shrink-0 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $history->count() }} data</span>
        </header>

        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50/60 text-xs font-semibold text-slate-500 dark:border-slate-800 dark:bg-slate-950/30 dark:text-slate-400">
                    <tr>
                        <th class="w-[32%] px-6 py-3">Merchant</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Terakhir diubah</th>
                        <th class="px-6 py-3">Pengguna</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($history as $config)
                        <tr class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                            <td class="px-6 py-4">
                                <p
                                    class="max-w-md whitespace-normal font-semibold leading-5 text-slate-900 [overflow-wrap:anywhere] dark:text-white"
                                    title="{{ $config->merchant_display_name ?: $config->merchant_name }}"
                                >{{ $config->merchant_display_name ?: $config->merchant_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $config->merchant_city ?: '-' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold {{ $config->is_active ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $config->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $config->is_active ? 'Aktif' : 'Tidak aktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ optional($config->updated_at)->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{ $config->updatedBy?->name ?: $config->createdBy?->name ?: '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                @unless($config->is_active)
                                    <form method="POST" action="{{ route($routePrefix.'.qris.activate', $config) }}" onsubmit="return confirm('Aktifkan kembali QRIS ini? QRIS aktif saat ini akan dinonaktifkan.')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">Aktifkan</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">Digunakan</span>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">Belum ada riwayat konfigurasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            @forelse($history as $config)
                <article class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p
                                class="whitespace-normal text-sm font-semibold leading-5 text-slate-900 [overflow-wrap:anywhere] dark:text-white"
                                title="{{ $config->merchant_display_name ?: $config->merchant_name }}"
                            >{{ $config->merchant_display_name ?: $config->merchant_name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $config->merchant_city ?: '-' }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold {{ $config->is_active ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $config->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                            {{ $config->is_active ? 'Aktif' : 'Tidak aktif' }}
                        </span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-xs dark:border-slate-800">
                        <div><dt class="text-slate-400">Diubah</dt><dd class="mt-1 text-slate-600 dark:text-slate-300">{{ optional($config->updated_at)->translatedFormat('d M Y, H:i') }}</dd></div>
                        <div><dt class="text-slate-400">Pengguna</dt><dd class="mt-1 truncate text-slate-600 dark:text-slate-300">{{ $config->updatedBy?->name ?: $config->createdBy?->name ?: '-' }}</dd></div>
                    </dl>
                    @unless($config->is_active)
                        <form method="POST" action="{{ route($routePrefix.'.qris.activate', $config) }}" class="mt-3" onsubmit="return confirm('Aktifkan kembali QRIS ini? QRIS aktif saat ini akan dinonaktifkan.')">
                            @csrf
                            @method('PATCH')
                            <button class="w-full rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">Aktifkan QRIS ini</button>
                        </form>
                    @endunless
                </article>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-400">Belum ada riwayat konfigurasi.</div>
            @endforelse
        </div>
    </section>

    <x-modal id="qris-input-modal" maxWidth="xl">
        <x-slot name="title">{{ $activeConfig ? 'Ganti QRIS' : 'Tambahkan QRIS' }}</x-slot>
        <x-slot name="icon">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h2v2h-2v-2zm4 0h2v6h-6v-2h4v-4zm-4 4h2v2h-2v-2z" />
            </svg>
        </x-slot>
        <x-slot name="description">
            Cabang <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $selectedBranch->name }}</span>
        </x-slot>

        <form method="POST" action="{{ route($routePrefix.'.qris.store') }}" class="pb-1" x-data="{ manualInput: {{ old('qris_payload') ? 'true' : 'false' }} }">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">

            @if($errors->any())
                <div class="mb-4 flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M10.29 3.86 1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    </svg>
                    <div><p class="font-bold">QRIS belum dapat disimpan</p><p class="mt-0.5 text-xs">{{ $errors->first() }}</p></div>
                </div>
            @endif

            <label class="group block cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-5 py-7 text-center transition hover:border-blue-400 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-950/50 dark:hover:border-blue-500 dark:hover:bg-blue-500/5">
                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg bg-white text-blue-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-blue-400 dark:ring-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </span>
                <span class="mt-3 block text-sm font-bold text-slate-900 dark:text-white">Unggah gambar QRIS</span>
                <span class="mt-1 block text-xs text-slate-500">PNG, JPG, atau WebP &middot; maksimal 10 MB</span>
                <input type="file" accept="image/png,image/jpeg,image/webp" data-qris-image class="sr-only">
            </label>
            <p data-qris-status class="mt-2 text-xs text-slate-500 dark:text-slate-400">Belum ada gambar yang dipilih.</p>

            <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                <button type="button" @click="manualInput = !manualInput" class="flex w-full items-center justify-between text-left text-sm font-semibold text-slate-700 dark:text-slate-300">
                    <span>Masukkan payload secara manual</span>
                    <svg class="h-4 w-4 text-slate-400 transition" :class="manualInput && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="manualInput" x-collapse x-cloak class="pt-3">
                    <label for="qris_payload" class="sr-only">Payload QRIS</label>
                    <textarea
                        id="qris_payload"
                        name="qris_payload"
                        rows="4"
                        maxlength="5000"
                        data-qris-payload
                        autocomplete="off"
                        spellcheck="false"
                        class="w-full resize-none rounded-lg border-slate-300 bg-white font-mono text-xs leading-relaxed text-slate-700 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        placeholder="Tempel payload QRIS di sini."
                    >{{ old('qris_payload') }}</textarea>
                </div>
            </div>

            <p class="mt-4 flex items-start gap-2 text-xs leading-5 text-slate-500 dark:text-slate-400">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Sistem memvalidasi merchant, mata uang, dan CRC sebelum payload disimpan terenkripsi.
            </p>

            <div class="mt-5 flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
                <button type="button" @click="$dispatch('close-modal', 'qris-input-modal')" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 sm:w-auto">Batal</button>
                <button class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-blue-600 px-5 text-sm font-bold text-white transition hover:bg-blue-700 sm:w-auto">
                    {{ $activeConfig ? 'Ganti QRIS' : 'Simpan QRIS' }}
                </button>
            </div>
        </form>
    </x-modal>

</div>
@endsection
