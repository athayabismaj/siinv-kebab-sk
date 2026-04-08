@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')
@php
    $type = $type ?? 'daily';
@endphp

<div class="space-y-8">
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Analisis Penjualan</span>
        </nav>

        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white tracking-tight mb-3">
            Analisis Penjualan
        </h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
            Pantau performa omzet, jumlah transaksi, dan pergerakan menu terlaris secara mendetail.<br class="hidden sm:block mt-1">
            Gunakan tab di bawah untuk beralih antara laporan harian, mingguan, atau bulanan.
        </p>

        <div class="inline-flex items-center gap-2.5 px-3 py-1.5 bg-blue-50/50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 rounded-lg shadow-sm">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            <span class="text-[11px] sm:text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide">
                @if($type === 'daily')
                    Laporan Harian:
                    <span class="ml-1 text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $selectedDate->format('d M Y') }}</span>
                @elseif($type === 'weekly')
                    Laporan Mingguan:
                    <span class="ml-1 text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $selectedWeekStart->format('d M Y') }} - {{ $selectedWeekEnd->format('d M Y') }}</span>
                @else
                    Laporan Bulanan:
                    <span class="ml-1 text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $selectedMonth->translatedFormat('F Y') }}</span>
                @endif
            </span>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-3 w-full mb-6 relative z-10">
        <div class="w-full lg:w-auto flex bg-slate-100 dark:bg-slate-800/50 p-1.5 rounded-xl border border-slate-200/50 dark:border-slate-700/50 shrink-0">
            @foreach(['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan'] as $key => $label)
                <a href="{{ route('owner.reports.sales', ['type' => $key]) }}"
                    class="flex-1 lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 text-center
                    {{ $type === $key ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-3 flex-1">
            <div class="flex-1 flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all">
                <svg class="w-5 h-5 text-slate-400 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>

                @if($type === 'daily')
                    <form action="{{ route('owner.reports.sales') }}" method="GET" class="w-full flex">
                        <input type="hidden" name="type" value="daily">
                        <input type="date" name="date" value="{{ $selectedDate->toDateString() }}" onchange="this.form.submit()"
                            class="w-full bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none appearance-none dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer">
                    </form>
                @elseif($type === 'weekly')
                    <form action="{{ route('owner.reports.sales') }}" method="GET" class="w-full flex">
                        <input type="hidden" name="type" value="weekly">
                        <input type="date" name="week_date" value="{{ $selectedWeekStart->toDateString() }}" onchange="this.form.submit()"
                            class="w-full bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none appearance-none dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer">
                    </form>
                @else
                    <form action="{{ route('owner.reports.sales') }}" method="GET" class="w-full flex">
                        <input type="hidden" name="type" value="monthly">
                        <input type="month" name="month" value="{{ $selectedMonth->format('Y-m') }}" onchange="this.form.submit()"
                            class="w-full bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none appearance-none dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:cursor-pointer">
                    </form>
                @endif
            </div>

            <a href="{{ route('owner.reports.sales.export', ['type' => $type, 'date' => request('date'), 'week_date' => request('week_date'), 'month' => request('month')]) }}"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:bg-slate-800 dark:hover:bg-white active:scale-95 transition-all shadow-sm shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Export Data
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $stats = [
                ['label' => 'Omzet', 'value' => number_format($totalRevenue, 0, ',', '.'), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2', 'color' => 'blue', 'unit' => 'Rp'],
                ['label' => 'Jumlah Transaksi', 'value' => number_format($totalTransactions), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2', 'color' => 'emerald', 'unit' => 'kali'],
                ['label' => 'Rata-rata Transaksi', 'value' => number_format($avgTransaction, 0, ',', '.'), 'icon' => 'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6', 'color' => 'violet', 'unit' => 'Rp'],
                ['label' => 'Item Terjual', 'value' => number_format($totalMenuSold), 'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5', 'color' => 'orange', 'unit' => 'item'],
            ];
        @endphp

        @foreach($stats as $stat)
            <div class="relative p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl group hover:border-{{ $stat['color'] }}-500/30 hover:shadow-2xl hover:shadow-{{ $stat['color'] }}-500/10 transition-all duration-500 overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-{{ $stat['color'] }}-500/5 dark:bg-{{ $stat['color'] }}-400/5 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="relative flex flex-col items-center text-center">
                    <div class="w-12 h-12 rounded-2xl bg-{{ $stat['color'] }}-50 dark:bg-{{ $stat['color'] }}-900/20 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"></path></svg>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1">{{ $stat['label'] }}</p>
                    <div class="flex items-baseline gap-1">
                        @if($stat['unit'] === 'Rp') <span class="text-xs font-bold text-slate-400">Rp</span> @endif
                        <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ $stat['value'] }}</p>
                        @if($stat['unit'] !== 'Rp') <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-0.5">{{ $stat['unit'] }}</span> @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-5">
            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-5 flex items-center gap-2">
                    <span class="w-4 h-1 bg-blue-500 rounded-full"></span>
                    Menu Terlaris
                </p>
                @if($topMenu)
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 shrink-0 rounded-2xl bg-blue-50 dark:bg-slate-800 flex items-center justify-center ring-1 ring-blue-100 dark:ring-slate-700">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-base font-black text-slate-900 dark:text-white leading-tight truncate">{{ $topMenu->menu_name }}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold ring-1 ring-slate-200 dark:ring-slate-700">{{ number_format($topMenu->total_qty) }}x terjual</span>
                                <span class="text-xs font-black text-blue-600 dark:text-blue-400">Rp {{ number_format($topMenu->total_sales, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="py-8 text-center text-slate-400 italic text-sm">Belum ada data</div>
                @endif
            </div>

            <div class="p-6 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-5 flex items-center gap-2">
                    <span class="w-4 h-1 bg-emerald-500 rounded-full"></span>
                    Andil Penjualan
                </p>
                <div class="space-y-4">
                    @forelse($contributions->take(5) as $idx => $item)
                        @php
                            $colors = ['bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-orange-500', 'bg-rose-500'];
                            $color = $colors[$idx % count($colors)];
                        @endphp
                        <div>
                            <div class="flex justify-between items-baseline mb-1.5">
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200 truncate max-w-[60%]">{{ $item->menu_name }}</span>
                                <span class="text-xs font-black text-slate-500 dark:text-slate-400 shrink-0">{{ $item->contribution }}%</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full {{ $color }} rounded-full transition-all duration-500" style="width:{{ $item->contribution }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-slate-400 italic text-xs">Belum ada data kontribusi</div>
                    @endforelse
                    @if($contributions->count() > 5)
                        <p class="text-center text-[10px] text-slate-400 italic pt-1">+{{ $contributions->count() - 5 }} menu lainnya</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest">
                            @if($type === 'daily')
                                Breakdown Menu | {{ $selectedDate->format('d M Y') }}
                            @elseif($type === 'weekly')
                                Rincian Mingguan | {{ $selectedWeekStart->format('d M') }} - {{ $selectedWeekEnd->format('d M Y') }}
                            @else
                                Rincian Harian | {{ $selectedMonth->translatedFormat('F Y') }}
                            @endif
                        </p>
                        <p class="text-[10px] text-slate-400 mt-0.5" id="pagination-info"></p>
                    </div>
                    <div class="flex items-center gap-2" id="pagination-controls"></div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" id="breakdown-table">
                        <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
                            @if($type === 'daily')
                                <tr>
                                    <th class="px-6 py-3">Nama Item</th>
                                    <th class="px-6 py-3 text-center">Terjual</th>
                                    <th class="px-6 py-3 text-right">Subtotal</th>
                                </tr>
                            @else
                                <tr>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3 text-center">Transaksi</th>
                                    <th class="px-6 py-3 text-right">Omzet</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50" id="table-body">
                            @if($type === 'daily')
                                @forelse($contributions as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ $row->menu_name }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->total_qty) }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                            <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->total_sales, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-6 py-20 text-center text-slate-400 italic text-sm">Belum ada transaksi pada periode ini.</td></tr>
                                @endforelse
                            @elseif($type === 'weekly')
                                @forelse($weeklyBreakdown as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->trx_count) }} trx</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                            <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->revenue, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-6 py-20 text-center text-slate-400 italic text-sm">Tidak ada data untuk periode ini.</td></tr>
                                @endforelse
                            @else
                                @forelse($dailyBreakdown as $row)
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d M Y') }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg font-bold text-slate-500 dark:text-slate-400 text-xs">{{ number_format($row->trx_count) }} trx</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-slate-900 dark:text-white">
                                            <span class="text-[10px] font-medium text-slate-400 mr-0.5">Rp</span>{{ number_format($row->revenue, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-6 py-20 text-center text-slate-400 italic text-sm">Tidak ada data untuk periode ini.</td></tr>
                                @endforelse
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const PER_PAGE = 20;
    const body = document.getElementById('table-body');
    if (!body) return;

    const allRows = Array.from(body.querySelectorAll('tr'));
    if (allRows.length === 0) return;

    let currentPage = 1;
    const totalPages = Math.ceil(allRows.length / PER_PAGE);

    function render(page) {
        currentPage = page;
        const start = (page - 1) * PER_PAGE;
        const end = start + PER_PAGE;

        allRows.forEach((row, i) => {
            row.style.display = i >= start && i < end ? '' : 'none';
        });

        const info = document.getElementById('pagination-info');
        if (info) info.textContent = `Menampilkan ${start + 1}-${Math.min(end, allRows.length)} dari ${allRows.length} data`;

        renderControls();
    }

    function btn(label, page, disabled = false, active = false) {
        const el = document.createElement('button');
        el.textContent = label;
        el.disabled = disabled;
        el.className = [
            'px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200',
            active
                ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                : disabled
                    ? 'text-slate-300 dark:text-slate-700 cursor-not-allowed'
                    : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
        ].join(' ');
        if (!disabled) el.addEventListener('click', () => render(page));
        return el;
    }

    function renderControls() {
        const ctrl = document.getElementById('pagination-controls');
        if (!ctrl) return;
        ctrl.innerHTML = '';

        ctrl.appendChild(btn('<', currentPage - 1, currentPage === 1));

        const range = [];
        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || Math.abs(p - currentPage) <= 1) {
                range.push(p);
            } else if (range[range.length - 1] !== '...') {
                range.push('...');
            }
        }

        range.forEach(p => {
            if (p === '...') {
                const s = document.createElement('span');
                s.className = 'px-1 text-slate-300 dark:text-slate-700 text-xs';
                s.textContent = '...';
                ctrl.appendChild(s);
            } else {
                ctrl.appendChild(btn(p, p, false, p === currentPage));
            }
        });

        ctrl.appendChild(btn('>', currentPage + 1, currentPage === totalPages));
    }

    render(1);
})();
</script>
@endpush
