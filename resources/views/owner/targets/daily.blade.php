@extends('layouts.app')

@section('content')
<div class="space-y-6 pb-10">
    <!-- Header & Date Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="mb-3 flex items-center gap-2 overflow-x-auto pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400 sm:text-[11px]">
                <a href="{{ route('owner.panel') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Beranda</a>
                <span class="text-slate-300 dark:text-slate-600">/</span>
                <span class="text-blue-600 dark:text-blue-400">Target Harian</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">Target Harian</h1>
            <p class="mt-2 text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400 max-w-3xl">Pantau performa dan tetapkan target pencapaian harian.</p>
        </div>
        
        <div class="flex items-center gap-1.5 sm:gap-2" x-data x-ref="filterFormWrapper">
            <a href="{{ route('owner.targets.index', ['date' => $selectedDate->copy()->subDay()->toDateString()]) }}" class="p-2 sm:px-3 sm:py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm focus:ring-2 focus:ring-blue-500 outline-none flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
            </a>
            
            <form method="GET" action="{{ route('owner.targets.index') }}" class="flex items-center m-0 w-full sm:w-auto">
                <input type="date" name="date" value="{{ $selectedDate->toDateString() }}"
                       @change="$el.form.submit()"
                       class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm font-semibold text-slate-700 dark:text-slate-200 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all cursor-pointer">
            </form>

            <a href="{{ route('owner.targets.index', ['date' => $selectedDate->copy()->addDay()->toDateString()]) }}" class="p-2 sm:px-3 sm:py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shadow-sm focus:ring-2 focus:ring-blue-500 outline-none flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
            </a>
        </div>
    </div>

    <!-- Alerts -->
    @if($errors->any())
        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800/50 dark:bg-red-900/20 dark:text-red-300 shadow-sm">
            <div class="h-8 w-8 rounded-full bg-red-100 dark:bg-red-800/50 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </div>
            <div>
                <ul class="list-disc list-inside text-sm font-medium space-y-1 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Calculation for Progress -->
    @php
        $revenuePercentage = $targetRevenue > 0 ? min(100, round(($actualRevenue / $targetRevenue) * 100)) : 0;
        $transactionPercentage = $targetTransactions > 0 ? min(100, round(($actualTransactions / $targetTransactions) * 100)) : 0;
    @endphp

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Card Omzet -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-5 dark:opacity-10 pointer-events-none group-hover:scale-110 transition-transform duration-500">
                <svg class="w-24 h-24 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            
            <div class="flex justify-between items-start mb-5 relative">
                <div>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Omzet Penjualan</p>
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Rp {{ number_format($actualRevenue, 0, ',', '.') }}</h3>
                        <span class="text-sm font-semibold text-slate-400">/ Rp {{ number_format($targetRevenue, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 border border-blue-100 dark:border-blue-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>

            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 mb-3 overflow-hidden relative">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-1000 ease-out" style="width: {{ $revenuePercentage }}%"></div>
            </div>
            
            <div class="flex justify-between items-center text-sm">
                <span class="font-bold text-slate-700 dark:text-slate-300">{{ $revenuePercentage }}% <span class="font-medium text-slate-500">Tercapai</span></span>
                @if($revenueGap >= 0)
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-md">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        Surplus Rp {{ number_format($revenueGap, 0, ',', '.') }}
                    </span>
                @else
                    <span class="text-rose-600 dark:text-rose-400 font-bold flex items-center gap-1 bg-rose-50 dark:bg-rose-500/10 px-2 py-0.5 rounded-md">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        Kurang Rp {{ number_format(abs($revenueGap), 0, ',', '.') }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Card Transaksi -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-6 opacity-5 dark:opacity-10 pointer-events-none group-hover:scale-110 transition-transform duration-500">
                <svg class="w-24 h-24 text-sky-600" fill="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>

            <div class="flex justify-between items-start mb-5 relative">
                <div>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1.5">Jumlah Transaksi</p>
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <h3 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">{{ number_format($actualTransactions, 0, ',', '.') }}</h3>
                        <span class="text-sm font-semibold text-slate-400">/ {{ number_format($targetTransactions, 0, ',', '.') }} Trx</span>
                    </div>
                </div>
                <div class="h-12 w-12 rounded-2xl bg-sky-50 dark:bg-sky-500/10 flex items-center justify-center text-sky-600 dark:text-sky-400 shrink-0 border border-sky-100 dark:border-sky-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                </div>
            </div>

            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 mb-3 overflow-hidden relative">
                <div class="bg-gradient-to-r from-sky-500 to-sky-600 h-3 rounded-full transition-all duration-1000 ease-out" style="width: {{ $transactionPercentage }}%"></div>
            </div>
            
            <div class="flex justify-between items-center text-sm">
                <span class="font-bold text-slate-700 dark:text-slate-300">{{ $transactionPercentage }}% <span class="font-medium text-slate-500">Tercapai</span></span>
                @if($transactionGap >= 0)
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded-md">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        Surplus {{ number_format($transactionGap, 0, ',', '.') }} Trx
                    </span>
                @else
                    <span class="text-rose-600 dark:text-rose-400 font-bold flex items-center gap-1 bg-rose-50 dark:bg-rose-500/10 px-2 py-0.5 rounded-md">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        Kurang {{ number_format(abs($transactionGap), 0, ',', '.') }} Trx
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Form Setting Target -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden mt-6">
        <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/20">
            <div class="flex items-center gap-5">
                <div class="h-10 w-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 border border-indigo-100 dark:border-indigo-500/20 shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </div>
                <div>
                    <h2 class="text-[15px] sm:text-base font-bold text-slate-800 dark:text-white">Pengaturan Target</h2>
                    @if($target)
                        <p class="text-[11px] sm:text-xs font-semibold text-slate-500 mt-0.5">Berlaku sejak {{ $target->target_date->format('d/m/Y') }}</p>
                    @else
                        <p class="text-[11px] sm:text-xs font-semibold text-slate-500 mt-0.5">Target belum ditetapkan untuk tanggal ini.</p>
                    @endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('owner.targets.store') }}" class="p-5 sm:p-6 space-y-6">
            @csrf
            <input type="hidden" name="target_date" value="{{ $selectedDate->toDateString() }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                <div>
                    <label class="block text-[13px] font-bold text-slate-700 dark:text-slate-300 mb-2">Target Omzet Harian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-slate-500 dark:text-slate-400 font-bold">Rp</span>
                        </div>
                        <input type="number" min="0" step="1000" name="target_revenue"
                               value="{{ old('target_revenue', (int) $targetRevenue) }}"
                               data-clear-zero-input
                               class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-[13px] font-bold text-slate-700 dark:text-slate-300 mb-2">Target Jumlah Transaksi</label>
                    <div class="relative">
                        <input type="number" min="0" name="target_transactions"
                               value="{{ old('target_transactions', $targetTransactions) }}"
                               data-clear-zero-input
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all shadow-sm">
                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                            <span class="text-slate-500 dark:text-slate-400 font-bold text-xs uppercase tracking-wider">Trx</span>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[13px] font-bold text-slate-700 dark:text-slate-300 mb-2">Catatan Target <span class="text-slate-400 font-medium">(Opsional)</span></label>
                <textarea name="notes" rows="2" placeholder="Tuliskan strategi untuk mencapai target hari ini..."
                          class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all resize-none shadow-sm">{{ old('notes', $target->notes ?? '') }}</textarea>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 w-full sm:w-auto p-3 sm:p-0 bg-slate-50 sm:bg-transparent dark:bg-slate-800/50 sm:dark:bg-transparent rounded-xl border border-slate-200 dark:border-slate-700 sm:border-0">
                    <div class="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0 sm:hidden lg:flex">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-[11px] sm:text-xs font-medium text-slate-500 dark:text-slate-400 leading-relaxed text-left max-w-md">
                        Target yang disimpan akan menjadi target <strong class="text-slate-700 dark:text-slate-300">default</strong> untuk hari berikutnya sampai Anda mengubahnya.
                    </p>
                </div>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-[13px] sm:text-sm font-bold tracking-wide transition-all shadow-sm hover:shadow-md flex items-center justify-center gap-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-clear-zero-input]').forEach((input) => {
        input.addEventListener('focus', () => {
            if (input.value === '0') {
                input.value = '';
            }
        });

        input.addEventListener('input', () => {
            input.value = input.value.replace(/^0+(?=\d)/, '');
        });

        input.addEventListener('blur', () => {
            if (input.value === '') {
                input.value = '0';
            }
        });
    });
</script>
@endpush
