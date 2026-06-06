@php
    $totalColumns = max((int) ($columns ?? 3), 3);
    $titleColspan = max($totalColumns - 1, 1);
@endphp

<tr>
    <td rowspan="3"></td>
    <td colspan="{{ $titleColspan }}" style="font-weight: bold; font-size: 14px;">KEBAB SK</td>
</tr>
<tr>
    <td colspan="{{ $titleColspan }}" style="font-size: 11px;">Sistem Manajemen Inventory &amp; Penjualan</td>
</tr>
<tr>
    <td colspan="{{ $titleColspan }}" style="font-weight: bold; font-size: 13px;">{{ $reportTitle }}</td>
</tr>
<tr><td colspan="{{ $totalColumns }}"></td></tr>
<tr>
    <td style="font-weight: bold;">Periode Data</td>
    <td colspan="{{ max($totalColumns - 1, 1) }}">: {{ $periode }}</td>
</tr>
<tr>
    <td style="font-weight: bold;">Mode Periode</td>
    <td colspan="{{ max($totalColumns - 1, 1) }}">: {{ $periodLabel ?? '-' }}</td>
</tr>
@foreach($excelMetaRows ?? $metaRows ?? [] as $row)
    <tr>
        <td style="font-weight: bold;">{{ $row[0] ?? '-' }}</td>
        <td colspan="{{ max($totalColumns - 1, 1) }}">: {{ $row[1] ?? '-' }}</td>
    </tr>
@endforeach
<tr><td colspan="{{ $totalColumns }}"></td></tr>
