@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Stok Harian Kasir')

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex-1 w-full overflow-hidden">
            
            {{-- BREADCRUMB (Anti Pecah di Mobile) --}}
            <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto hide-scrollbar pb-1">
                <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Beranda
                </a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                    Kasir & Stok
                </span>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                    Stok Harian Kasir
                </span>
            </nav>

            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white mb-2">
                Stok Harian Kasir
            </h1>

            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Kelola sesi harian kasir: buka sesi, transfer stok awal dari gudang utama, lalu tutup sesi dengan menginput sisa bahan.
            </p>
        </div>
    </div>

    {{-- ================= CONTROL BAR (FILTER & BUKA SESI) ================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-5">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-5">
            
            {{-- Filter Form --}}
            <form method="GET" action="{{ route('admin.daily-stocks.index') }}" class="flex-1 w-full flex flex-col sm:flex-row gap-3 items-end">
                <div class="w-full sm:w-auto flex-1 max-w-[200px]">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Tanggal Sesi</label>
                    <input type="date"
                           name="date"
                           value="{{ $selectedDate->toDateString() }}"
                           class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-[13px] font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all dark:[color-scheme:dark]">
                </div>
                
                <div class="w-full sm:w-auto flex-1 max-w-[250px]">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Pilih Kasir</label>
                    <select name="cashier_id" class="w-full h-10 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-[13px] font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                        @forelse($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" {{ (int) $selectedCashierId === (int) $cashier->id ? 'selected' : '' }}>
                                {{ $cashier->name }}
                            </option>
                        @empty
                            <option value="">Belum ada user kasir</option>
                        @endforelse
                    </select>
                </div>

                <div class="w-full sm:w-auto shrink-0">
                    <button type="submit" class="w-full sm:w-auto h-10 px-6 rounded-xl bg-slate-900 text-white text-[13px] font-bold hover:bg-slate-800 transition-all shadow-sm dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                        Tampilkan
                    </button>
                </div>
            </form>

            {{-- Action Buka Sesi --}}
            @if($selectedCashierId > 0 && !$session)
                <div class="w-full md:w-auto shrink-0 pt-4 md:pt-0 border-t md:border-t-0 md:border-l border-slate-100 dark:border-slate-800 md:pl-5">
                    <form method="POST" action="{{ route('admin.daily-stocks.open') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                        <input type="hidden" name="cashier_id" value="{{ $selectedCashierId }}">
                        <button type="submit" class="w-full sm:w-auto h-10 px-6 inline-flex items-center justify-center gap-2 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400 text-[13px] font-bold hover:bg-blue-100 dark:hover:bg-blue-500/20 transition-all">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                            Buka Sesi Harian
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>

    {{-- ================= KONDISI EMPTY / ERROR ================= --}}
    @if($selectedCashierId <= 0)
        <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm font-medium text-amber-800 dark:border-amber-900/50 dark:bg-amber-900/20 dark:text-amber-300 shadow-sm">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            User dengan role kasir belum tersedia. Silakan tambahkan user kasir terlebih dahulu.
        </div>
    @elseif(!$session)
        <div class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-16 text-center shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-4 border border-slate-100 dark:border-slate-700">
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-sm font-medium max-w-md">
                Belum ada sesi stok harian untuk tanggal dan kasir ini. Silakan klik tombol <strong class="text-blue-600 dark:text-blue-400">Buka Sesi Harian</strong> di atas untuk memulai operasional.
            </p>
        </div>
    @else

        {{-- ================= SUMMARY CARDS (Jika Sesi Ada) ================= --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Bahan</p>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums" id="summary-items">{{ $summary['items_count'] }}</span>
                </div>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-blue-500/30"></div>
            </div>

            <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Dibawa</p>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums" id="summary-opening">{{ number_format($summary['total_opening'], 2, ',', '.') }}</span>
                </div>
                <p class="text-[10px] font-semibold text-slate-400 mt-1 uppercase tracking-wider">Satuan Dasar</p>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-emerald-500/30"></div>
            </div>

            <div class="relative overflow-hidden p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Sisa</p>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums" id="summary-remaining">{{ number_format($summary['total_remaining'], 2, ',', '.') }}</span>
                </div>
                <p class="text-[10px] font-semibold text-slate-400 mt-1 uppercase tracking-wider">Satuan Dasar</p>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-amber-500/30"></div>
            </div>

            <div class="relative overflow-hidden p-5 bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/50 rounded-2xl shadow-sm">
                <p class="text-[10px] font-bold text-rose-500 dark:text-rose-400 uppercase tracking-widest mb-1.5">Total Terpakai</p>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-rose-600 dark:text-rose-400 tabular-nums" id="summary-used">{{ number_format($summary['total_used'], 2, ',', '.') }}</span>
                </div>
                <p class="text-[10px] font-semibold text-rose-400/80 mt-1 uppercase tracking-wider">Satuan Dasar</p>
                <div class="absolute bottom-0 left-0 h-1 w-full bg-rose-500/50"></div>
            </div>
        </div>

        {{-- ================= MAIN CONTENT AREA ================= --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            
            {{-- Header Sesi --}}
            <div class="p-5 md:p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/30 dark:bg-slate-800/20">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        Sesi #{{ $session->id }} 
                        <span class="text-slate-300 dark:text-slate-600">|</span> 
                        {{ $session->cashier->name ?? '-' }}
                    </h2>
                    <p class="text-xs font-medium text-slate-500 mt-1">
                        {{ $session->session_date->translatedFormat('d F Y') }}
                    </p>
                </div>
                <div>
                    @if($session->status === 'closed')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400 uppercase tracking-widest shadow-sm">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Sesi Ditutup
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 uppercase tracking-widest shadow-sm">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Sesi Aktif
                        </span>
                    @endif
                </div>
            </div>

            {{-- Form Transfer (Tambah Bahan) Jika Sesi Open --}}
            @if($session->status === 'open')
                <div class="p-5 md:p-6 border-b border-slate-100 dark:border-slate-800">
                    <form method="POST" action="{{ route('admin.daily-stocks.transfer') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        @csrf
                        <input type="hidden" name="session_id" value="{{ $session->id }}">

                        <div class="md:col-span-5">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Bahan (Dari Gudang)</label>
                            <select name="ingredient_id" required class="w-full h-[42px] px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-[13px] font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                                <option value="">-- Pilih bahan --</option>
                                @foreach($ingredients as $ingredient)
                                    <option value="{{ $ingredient->id }}">
                                        {{ $ingredient->name }} (Stok: {{ number_format((float) $ingredient->stock, 2, ',', '.') }} {{ strtolower($ingredient->base_unit ?? $ingredient->display_unit) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Jml Dibawa</label>
                            <input type="number" name="quantity" step="0.01" min="0.01" required placeholder="0.00" class="w-full h-[42px] px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-[13px] font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all tabular-nums">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Catatan (Opsional)</label>
                            <input type="text" name="note" maxlength="255" placeholder="Keterangan..." class="w-full h-[42px] px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-[13px] font-semibold text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                        </div>

                        <div class="md:col-span-2">
                            <button type="submit" class="w-full h-[42px] flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 text-white text-[13px] font-bold hover:bg-emerald-700 transition-all shadow-sm shadow-emerald-500/20">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                                Tambah
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Reopen Sesi Form --}}
            @if($session->status !== 'open')
                <div class="p-5 md:p-6 border-b border-slate-100 dark:border-slate-800 bg-amber-50/50 dark:bg-amber-900/10">
                    <form method="POST" action="{{ route('admin.daily-stocks.reopen') }}" class="flex flex-col sm:flex-row items-end gap-3 w-full max-w-xl">
                        @csrf
                        <input type="hidden" name="session_id" value="{{ $session->id }}">
                        <div class="flex-1 w-full">
                            <label class="block text-[10px] font-bold text-amber-600 dark:text-amber-500 uppercase tracking-widest mb-1.5">Alasan Reopen Sesi</label>
                            <input type="text" name="notes" maxlength="255" placeholder="Contoh: Koreksi input sisa bahan..." required
                                   class="w-full h-10 px-3 rounded-xl border border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-800 text-[13px] font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 outline-none transition-all">
                        </div>
                        <button type="submit" class="w-full sm:w-auto h-10 px-6 rounded-xl bg-amber-500 text-white text-[13px] font-bold hover:bg-amber-600 transition-all shadow-sm shadow-amber-500/20 shrink-0">
                            Buka Kembali Sesi
                        </button>
                    </form>
                </div>
            @endif

            {{-- Tabel Daftar Bahan Dibawa --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="hidden md:table-header-group">
                        <tr class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30">
                            <th class="px-6 py-4 whitespace-nowrap">Bahan Baku</th>
                            <th class="px-6 py-4 text-right whitespace-nowrap">Dibawa</th>
                            <th class="px-6 py-4 text-right whitespace-nowrap">Sisa</th>
                            <th class="px-6 py-4 text-right whitespace-nowrap text-blue-600 dark:text-blue-400">Terpakai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        @forelse($session->items as $item)
                            {{-- ROW DESKTOP --}}
                            <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-900 dark:text-white text-[14px]">{{ $item->ingredient->name }}</p>
                                    <p class="text-[11px] font-semibold text-slate-400 mt-0.5 uppercase tracking-wider">{{ $item->display_unit }}</p>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                    <span class="text-[13px] font-bold text-slate-700 dark:text-slate-300 tabular-nums">
                                        {{ rtrim(rtrim(number_format((float) $item->opening_display, 2, '.', ''), '0'), '.') }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 ml-1">{{ $item->display_unit }}</span>
                                    @if($item->opening_pack !== null)
                                        <div class="text-[10px] font-semibold text-slate-400 mt-0.5">({{ rtrim(rtrim(number_format((float) $item->opening_pack, 2, '.', ''), '0'), '.') }} pack)</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                    <span class="text-[13px] font-bold text-slate-700 dark:text-slate-300 tabular-nums">
                                        {{ rtrim(rtrim(number_format((float) $item->remaining_display, 2, '.', ''), '0'), '.') }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 ml-1">{{ $item->display_unit }}</span>
                                    @if($item->remaining_pack !== null)
                                        <div class="text-[10px] font-semibold text-slate-400 mt-0.5">({{ rtrim(rtrim(number_format((float) $item->remaining_pack, 2, '.', ''), '0'), '.') }} pack)</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap align-middle">
                                    <span class="text-[14px] font-black text-rose-600 dark:text-rose-400 tabular-nums">
                                        {{ rtrim(rtrim(number_format((float) $item->used_display, 2, '.', ''), '0'), '.') }}
                                    </span>
                                    <span class="text-[10px] font-bold text-rose-400 ml-1">{{ $item->display_unit }}</span>
                                </td>
                            </tr>

                            {{-- CARD MOBILE --}}
                            <tr class="md:hidden border-b border-slate-100 dark:border-slate-800/50 last:border-0">
                                <td class="p-0">
                                    <div class="p-4 sm:p-5">
                                        <div class="mb-3">
                                            <p class="font-bold text-slate-900 dark:text-white text-[15px] leading-tight">{{ $item->ingredient->name }}</p>
                                        </div>
                                        <div class="grid grid-cols-3 gap-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl p-3 border border-slate-100 dark:border-slate-700/50 text-center divide-x divide-slate-200 dark:divide-slate-700">
                                            <div>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Dibawa</p>
                                                <p class="font-bold text-slate-700 dark:text-slate-300 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) $item->opening_display, 2, '.', ''), '0'), '.') }} <span class="text-[9px] font-normal">{{ $item->display_unit }}</span></p>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Sisa</p>
                                                <p class="font-bold text-slate-700 dark:text-slate-300 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) $item->remaining_display, 2, '.', ''), '0'), '.') }} <span class="text-[9px] font-normal">{{ $item->display_unit }}</span></p>
                                            </div>
                                            <div>
                                                <p class="text-[9px] font-bold text-rose-500 uppercase tracking-widest mb-1">Terpakai</p>
                                                <p class="font-black text-rose-600 dark:text-rose-400 text-xs tabular-nums">{{ rtrim(rtrim(number_format((float) $item->used_display, 2, '.', ''), '0'), '.') }} <span class="text-[9px] font-normal">{{ $item->display_unit }}</span></p>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400 text-sm font-medium">
                                    Belum ada bahan yang ditransfer ke sesi ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ================= FORM PENUTUPAN SESI ================= --}}
            @if($session->status === 'open' && $session->items->isNotEmpty())
                <div class="border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900">
                    <div class="px-5 py-4 border-b border-slate-200/60 dark:border-slate-800">
                        <h3 class="text-[13px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-widest">Input Sisa Penutupan Sesi</h3>
                        <p class="text-[11px] text-slate-500 mt-1">Masukkan jumlah sisa aktual untuk setiap bahan sebelum menutup sesi.</p>
                    </div>
                    
                    <form method="POST" action="{{ route('admin.daily-stocks.close') }}" class="p-5 md:p-6 space-y-6" id="close-session-form">
                        @csrf
                        <input type="hidden" name="session_id" value="{{ $session->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($session->items as $item)
                                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/10 transition-all">
                                    <div class="mb-3">
                                        <p class="text-[13px] font-bold text-slate-900 dark:text-white leading-tight">{{ $item->ingredient->name }}</p>
                                        <p class="text-[10px] font-semibold text-slate-500 mt-1">Dibawa: <span class="text-slate-700 dark:text-slate-300">{{ rtrim(rtrim(number_format((float) $item->opening_display, 2, '.', ''), '0'), '.') }} {{ $item->display_unit }}</span></p>
                                    </div>
                                    
                                    <div class="relative">
                                        <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sisa Fisik</label>
                                        <div class="relative flex items-center">
                                            <input
                                                type="number"
                                                name="remaining[{{ $item->ingredient_id }}]"
                                                min="0"
                                                max="{{ $item->opening_display }}"
                                                step="0.01"
                                                value="{{ rtrim(rtrim(number_format((float) $item->remaining_display, 2, '.', ''), '0'), '.') }}"
                                                data-opening-base="{{ $item->opening_qty }}"
                                                data-display-unit="{{ $item->display_unit }}"
                                                data-pack-size="{{ $item->pack_size }}"
                                                class="daily-remaining-input w-full h-[42px] px-3 pr-12 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-sm font-semibold text-slate-900 dark:text-white outline-none tabular-nums focus:border-blue-500 focus:bg-white dark:focus:bg-slate-800 transition-all"
                                            >
                                            <span class="absolute right-3 text-[10px] font-bold uppercase tracking-widest text-slate-400 pointer-events-none">{{ $item->display_unit }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="max-w-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-sm focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/10 transition-all">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Catatan Penutupan Sesi (Opsional)</label>
                            <input type="text" name="notes" maxlength="255" placeholder="Keterangan hasil opname/sisa..." class="w-full h-[42px] px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-900 dark:text-white outline-none focus:border-blue-500 focus:bg-white dark:focus:bg-slate-800 transition-all">
                        </div>

                        <div class="pt-4 border-t border-slate-200/60 dark:border-slate-700/50 flex justify-end">
                            <button type="submit" class="w-full sm:w-auto px-8 py-3 rounded-xl bg-slate-900 text-white text-[14px] font-bold hover:bg-slate-800 transition-all shadow-md dark:bg-blue-600 dark:hover:bg-blue-700 flex items-center justify-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                Konfirmasi & Tutup Sesi
                            </button>
                        </div>
                    </form>
                </div>
            @endif

        </div>
    @endif
</div>

<style>
/* CSS Helper untuk menyembunyikan scrollbar di menu navigasi */
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Live calculation untuk "Total Sisa" & "Total Terpakai" saat form penutupan diisi
    const inputs = document.querySelectorAll('.daily-remaining-input');
    if (!inputs.length) return;

    const openingEl = document.getElementById('summary-opening');
    const remainingEl = document.getElementById('summary-remaining');
    const usedEl = document.getElementById('summary-used');

    const toNumber = (v) => {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : 0;
    };

    const toBase = (displayQty, unit, packSize) => {
        if (unit === 'kg' || unit === 'l') return displayQty * 1000;
        if (unit === 'pcs') return displayQty * Math.max(1, packSize);
        return displayQty;
    };

    const fmt = (n) => {
        return n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const recompute = () => {
        let totalOpening = 0;
        let totalRemaining = 0;

        inputs.forEach((input) => {
            const openingBase = toNumber(input.dataset.openingBase);
            const unit = (input.dataset.displayUnit || '').toLowerCase();
            const packSize = parseInt(input.dataset.packSize || '1', 10);
            
            // Ambil input value, pastikan tidak negatif
            const remainingDisplay = Math.max(0, toNumber(input.value));
            const remainingBase = toBase(remainingDisplay, unit, packSize);

            totalOpening += openingBase;
            totalRemaining += remainingBase;
        });

        const totalUsed = Math.max(0, totalOpening - totalRemaining);

        if (openingEl) openingEl.textContent = fmt(totalOpening);
        if (remainingEl) remainingEl.textContent = fmt(totalRemaining);
        if (usedEl) usedEl.textContent = fmt(totalUsed);
    };

    // Dengarkan event input agar setiap ketikan kasir langsung mengubah kartu summary di atas
    inputs.forEach((input) => input.addEventListener('input', recompute));
    recompute(); // Hitung awal
});
</script>
@endsection