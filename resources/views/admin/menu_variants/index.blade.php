@extends('layouts.app')

@section('sidebar')
    @include('partials.sidebar_admin')
@endsection

@section('title', 'Varian Menu: ' . $menu->name)

@section('content')
<div class="w-full space-y-6 overflow-x-hidden pb-10">

    {{-- ================= HEADER & BREADCRUMB ================= --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-2">
        <div class="flex-1 w-full overflow-hidden">
            
            {{-- BREADCRUMB (Anti Pecah di Mobile) --}}
            <nav class="flex items-center gap-2.5 text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 overflow-x-auto hide-scrollbar pb-1">
                <a href="{{ route('admin.panel') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Beranda
                </a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <span class="whitespace-nowrap text-slate-500 dark:text-slate-400">
                    Menu & Resep
                </span>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <a href="{{ route('admin.menus.index') }}" class="whitespace-nowrap hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    Manajemen Menu
                </a>
                <span class="shrink-0 text-slate-300 dark:text-slate-600">/</span>
                
                <span class="whitespace-nowrap text-blue-600 dark:text-blue-400">
                    Varian Menu
                </span>
            </nav>

            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Varian Menu
                </h1>
                <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-lg text-xs border border-slate-200 dark:border-slate-700 shadow-sm">
                    {{ $menu->name }}
                </span>
            </div>

            <p class="text-sm text-slate-500 dark:text-slate-400 max-w-2xl leading-relaxed">
                Kelola varian dari menu <strong class="text-slate-700 dark:text-slate-300">{{ $menu->name }}</strong>. Setiap varian memiliki harga modal (HPP) dan harga jual yang berbeda-beda.
            </p>
        </div>

        {{-- TOMBOL AKSI --}}
        <div class="flex flex-col sm:flex-row items-center gap-3 shrink-0 w-full lg:w-auto mt-2 lg:mt-0">
            <a href="{{ route('admin.menus.index') }}"
               class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-[13px] font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 transition-all shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Menu
            </a>

            <a href="{{ route('admin.menu-variants.create', $menu->id) }}"
               class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-[13px] font-semibold rounded-xl hover:bg-indigo-700 transition-all shadow-sm shadow-indigo-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Varian
            </a>
        </div>
    </div>

    {{-- ================= ALERTS ================= --}}
    @if(session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-900/20 dark:text-emerald-300 shadow-sm">
            <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ================= TABLE & CARD SECTION ================= --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
        
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-800/30">
            <div>
                <h2 class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Daftar Varian Aktif</h2>
            </div>
            <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest bg-white dark:bg-slate-900 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 shadow-sm">
                Total: <span class="text-slate-800 dark:text-slate-200">{{ $variants->count() }} Varian</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="hidden md:table-header-group text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Nama Varian</th>
                        <th class="px-6 py-4 text-right">Harga Modal (HPP)</th>
                        <th class="px-6 py-4 text-right">Harga Jual</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Urutan</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                    @forelse($variants as $variant)
                        
                        <tr class="hidden md:table-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors group">
                            
                            {{-- Nama --}}
                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-900 dark:text-white text-[15px]">{{ $variant->name }}</p>
                            </td>

                            {{-- Harga Modal --}}
                            <td class="px-6 py-5 text-right">
                                <div class="font-medium text-slate-500 dark:text-slate-400 tabular-nums">
                                    Rp <span class="font-bold">{{ number_format($variant->cost_price ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </td>

                            {{-- Harga Jual --}}
                            <td class="px-6 py-5 text-right">
                                <div class="font-black text-slate-900 dark:text-white tabular-nums text-base">
                                    Rp {{ number_format($variant->sell_price ?? $variant->price, 0, ',', '.') }}
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-5 text-center">
                                @if($variant->is_available)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 uppercase tracking-widest">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Tersedia
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:border-rose-800/50 dark:text-rose-400 uppercase tracking-widest">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Nonaktif
                                    </span>
                                @endif
                            </td>

                            {{-- Urutan --}}
                            <td class="px-6 py-5 text-center">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $variant->sort_order }}</span>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-3 text-[12px] font-bold">
                                    
                                    <a href="{{ route('admin.menu-variants.edit', [$menu->id, $variant->id]) }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors uppercase tracking-widest">
                                        Edit
                                    </a>
                                    
                                    <span class="text-slate-200 dark:text-slate-700">|</span>
                                    
                                    <form action="{{ route('admin.menu-variants.destroy', [$menu->id, $variant->id]) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus varian menu ini?')" class="text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 transition-colors uppercase tracking-widest">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <tr class="md:hidden">
                            <td colspan="6" class="p-0">
                                <div class="p-5 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                    
                                    <div class="flex justify-between items-start gap-3 mb-4">
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-[16px] leading-tight">{{ $variant->name }}</p>
                                        </div>
                                        @if($variant->is_available)
                                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400 uppercase tracking-widest">
                                                Tersedia
                                            </span>
                                        @else
                                            <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold rounded bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:border-rose-800/50 dark:text-rose-400 uppercase tracking-widest">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 mb-4 p-3 rounded-xl bg-slate-50 border border-slate-100 dark:bg-slate-800/50 dark:border-slate-800">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Modal (HPP)</p>
                                            <p class="font-semibold text-slate-600 dark:text-slate-400 text-sm tabular-nums">Rp {{ number_format($variant->cost_price ?? 0, 0, ',', '.') }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Harga Jual</p>
                                            <p class="font-black text-slate-900 dark:text-white text-base tabular-nums leading-none">Rp {{ number_format($variant->sell_price ?? $variant->price, 0, ',', '.') }}</p>
                                        </div>
                                    </div>

                                    <div class="text-[11px] font-semibold text-slate-400 mb-4">
                                        Urutan Tampil: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $variant->sort_order }}</span>
                                    </div>

                                    <div class="flex items-center gap-4 text-[11px] font-bold uppercase tracking-widest pt-4 border-t border-slate-100 dark:border-slate-800/50">
                                        <a href="{{ route('admin.menu-variants.edit', [$menu->id, $variant->id]) }}" class="flex-1 flex items-center justify-center gap-1.5 text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors bg-blue-50 dark:bg-blue-500/10 px-3 py-2 rounded-lg border border-blue-100 dark:border-blue-500/20">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.menu-variants.destroy', [$menu->id, $variant->id]) }}" method="POST" class="flex-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus varian menu ini?')" class="w-full flex items-center justify-center gap-1.5 text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 transition-colors bg-rose-50 dark:bg-rose-500/10 px-3 py-2 rounded-lg border border-rose-100 dark:border-rose-500/20">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-50 dark:bg-slate-800 mb-3 border border-slate-100 dark:border-slate-700">
                                    <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                </div>
                                <p class="text-slate-500 dark:text-slate-400 text-sm font-medium">Belum ada data varian untuk menu ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection