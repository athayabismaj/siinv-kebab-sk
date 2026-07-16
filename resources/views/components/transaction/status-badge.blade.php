@props([
    'presentation',
    'alignment' => 'left',
])

<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black {{ $presentation->badgeClass }}">
    <span class="h-1.5 w-1.5 rounded-full {{ $presentation->dotClass }}"></span>
    <span>{{ $presentation->statusLabel }}</span>
</span>

@if($presentation->isVoid)
    <x-transaction.void-reason :presentation="$presentation" :alignment="$alignment" />
@endif
