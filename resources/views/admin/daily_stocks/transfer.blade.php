@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Transfer Stok Harian')
@section('disableGlobalAlerts', 'true')

@push('styles')
@vite('resources/css/pages/daily-stock-transfer.css')
@endpush

@section('content')
<div class="transfer-stock-page w-full space-y-5 overflow-x-hidden pb-10">
    <x-page-header
        title="Siapkan Saldo Awal Stok"
        subtitle="Periksa saldo yang terbawa, sesuaikan dengan stok fisik, lalu simpan."
        breadcrumb-parent="Stok Harian"
        breadcrumb-child="Transfer Bahan">
        <div class="flex flex-col items-center gap-3 sm:flex-row">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-[10px] font-black text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ strtoupper(substr($session->cashier->name ?? 'U', 0, 1)) }}</span>
                    {{ $session->cashier->name ?? 'User Tidak Diketahui' }}
                </span>
                <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">{{ $session->session_date->translatedFormat('d F Y') }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-black uppercase tracking-wider text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-500/10 dark:text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Sesi #{{ $session->id }} Buka</span>
            </div>
            <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-[13px] font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 sm:w-auto dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"><i class="fa-solid fa-arrow-left text-xs text-slate-400"></i>Kembali ke Sesi</a>
        </div>
    </x-page-header>

    @include('partials.flash_alerts', ['class' => 'w-full space-y-2'])

    @if($session->carryForwardSource)
        <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-amber-600 ring-1 ring-amber-200 dark:bg-slate-900 dark:text-amber-300 dark:ring-amber-500/30"><i class="fa-solid fa-clock-rotate-left text-sm"></i></span>
            <div><p class="text-sm font-black">Saldo sisa dari sesi {{ $session->carryForwardSource->session_date->translatedFormat('d F Y') }}</p><p class="mt-0.5 text-xs font-semibold leading-relaxed text-amber-700 dark:text-amber-200/80">Naikkan jika mengambil dari gudang, atau turunkan jika stok fisik lebih sedikit.</p></div>
        </div>
    @endif

    @if($errors->any())
        <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-white px-4 py-3 shadow-sm dark:border-rose-900/60 dark:bg-slate-900">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div><p class="text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-300">Input Belum Valid</p><ul class="mt-1 list-disc space-y-0.5 pl-4 text-sm font-semibold text-slate-700 dark:text-slate-200">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.daily-stocks.transfer.form') }}" class="transfer-filter-form relative z-10">
        <input type="hidden" name="session_id" value="{{ $session->id }}">
        <div class="transfer-filter-search relative flex h-11 items-center rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 text-xs text-slate-400"></i>
            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama bahan..." class="h-full w-full rounded-xl border-0 bg-transparent pl-10 pr-4 text-[13px] font-semibold text-slate-700 outline-none placeholder:text-slate-400 focus:ring-0 dark:text-slate-200">
        </div>
        <select name="category_id" data-submit-on-change class="transfer-filter-category h-11 cursor-pointer rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 shadow-sm outline-none dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
            <option value="">Semua Kategori</option>
            @foreach($categories as $category)<option value="{{ $category->id }}" {{ (int) $selectedCategoryId === (int) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>@endforeach
        </select>
        @if($search || $selectedCategoryId > 0)<a href="{{ route('admin.daily-stocks.transfer.form', ['session_id' => $session->id]) }}" class="inline-flex h-11 items-center justify-center rounded-xl px-4 text-xs font-bold text-slate-400 hover:text-rose-500">Atur Ulang</a>@endif
        <button type="submit" class="sr-only">Cari</button>
    </form>

    <form method="POST" action="{{ route('admin.daily-stocks.transfer', ['search' => $search, 'category_id' => $selectedCategoryId]) }}"
        x-data="{
            isMobile: window.innerWidth < 768, activeIngredient: null, visibleCount: 10,
            total: {{ $ingredients->count() }}, filter: 'all', states: {},
            registerState(id, difference, dirty) { this.states = { ...this.states, [id]: { difference: Number(difference) || 0, dirty: !!dirty } } },
            adjustedCount() { return Object.values(this.states).filter(state => Math.abs(state.difference) > .0001).length },
            issueCount() { return Object.values(this.states).filter(state => state.difference < 0).length },
            dirtyCount() { return Object.values(this.states).filter(state => state.dirty).length },
            visibleFor(id, index) {
                const difference = this.states[id]?.difference || 0;
                if (this.filter === 'adjusted' && Math.abs(difference) <= .0001) return false;
                if (this.filter === 'issues' && difference >= 0) return false;
                return !this.isMobile || this.filter !== 'all' || index < this.visibleCount;
            }
        }"
        @resize.window="isMobile = window.innerWidth < 768"
        class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf
        <input type="hidden" name="session_id" value="{{ $session->id }}">

        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/70 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-800/30 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="text-[13px] font-black uppercase tracking-widest text-slate-900 dark:text-white">Saldo Outlet & Bahan Gudang</h2><p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">Setiap bahan hanya menggunakan satu input dan satuan tetap.</p></div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-stock-filter="all" @click="filter = 'all'; visibleCount = 10" :class="filter === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-white text-slate-500 dark:bg-slate-900 dark:text-slate-300'" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[10px] font-bold dark:border-slate-700">Semua <span>{{ $ingredients->count() }}</span></button>
                <button type="button" data-stock-filter="adjusted" @click="filter = 'adjusted'" :class="filter === 'adjusted' ? 'bg-blue-600 text-white' : 'bg-white text-slate-500 dark:bg-slate-900 dark:text-slate-300'" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[10px] font-bold dark:border-slate-700">Disesuaikan <span x-text="adjustedCount()"></span></button>
                <button type="button" data-stock-filter="issues" @click="filter = 'issues'" :class="filter === 'issues' ? 'bg-rose-600 text-white' : 'bg-white text-slate-500 dark:bg-slate-900 dark:text-slate-300'" class="rounded-lg border border-slate-200 px-3 py-1.5 text-[10px] font-bold dark:border-slate-700">Bermasalah <span x-text="issueCount()"></span></button>
            </div>
        </div>

        @if($ingredients->isEmpty())
            <div class="flex flex-col items-center justify-center px-4 py-12 text-center"><i class="fa-regular fa-folder-open text-3xl text-slate-300"></i><h3 class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-200">Bahan Tidak Ditemukan</h3><p class="mt-1 text-xs text-slate-500">Ubah pencarian atau kategori yang digunakan.</p></div>
        @else
            <div class="hidden grid-cols-[minmax(220px,1.35fr)_130px_minmax(220px,1fr)_170px] border-b border-slate-200 bg-white px-5 py-2.5 text-[9px] font-black uppercase tracking-widest text-slate-400 md:grid dark:border-slate-800 dark:bg-slate-900">
                <span>Bahan & Saldo</span><span>Sisa Kemarin</span><span>Stok Awal Hari Ini</span><span class="text-right">Status</span>
            </div>
            <div class="stock-responsive-list md:max-h-[65vh] md:overflow-y-auto" data-responsive-stock-list>
                @foreach($ingredients as $ingredient)
                    @include('admin.daily_stocks.partials.transfer-ingredient', ['ingredient' => $ingredient])
                @endforeach
            </div>
            <div x-show="isMobile && filter === 'all' && visibleCount < total" x-cloak class="border-t border-slate-100 bg-slate-50/70 p-3 text-center dark:border-slate-800 dark:bg-slate-950/40">
                <button type="button" @click="visibleCount = Math.min(total, visibleCount + 10)" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 text-xs font-bold text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Tampilkan 10 berikutnya <i class="fa-solid fa-chevron-down text-[9px]"></i></button>
            </div>
        @endif

        <div class="sticky bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(.75rem+env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(15,23,42,0.06)] backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/95 md:px-6 md:py-3">
            <div class="mx-auto flex w-full max-w-screen-2xl flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-h-4 text-[11px] font-semibold text-slate-500 dark:text-slate-400"><span x-show="dirtyCount() === 0" x-cloak>Belum ada input yang diubah.</span><span x-show="dirtyCount() > 0" x-cloak><strong class="text-blue-600" x-text="dirtyCount()"></strong> bahan diubah.</span><span x-show="issueCount() > 0" x-cloak class="ml-2 text-rose-600"><strong x-text="issueCount()"></strong> perlu diperiksa.</span></div>
                <div class="grid grid-cols-[96px_minmax(0,1fr)] gap-2 sm:flex sm:items-center">
                    <a href="{{ route('admin.daily-stocks.index', ['date' => $session->session_date->toDateString(), 'cashier_id' => $session->cashier_id]) }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 text-xs font-bold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 active:scale-[.98] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"><i class="fa-solid fa-xmark text-[11px] text-slate-400"></i>Batal</a>
                    <button type="submit" class="inline-flex h-11 min-w-0 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-xs font-extrabold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-700 active:scale-[.98] sm:min-w-52"><i class="fa-solid fa-check text-[11px]"></i>Simpan Saldo Awal</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
