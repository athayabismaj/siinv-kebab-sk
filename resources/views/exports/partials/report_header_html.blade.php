<table style="width:100%; margin-bottom:14px;">
    <tr>
        <td style="width:86px; vertical-align:middle;">
            @if(!empty($logoDataUri))
                <img src="{{ $logoDataUri }}" alt="Logo Kebab SK" style="width:72px; height:72px; object-fit:contain; display:block;">
            @endif
        </td>
        <td style="vertical-align:middle;">
            <div style="font-size:16px; font-weight:bold; color:#111; text-transform:uppercase; letter-spacing:0.5px;">KEBAB SK</div>
            <div style="font-size:10.5px; color:#666; margin-top:2px;">Sistem Manajemen Inventory &amp; Penjualan</div>
            <div style="font-size:16px; font-weight:bold; color:#111; text-transform:uppercase; margin-top:5px; letter-spacing:0.7px;">{{ $reportTitle }}</div>
        </td>
    </tr>
</table>

<div style="border-top:2px solid #111; margin-bottom:2px;"></div>
<div style="border-top:1px solid #111; margin-bottom:12px;"></div>

<table style="width:100%; margin-bottom:14px; border:1px solid #d8d8d8; background:#f7f7f7;">
    <tr>
        <td style="width:115px; color:#555; padding:7px 10px 3px 10px;">Periode Data</td>
        <td style="width:8px; color:#555; padding:7px 0 3px 0;">:</td>
        <td style="color:#222; padding:7px 10px 3px 4px;">{{ $periode }}</td>
        <td style="width:115px; color:#555; padding:7px 10px 3px 10px;">Mode Periode</td>
        <td style="width:8px; color:#555; padding:7px 0 3px 0;">:</td>
        <td style="width:130px; color:#222; padding:7px 10px 3px 4px;">{{ $periodLabel ?? '-' }}</td>
    </tr>
    @foreach($metaRows ?? [] as $row)
        <tr>
            <td style="color:#555; padding:3px 10px {{ $loop->last ? '7px' : '3px' }} 10px;">{{ $row[0] ?? '-' }}</td>
            <td style="color:#555; padding:3px 0 {{ $loop->last ? '7px' : '3px' }} 0;">:</td>
            <td style="color:#222; padding:3px 10px {{ $loop->last ? '7px' : '3px' }} 4px;">{{ $row[1] ?? '-' }}</td>
            <td style="color:#555; padding:3px 10px {{ $loop->last ? '7px' : '3px' }} 10px;">{{ $row[2] ?? '' }}</td>
            <td style="color:#555; padding:3px 0 {{ $loop->last ? '7px' : '3px' }} 0;">{{ !empty($row[2]) ? ':' : '' }}</td>
            <td style="color:#222; padding:3px 10px {{ $loop->last ? '7px' : '3px' }} 4px;">{{ $row[3] ?? '' }}</td>
        </tr>
    @endforeach
</table>
