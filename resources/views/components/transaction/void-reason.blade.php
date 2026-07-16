@props([
    'presentation',
    'alignment' => 'left',
])

<details class="relative inline-block">
    <summary class="app-details-summary flex h-5 w-5 cursor-pointer list-none items-center justify-center rounded-full bg-white text-[11px] font-black text-slate-900 ring-1 ring-slate-300 transition hover:bg-slate-50 dark:bg-slate-950 dark:text-white dark:ring-slate-600 dark:hover:bg-slate-900" title="Lihat alasan pembatalan">!</summary>
    <div class="absolute {{ $alignment === 'right' ? 'right-0' : 'left-0' }} top-full z-30 mt-2 w-48 rounded-xl border border-amber-100 bg-white p-3 text-left shadow-xl shadow-slate-900/10 dark:border-amber-500/20 dark:bg-slate-900 dark:shadow-black/30">
        <p class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-300">Alasan Pembatalan</p>
        <p class="mt-1 text-xs font-bold text-slate-700 dark:text-slate-100">{{ $presentation->voidReasonLabel ?: 'Alasan belum tercatat' }}</p>
    </div>
</details>
