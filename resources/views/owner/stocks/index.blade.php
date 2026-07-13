@extends('layouts.app')

@section('title', 'Monitoring Stok')

@section('sidebar')
    @include('partials.sidebar_owner')
@endsection

@section('content')

@php
    function formatStockOwner($value)
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    function stockPercentOwner($stock, $minimum)
    {
        if ($minimum <= 0) return 100;
        return min(100, ($stock / ($minimum * 2)) * 100);
    }
@endphp

<div class="space-y-8">

    <x-page-header 
        title="Monitoring Stok" 
        subtitle="Pantau ketersediaan stok bahan secara langsung. Halaman ini hanya untuk pemantauan." 
        breadcrumb-parent="Owner" 
        breadcrumb-child="Monitoring Stok">
        
        {{-- STAT CHIPS inline --}}
        <div class="flex gap-4 flex-wrap shrink-0">
            <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                Total <span class="text-slate-800 dark:text-white">{{ $summary['total'] }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                Rendah <span class="text-amber-800 dark:text-amber-300">{{ $summary['low'] }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-widest text-red-600 dark:text-red-400">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                Habis <span class="text-red-800 dark:text-red-300">{{ $summary['out'] }}</span>
            </div>
        </div>
    </x-page-header>

    {{-- ================= ALERT ================= --}}
    @if ($summary['low'] > 0)
        <div class="px-4 py-3 text-sm rounded-xl
                    bg-amber-50 border border-amber-200 text-amber-700">
            {{ $summary['low'] }} bahan memiliki stok rendah / habis.
        </div>
    @endif


    {{-- ================= FILTER ================= --}}
    <form method="GET" x-data x-ref="filterForm" class="flex flex-col md:flex-row md:items-center gap-3">

        {{-- SEARCH --}}
        <div class="relative flex-1">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari bahan (Tekan Enter)..."
                @search="$refs.filterForm.submit()"
                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-sm focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500 transition-colors">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
            </svg>
        </div>

        {{-- CATEGORY --}}
        <select name="category" @change="$refs.filterForm.submit()" class="px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-sm focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500 transition-colors">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ (string) request('category') === (string) $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>

        {{-- FILTER HARGA --}}
        <select name="has_price" @change="$refs.filterForm.submit()" class="px-4 py-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-sm focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500 transition-colors">
            <option value="">Semua Harga</option>
            <option value="1" {{ ($hasPrice ?? '') === '1' ? 'selected' : '' }}>Ada Harga Jual</option>
            <option value="0" {{ ($hasPrice ?? '') === '0' ? 'selected' : '' }}>Belum Ada Harga</option>
        </select>

    </form>


    {{-- ================= MOBILE CARD ================= --}}
    <div class="space-y-5 md:hidden">

        @forelse ($ingredients as $ingredient)

            @php
                $stock = $ingredient->converted_stock;
                $minimum = $ingredient->converted_minimum_stock;
                $percent = stockPercentOwner($stock, $minimum);
            @endphp

            <div class="p-6 rounded-2xl
                        bg-white dark:bg-slate-900
                        border border-slate-200 dark:border-slate-800">

                {{-- HEADER --}}
                <div class="flex justify-between items-start mb-4">

                    <div>

                        <div class="text-base font-semibold text-slate-800 dark:text-white">
                            {{ $ingredient->name }}
                        </div>

                        <div class="text-xs text-slate-500 mt-1">
                            {{ $ingredient->category->name ?? '-' }}
                        </div>

                        @if ($ingredient->selling_price > 0)
                            @php
                                $priceUnit = match($ingredient->display_unit ?? '') {
                                    'kg'  => '/kg',
                                    'l'   => '/liter',
                                    'g'   => '/gram',
                                    'ml'  => '/ml',
                                    'pcs' => '/pack',
                                    default => '',
                                };
                            @endphp
                            <div class="mt-2 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($ingredient->selling_price, 0, ',', '.') }}<span class="text-[9px] font-normal text-emerald-500/80">{{ $priceUnit }}</span>
                            </div>
                        @endif

                    </div>

                    {{-- STATUS --}}
                    @if ($stock <= 0)
                        <span class="px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/30 dark:border-red-800/50 dark:text-red-400">Habis</span>
                    @elseif ($stock <= $minimum)
                        <span class="px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full bg-amber-50 border border-amber-200 text-amber-600 dark:bg-amber-900/30 dark:border-amber-800/50 dark:text-amber-400">Rendah</span>
                    @else
                        <span class="px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400">Aman</span>
                    @endif

                </div>


                {{-- STOCK --}}
                <div class="mb-4">

                    <div class="text-xl font-semibold text-slate-800 dark:text-white">

                        {{ formatStockOwner($stock) }}

                        <span class="text-sm text-slate-500">
                            {{ $ingredient->display_stock_unit }}
                        </span>

                    </div>

                    <div class="text-xs text-slate-400 mt-1">

                        Minimum:
                        {{ formatStockOwner($minimum) }}
                        {{ $ingredient->display_stock_unit }}

                    </div>

                </div>


                {{-- PROGRESS --}}
                <div class="w-full h-2 mb-4 rounded-full overflow-hidden
                            bg-slate-100 dark:bg-slate-800">

                    <div
                        class="h-full rounded-full
                        {{ $stock <= 0 ? 'bg-red-500' : ($stock <= $minimum ? 'bg-amber-500' : 'bg-emerald-500') }}"
                        style="width: {{ $percent }}%"
                    ></div>

                </div>


                {{-- UPDATE --}}
                <div class="text-xs text-slate-500">

                    Update:
                    {{ $ingredient->updated_at?->format('d M Y H:i') ?? '-' }}

                </div>

            </div>

        @empty

            <div class="py-12 text-center text-slate-500">
                Tidak ada data bahan
            </div>

        @endforelse

    </div>


    {{-- ================= DESKTOP TABLE ================= --}}
    <div class="hidden md:block rounded-3xl overflow-hidden
                bg-white dark:bg-slate-900
                border border-slate-200 dark:border-slate-800">

        <table class="min-w-full text-sm">

            <thead class="text-[10px] font-bold uppercase tracking-widest
                          text-slate-500 dark:text-slate-400
                          bg-slate-50/80 dark:bg-slate-800/60
                          border-b border-slate-200 dark:border-slate-800">

                <tr>
                    <th class="px-6 py-4 text-left">Nama Bahan</th>
                    <th class="px-6 py-4 text-left">Stok Saat Ini</th>
                    <th class="px-6 py-4 text-left">Status</th>
                    <th class="px-6 py-4 text-right">Terakhir Diupdate</th>
                </tr>

            </thead>

            <tbody>

                @forelse ($ingredients as $ingredient)

                    @php
                        $stock = $ingredient->converted_stock;
                        $minimum = $ingredient->converted_minimum_stock;
                        $percent = stockPercentOwner($stock, $minimum);
                    @endphp

                    <tr class="border-b border-slate-100 dark:border-slate-800
                               hover:bg-slate-50 dark:hover:bg-slate-800/60">

                        {{-- NAME --}}
                        <td class="px-6 py-6">

                            <div class="font-medium text-slate-800 dark:text-white">
                                {{ $ingredient->name }}
                            </div>

                            <div class="text-xs text-slate-400 mt-1">
                                {{ $ingredient->category->name ?? '-' }}
                            </div>

                            @if ($ingredient->selling_price > 0)
                                @php
                                    $priceUnit = match($ingredient->display_unit ?? '') {
                                        'kg'  => '/kg',
                                        'l'   => '/liter',
                                        'g'   => '/gram',
                                        'ml'  => '/ml',
                                        'pcs' => '/pack',
                                        default => '',
                                    };
                                @endphp
                                <div class="mt-2 text-[12px] font-bold text-emerald-600 dark:text-emerald-400">
                                    Rp {{ number_format($ingredient->selling_price, 0, ',', '.') }}<span class="text-[10px] font-normal text-emerald-500/80">{{ $priceUnit }}</span>
                                </div>
                            @endif

                        </td>


                        {{-- STOCK --}}
                        <td class="px-6 py-6 w-72">

                            <div class="text-lg font-semibold text-slate-800 dark:text-white">

                                {{ formatStockOwner($stock) }}

                                <span class="text-sm text-slate-500">
                                    {{ $ingredient->display_stock_unit }}
                                </span>

                            </div>

                            <div class="text-xs text-slate-400 mt-1 mb-3">

                                Minimum:
                                {{ formatStockOwner($minimum) }}
                                {{ $ingredient->display_stock_unit }}

                            </div>

                            <div class="w-full h-2 rounded-full overflow-hidden
                                        bg-slate-100 dark:bg-slate-800">

                                <div
                                    class="h-full rounded-full
                                    {{ $stock <= 0 ? 'bg-red-500' : ($stock <= $minimum ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                    style="width: {{ $percent }}%"
                                ></div>

                            </div>

                        </td>


                        {{-- STATUS --}}
                        <td class="px-6 py-6">
                            @if ($stock <= 0)
                                <span class="px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full bg-red-50 border border-red-200 text-red-600 dark:bg-red-900/30 dark:border-red-800/50 dark:text-red-400">Habis</span>
                            @elseif ($stock <= $minimum)
                                <span class="px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full bg-amber-50 border border-amber-200 text-amber-600 dark:bg-amber-900/30 dark:border-amber-800/50 dark:text-amber-400">Rendah</span>
                            @else
                                <span class="px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400">Aman</span>
                            @endif
                        </td>


                        {{-- UPDATE --}}
                        <td class="px-6 py-6 text-right text-slate-500">

                            {{ $ingredient->updated_at?->format('d M Y H:i') ?? '-' }}

                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            Tidak ada data bahan
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- PAGINATION --}}
    @if ($ingredients->hasPages())
        <div class="mt-8">
            {{ $ingredients->links() }}
        </div>
    @endif

</div>

@endsection
