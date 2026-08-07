@php
    $reportTitle = 'LAPORAN PEMAKAIAN BAHAN';
    $branchName = isset($branch) && $branch ? $branch->name : 'Semua Cabang';
    
    $metaRows = [
        ['Cabang', $branchName, 'Total Bahan', number_format($summary['ingredients_count'] ?? 0, 0, ',', '.') . ' Jenis'],
        ['Jml. Pemakaian', number_format($summary['logs_count'] ?? 0, 0, ',', '.') . ' kali', '', ''],
    ];

    $unitRows = [];
    $unitRows[] = [
        'title' => 'Bahan Terpakai',
        'value' => number_format($summary['ingredients_count'] ?? 0, 0, ',', '.'),
        'unit' => 'Jenis Bahan'
    ];
    $unitRows[] = [
        'title' => 'Jumlah Aktivitas',
        'value' => number_format($summary['logs_count'] ?? 0, 0, ',', '.'),
        'unit' => 'Kali Pemakaian'
    ];

    if (isset($summary['by_unit']) && is_array($summary['by_unit'])) {
        foreach ($summary['by_unit'] as $unitData) {
            $unitName = strtoupper($unitData['unit']);
            $total = floor($unitData['total']) == $unitData['total'] 
                ? number_format($unitData['total'], 0, ',', '.') 
                : rtrim(rtrim(number_format($unitData['total'], 2, ',', '.'), '0'), ',');
            
            $unitRows[] = [
                'title' => 'Total ' . $unitName,
                'value' => $total,
                'unit' => $unitName
            ];
            
            $metaRows[] = [
                'Total (' . $unitName . ')', 
                $total . ' ' . $unitName, 
                '', 
                ''
            ];
        }
    }
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 5])

        {{-- Table Header --}}
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">No</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Nama Bahan Baku</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Total Pemakaian</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Jumlah Pemakaian</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Terakhir Dipakai</th>
        </tr>

        {{-- Table Body --}}
        @forelse($items as $index => $item)
            @php $rowBg = $loop->even ? '#f8f9fc' : '#ffffff'; @endphp
            <tr>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #888888;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #1a1a2e; font-weight: bold;">{{ $item->ingredient_name }}</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #0d8a53; font-weight: bold;">
                    @php
                        $qtyLabel = \App\Support\UsageQuantityFormatter::formatLabel(
                            (float) $item->total_quantity,
                            (string) ($item->base_unit ?? ''),
                            (string) ($item->display_unit ?? ''),
                            (int) ($item->pack_size ?? 1)
                        );
                    @endphp
                    {{ $qtyLabel }}
                </td>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #333333;">{{ number_format($item->usage_count, 0, ',', '.') }} kali</td>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #555555;">{{ $item->last_used_at ? \Carbon\Carbon::parse($item->last_used_at)->format('d/m/Y H:i') : '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center; border: 1px solid #d0d4e0; padding: 14px 10px; color: #999999; font-style: italic;">Tidak ada data pemakaian bahan pada periode ini.</td>
            </tr>
        @endforelse

        {{-- Spacer --}}
        <tr><td colspan="5"></td></tr>

        {{-- Footer --}}
        <tr>
            <td colspan="3" style="color: #999999; font-size: 9px;">Dicetak oleh: {{ auth()->user() ? auth()->user()->name : 'Sistem' }}</td>
            <td colspan="2" style="text-align: right; color: #999999; font-size: 9px;">{{ now()->translatedFormat('d F Y, H:i:s') }}</td>
        </tr>
    </table>
@else
@php
    // Reset metaRows for HTML to avoid duplicate info with the cards below
    $metaRows = [
        ['Cabang', $branchName, '', ''],
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pemakaian Bahan - Kebab SK</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; background: #fff; padding: 30px 35px; }
        table { border-collapse: collapse; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    @include('exports.partials.report_header_html')

    {{-- CARDS FOR HTML / PDF --}}
    @if(isset($unitRows) && count($unitRows) > 0)
    <div style="margin-bottom: 16px; width: 100%;">
        <table style="width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-left: -8px; margin-right: -8px;">
            <tr>
                @foreach($unitRows as $row)
                <td style="width: {{ 100 / count($unitRows) }}%; background-color: #f8f9fa; border: 1px solid #e0e0e0; border-top: 3px solid #1a1a2e; padding: 12px; vertical-align: top;">
                    <div style="font-size: 10px; color: #666; text-transform: uppercase; font-weight: bold; margin-bottom: 4px;">{{ $row['title'] }}</div>
                    <div style="font-size: 16px; font-weight: bold; color: #111; margin-bottom: 2px;">{{ $row['value'] }}</div>
                    <div style="font-size: 9px; color: #888;">{{ $row['unit'] }}</div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>
    @endif

    {{-- DATA TABLE --}}
    <table style="width: 100%;">
        <thead>
            <tr style="background-color: #f0f0f0; border-top: 1px solid #bbb; border-bottom: 1px solid #bbb;">
                <th style="width:5%;  padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">No</th>
                <th style="width:30%; padding:8px 8px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Nama Bahan Baku</th>
                <th style="width:25%; padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#0d8a53; border:none;">Total Pemakaian</th>
                <th style="width:20%; padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Jumlah Pemakaian</th>
                <th style="width:20%; padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Terakhir Dipakai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr style="border-bottom:1px solid #eee; {{ $loop->even ? 'background-color:#f9f9f9;' : '' }}">
                    <td style="padding:7px 8px; text-align:center; color:#bbb; font-size:10px;">{{ $index + 1 }}</td>
                    <td style="padding:7px 8px; text-align:left; font-weight:600; color:#222;">{{ $item->ingredient_name }}</td>
                    <td style="padding:7px 8px; text-align:right; color:#0d8a53; font-weight:bold;">
                        @php
                            $qtyLabel = \App\Support\UsageQuantityFormatter::formatLabel(
                                (float) $item->total_quantity,
                                (string) ($item->base_unit ?? ''),
                                (string) ($item->display_unit ?? ''),
                                (int) ($item->pack_size ?? 1)
                            );
                        @endphp
                        {{ $qtyLabel }}
                    </td>
                    <td style="padding:7px 8px; text-align:center; color:#555;">{{ number_format($item->usage_count, 0, ',', '.') }} kali</td>
                    <td style="padding:7px 8px; text-align:center; color:#555;">{{ $item->last_used_at ? \Carbon\Carbon::parse($item->last_used_at)->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding:20px; text-align:center; color:#aaa; font-style:italic;">
                        Tidak ada data pemakaian bahan pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div style="border-top:1px solid #bbb;"></div>

    {{-- FOOTER --}}
    <table style="width:100%; margin-top:16px; border-top:1px solid #ddd;">
        <tr>
            <td style="padding-top:8px; font-size:9.5px; color:#999;">
                Dicetak oleh: <strong style="color:#555;">{{ auth()->user() ? auth()->user()->name : 'Sistem' }}</strong>
            </td>
            <td style="padding-top:8px; font-size:9.5px; color:#999; text-align:right;">
                {{ now()->translatedFormat('d F Y, H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
@endif
