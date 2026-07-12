@php
    $totalColumns = max((int) ($columns ?? 4), 4);
    $titleColspan = max($totalColumns - 1, 1);
    // Align right metadata with 'Diinput Oleh' column (index 5) for a 7-column table.
    $rightLabelIndex = max($totalColumns - 2, 2);
    $rightValueIndex = $rightLabelIndex + 1;
@endphp

{{-- ===== BRAND HEADER ===== --}}
<tr>
    <td rowspan="3" style="vertical-align: middle; width: 60px;"></td>
    <td colspan="{{ $titleColspan }}" style="font-weight: bold; font-size: 16px; color: #1a1a2e; vertical-align: bottom;">KEBAB SK</td>
</tr>
<tr>
    <td colspan="{{ $titleColspan }}" style="font-size: 10px; color: #666666;">Sistem Manajemen Inventory &amp; Penjualan</td>
</tr>
<tr>
    <td colspan="{{ $titleColspan }}" style="font-weight: bold; font-size: 12px; color: #1a1a2e;">{{ $reportTitle }}</td>
</tr>

{{-- Spacer --}}
<tr><td colspan="{{ $totalColumns }}"></td></tr>

{{-- ===== META INFO ===== --}}
<tr>
    <td style="font-weight: bold; background-color: #f0f2f8; border: 1px solid #d0d4e0; padding: 6px 10px; color: #333333;">Periode Data</td>
    <td style="background-color: #f0f2f8; border: 1px solid #d0d4e0; padding: 6px 10px; font-weight: bold; color: #1a1a2e;">{{ $periode }}</td>
    @for($i = 2; $i < $totalColumns; $i++)
        @if($i === $rightLabelIndex)
            <td style="background-color: #f0f2f8; border: 1px solid #d0d4e0; padding: 6px 10px; font-weight: bold; color: #333333;">Mode Periode</td>
        @elseif($i === $rightValueIndex)
            <td style="background-color: #f0f2f8; border: 1px solid #d0d4e0; padding: 6px 10px; font-weight: bold; color: #1a1a2e;">{{ $periodLabel ?? '-' }}</td>
        @else
            <td style="background-color: #f0f2f8; border: 1px solid #d0d4e0;"></td>
        @endif
    @endfor
</tr>
@foreach($metaRows ?? [] as $row)
    <tr>
        <td style="font-weight: bold; background-color: #fafbfc; border: 1px solid #d0d4e0; padding: 6px 10px; color: #555555;">{{ $row[0] ?? '-' }}</td>
        <td style="background-color: #fafbfc; border: 1px solid #d0d4e0; padding: 6px 10px; color: #1a1a2e; font-weight: bold;">{{ $row[1] ?? '-' }}</td>
        @for($i = 2; $i < $totalColumns; $i++)
            @if($i === $rightLabelIndex)
                @if(!empty($row[2]))
                    <td style="font-weight: bold; background-color: #fafbfc; border: 1px solid #d0d4e0; padding: 6px 10px; color: #555555;">{{ $row[2] }}</td>
                @else
                    <td style="background-color: #fafbfc; border: 1px solid #d0d4e0;"></td>
                @endif
            @elseif($i === $rightValueIndex)
                @if(!empty($row[2]))
                    <td style="background-color: #fafbfc; border: 1px solid #d0d4e0; padding: 6px 10px; color: #1a1a2e; font-weight: bold;">{{ $row[3] ?? '-' }}</td>
                @else
                    <td style="background-color: #fafbfc; border: 1px solid #d0d4e0;"></td>
                @endif
            @else
                <td style="background-color: #fafbfc; border: 1px solid #d0d4e0;"></td>
            @endif
        @endfor
    </tr>
@endforeach

{{-- Spacer --}}
<tr><td colspan="{{ $totalColumns }}"></td></tr>
