@php
    $routePrefix = $routePrefix ?? 'admin.reports';
    $canInput = $canInput ?? false;

    $hasActiveFilters = request()->filled('date_from') || request()->filled('date_to') || request('type', 'daily') !== 'daily';
@endphp

<div class="space-y-8 max-w-full overflow-x-hidden">
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-2">
            <span class="text-slate-600 dark:text-slate-300">Keuangan</span>
            <span class="text-slate-200 dark:text-slate-700">/</span>
            <span class="text-slate-600 dark:text-slate-300">Laporan Pengeluaran</span>
        </nav>

        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">
            Laporan Pengeluaran
        </h1>

        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed mb-5">
            Pemasukan dihitung otomatis dari transaksi menu (pemasukan kotor). Halaman ini fokus memantau pengeluaran operasional harian, mingguan, dan bulanan.
        </p>

        <div class="inline-flex items-center gap-2.5 px-3 py-1.5 bg-blue-50/50 dark:bg-blue-500/10 border border-blue-100 dark:border-blue-500/20 rounded-lg shadow-sm">
            <span class="text-[11px] sm:text-xs font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wide">
                Periode Data:
                <span class="ml-1 text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $dateFrom->format('d M Y') }}</span>
                @if(!$dateFrom->isSameDay($dateTo))
                    <span class="mx-0.5 text-slate-400 normal-case">-</span>
                    <span class="text-slate-700 dark:text-slate-200 normal-case tracking-normal">{{ $dateTo->format('d M Y') }}</span>
                @endif
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route($routePrefix.'.cashflow') }}" id="filter-form" class="relative group z-10 mb-6">
        <div class="flex flex-col gap-3">
            <input type="hidden" name="type" id="hidden_type" value="{{ $type }}">
            <input type="hidden" name="date_from" id="hidden_date_from" value="{{ $dateFrom->toDateString() }}">
            <input type="hidden" name="date_to" id="hidden_date_to" value="{{ $dateTo->toDateString() }}">

            <div class="flex flex-col lg:flex-row gap-3 w-full">
                <div class="w-full lg:w-auto flex bg-slate-100 dark:bg-slate-800/50 p-1.5 rounded-xl border border-slate-200/50 dark:border-slate-700/50 shrink-0 overflow-x-auto no-scrollbar justify-start sm:justify-center">
                    <button type="button" onclick="changeType('daily')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 {{ $type === 'daily' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Harian</button>
                    <button type="button" onclick="changeType('weekly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 {{ $type === 'weekly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Mingguan</button>
                    <button type="button" onclick="changeType('monthly')" class="flex-1 min-w-[80px] lg:px-6 flex items-center justify-center px-3 py-2 text-[13px] font-bold rounded-lg transition-all duration-200 {{ $type === 'monthly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200' }}">Bulanan</button>
                </div>

                <div class="flex-1 flex items-center bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-sm p-1 w-full min-w-0">
                    <a href="{{ route($routePrefix.'.cashflow', array_merge(request()->except(['page','date_from','date_to']), ['type' => $type, 'date_from' => $prevFrom, 'date_to' => $prevTo])) }}" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-blue-600 dark:hover:text-blue-400 transition-all shrink-0">&#8249;</a>

                    <div class="flex-1 flex px-3">
                        <input type="{{ $inputType }}" value="{{ $inputValue }}" onchange="updateDateRange(this, '{{ $type }}')" class="w-full text-center bg-transparent border-none text-[13px] font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-0 cursor-pointer outline-none dark:[color-scheme:dark]">
                    </div>

                    @if($isFuture)
                        <div class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-300 dark:text-slate-700 cursor-not-allowed shrink-0">&#8250;</div>
                    @else
                        <a href="{{ route($routePrefix.'.cashflow', array_merge(request()->except(['page','date_from','date_to']), ['type' => $type, 'date_from' => $nextFrom, 'date_to' => $nextTo])) }}" class="w-10 h-10 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-blue-600 dark:hover:text-blue-400 transition-all shrink-0">&#8250;</a>
                    @endif
                </div>

                <div class="flex gap-2 shrink-0">
                    @if($canInput)
                        <a href="{{ route('admin.reports.cashflow.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-emerald-600 text-white text-sm font-bold rounded-xl hover:bg-emerald-700 transition-all shadow-sm">
                            + Input
                        </a>
                    @endif
                    <a href="{{ route($routePrefix.'.cashflow.export', request()->query()) }}" class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-sm font-bold rounded-xl hover:bg-slate-800 dark:hover:bg-white transition-all shadow-sm">
                        Export
                    </a>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-3 w-full">

                @if($hasActiveFilters)
                    <a href="{{ route($routePrefix.'.cashflow') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm text-slate-500 hover:text-red-500 hover:border-red-200 hover:bg-red-50 dark:hover:bg-red-500/10">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[2rem] text-center">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Omzet</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white">Rp {{ number_format($salesRevenue, 0, ',', '.') }}</p>
        </div>

        <div class="p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[2rem] text-center">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Jumlah Log</p>
            <p class="text-2xl font-black text-slate-900 dark:text-white">{{ number_format($expenseCount, 0, ',', '.') }}</p>
        </div>
        <div class="p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[2rem] text-center">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Total Pengeluaran</p>
            <p class="text-2xl font-black text-rose-600">Rp {{ number_format($expenseTotal, 0, ',', '.') }}</p>
        </div>
        <div class="p-6 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-[2rem] text-center">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Pemasukan Bersih</p>
            <p class="text-2xl font-black {{ $netCash >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">Rp {{ number_format($netCash, 0, ',', '.') }}</p>
        </div>
    </div>

    @forelse($groupedEntries as $date => $items)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                <h2 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</h2>
                <span class="px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-700 text-[10px] font-bold text-slate-500 dark:text-slate-300">{{ $items->count() }} data</span>
            </div>

            <div class="md:hidden p-4 space-y-3">
                @foreach($items as $entry)
                    <div class="rounded-xl border border-slate-100 dark:border-slate-800 p-4 space-y-2">
                        <div class="flex justify-between items-start gap-3">
                            <p class="font-bold text-slate-800 dark:text-white text-sm">{{ $entry->source ?: '-' }}</p>
                            <p class="font-black text-rose-600 text-sm">- Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}</p>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $entry->note ?: 'Tanpa catatan' }}</p>
                        <div class="text-[11px] text-slate-400">Input: {{ $entry->creator->name ?? '-' }} | {{ $entry->created_at?->format('H:i') }}</div>
                    </div>
                @endforeach
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm text-left">
                    <thead class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-3">Kategori</th>
                            <th class="px-6 py-3">Catatan</th>
                            <th class="px-6 py-3">Input Oleh</th>
                            <th class="px-6 py-3">Waktu</th>
                            <th class="px-6 py-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                        @foreach($items as $entry)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-800 dark:text-white">{{ $entry->source ?: '-' }}</td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $entry->note ?: '-' }}</td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">{{ $entry->creator->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-slate-400 dark:text-slate-500 text-xs">{{ $entry->created_at?->format('H:i') }}</td>
                                <td class="px-6 py-4 text-right font-black text-rose-600">- Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-16 text-center">
            <p class="text-slate-400 text-sm font-medium">Tidak ada data pengeluaran pada periode ini.</p>
        </div>
    @endforelse

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-slate-400 dark:text-slate-500 text-center sm:text-left">
            Halaman <span class="font-bold text-slate-700 dark:text-slate-200">{{ $entries->currentPage() }}</span>
            dari <span class="font-bold text-slate-700 dark:text-slate-200">{{ $entries->lastPage() }}</span>
            | Total <span class="font-bold text-slate-700 dark:text-slate-200">{{ $entries->total() }}</span> data
        </p>
        <div class="flex items-center justify-center gap-1.5">
            @if ($entries->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-300 dark:text-slate-700 cursor-not-allowed">&lt; Prev</span>
            @else
                <a href="{{ $entries->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">&lt; Prev</a>
            @endif
            @if ($entries->hasMorePages())
                <a href="{{ $entries->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 transition-colors">Next &gt;</a>
            @else
                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-300 dark:text-slate-700 cursor-not-allowed">Next &gt;</span>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function formatStr(d) {
    return d.getFullYear() + '-' + (d.getMonth() < 9 ? '0' : '') + (d.getMonth() + 1) + '-' + (d.getDate() < 10 ? '0' : '') + d.getDate();
}

function resolveWeekRange(dateObj) {
    let day = dateObj.getDay();
    let diff = day === 0 ? -6 : 1 - day;
    let start = new Date(dateObj);
    start.setDate(dateObj.getDate() + diff);
    let end = new Date(start);
    end.setDate(start.getDate() + 6);

    return { from: formatStr(start), to: formatStr(end) };
}

function changeType(newType) {
    document.getElementById('hidden_type').value = newType;
    let d = new Date();
    let from = '', to = '';

    if (newType === 'daily') {
        from = to = formatStr(d);
    } else if (newType === 'weekly') {
        const range = resolveWeekRange(d);
        from = range.from;
        to = range.to;
    } else {
        let start = new Date(d.getFullYear(), d.getMonth(), 1);
        let end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        from = formatStr(start);
        to = formatStr(end);
    }

    document.getElementById('hidden_date_from').value = from;
    document.getElementById('hidden_date_to').value = to;
    document.getElementById('filter-form').submit();
}

function updateDateRange(input, type) {
    let val = input.value;
    if (!val) return;

    let from = '', to = '';

    if (type === 'daily') {
        from = to = val;
    } else if (type === 'weekly') {
        const range = resolveWeekRange(new Date(val));
        from = range.from;
        to = range.to;
    } else {
        let parts = val.split('-');
        let start = new Date(parts[0], parts[1] - 1, 1);
        let end = new Date(parts[0], parts[1], 0);
        from = formatStr(start);
        to = formatStr(end);
    }

    document.getElementById('hidden_date_from').value = from;
    document.getElementById('hidden_date_to').value = to;
    document.getElementById('filter-form').submit();
}
</script>
@endpush
