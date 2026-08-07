@php
    $reportTitle = 'LAPORAN STOK HARIAN';
    $branchName = isset($branch) && $branch ? $branch->name : 'Semua Cabang';
    $metaRows = [
        ['Cabang', $branchName, 'Jumlah Sesi', number_format($summary['sessions_count'] ?? 0, 0, ',', '.') . ' Sesi'],
        ['Total Item', number_format($summary['items_count'] ?? 0, 0, ',', '.') . ' Bahan Baku', '', ''],
    ];

    $unitRows = [];
    if (isset($summary['by_unit']) && is_array($summary['by_unit'])) {
        $formatNum = function($num) {
            return floor($num) == $num 
                ? number_format($num, 0, ',', '.') 
                : rtrim(rtrim(number_format($num, 2, ',', '.'), '0'), ',');
        };

        foreach ($summary['by_unit'] as $unitData) {
            $unitName = strtoupper($unitData['unit']);
            $used = $formatNum($unitData['used']);
            $opening = $formatNum($unitData['opening']);
            $remaining = $formatNum($unitData['remaining']);
            
            $unitRows[] = [
                'unit' => $unitName,
                'used' => $used,
                'opening' => $opening,
                'remaining' => $remaining
            ];

            $metaRows[] = [
                'Terpakai (' . $unitName . ')', 
                $used, 
                'Rincian Stok ' . $unitName, 
                'Bawa: ' . $opening . '  →  Sisa: ' . $remaining
            ];
        }
    }
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 6])
        {{-- Table Header --}}
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">No</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Tanggal &amp; Kasir</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Status</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Total Item Aktif</th>
            <th style="font-weight: bold; text-align: right; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Est. Nilai Modal</th>
            <th style="font-weight: bold; text-align: right; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Est. Pendapatan</th>
        </tr>

        {{-- Table Body --}}
        @forelse($sessions as $index => $session)
            @php $rowBg = $loop->even ? '#f8f9fc' : '#ffffff'; @endphp
            <tr>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #888888;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #1a1a2e; font-weight: bold;">{{ $session->session_date->translatedFormat('d M Y') }} - {{ $session->cashier->name ?? 'User Tidak Diketahui' }}</td>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #333333;">{{ $session->status === 'closed' ? 'Selesai' : 'Aktif' }}</td>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #333333;">{{ (int) ($session->items_count ?? 0) }}</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #d32f2f; font-weight: bold;">Rp {{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #0d8a53; font-weight: bold;">Rp {{ number_format((float) ($session->total_revenue ?? 0), 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align: center; border: 1px solid #d0d4e0; padding: 14px 10px; color: #999999; font-style: italic;">Tidak ada data laporan stok harian pada periode ini.</td>
            </tr>
        @endforelse

        {{-- Spacer --}}
        <tr><td colspan="6"></td></tr>

        {{-- Footer --}}
        <tr>
            <td colspan="4" style="color: #999999; font-size: 9px;">Dicetak oleh: {{ auth()->user() ? auth()->user()->name : 'Sistem' }}</td>
            <td colspan="2" style="text-align: right; color: #999999; font-size: 9px;">{{ now()->translatedFormat('d F Y, H:i:s') }}</td>
        </tr>
    </table>
@else
@php
    // Reset metaRows for HTML so it doesn't stretch the header table
    $metaRows = [
        ['Cabang', $branchName, 'Jumlah Sesi', number_format($summary['sessions_count'] ?? 0, 0, ',', '.') . ' Sesi'],
        ['Total Item', number_format($summary['items_count'] ?? 0, 0, ',', '.') . ' Bahan Baku', '', ''],
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Stok Harian - Kebab SK</title>
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
                <td style="width: 33.33%; background-color: #f8f9fa; border: 1px solid #e0e0e0; border-top: 3px solid #1a1a2e; padding: 12px; vertical-align: top;">
                    <div style="font-size: 10px; color: #666; text-transform: uppercase; font-weight: bold; margin-bottom: 4px;">Rincian Stok {{ $row['unit'] }}</div>
                    <div style="font-size: 16px; font-weight: bold; color: #111; margin-bottom: 8px;">{{ $row['used'] }} <span style="font-size: 10px; font-weight: normal; color: #888;">Terpakai</span></div>
                    <div style="font-size: 9px; color: #333; border-top: 1px solid #eaeaea; padding-top: 6px;">
                        Bawa: <strong>{{ $row['opening'] }}</strong> <span style="color:#aaa; margin:0 3px;">→</span> Sisa: <strong>{{ $row['remaining'] }}</strong>
                    </div>
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
                <th style="width:4%;  padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">No</th>
                <th style="width:20%; padding:8px 8px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Tanggal &amp; Kasir</th>
                <th style="width:9%;  padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Status</th>
                <th style="width:12%;  padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Item</th>
                <th style="width:20%; padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#e67e22; border:none;">Est. Modal</th>
                <th style="width:20%; padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#c0392b; border:none;">Est. Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $index => $session)
                <tr style="border-bottom:1px solid #eee; {{ $loop->even ? 'background-color:#f9f9f9;' : '' }}">
                    <td style="padding:7px 8px; text-align:center; color:#bbb; font-size:10px;">{{ $index + 1 }}</td>
                    <td style="padding:7px 8px; text-align:left; font-weight:600; color:#222;">
                        {{ $session->session_date->translatedFormat('d M Y') }}<br>
                        <span style="font-size: 9px; font-weight: normal; color: #666;">{{ $session->cashier->name ?? 'User Tidak Diketahui' }}</span>
                    </td>
                    <td style="padding:7px 8px; text-align:center; color:#555;">{{ $session->status === 'closed' ? 'Selesai' : 'Aktif' }}</td>
                    <td style="padding:7px 8px; text-align:center; color:#555;">{{ number_format((int) ($session->items_count ?? 0), 0, ',', '.') }}</td>
                    <td style="padding:7px 8px; text-align:right; color:#e67e22; font-weight:bold;">Rp {{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}</td>
                    <td style="padding:7px 8px; text-align:right; color:#c0392b; font-weight:bold;">Rp {{ number_format((float) ($session->total_revenue ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding:20px; text-align:center; color:#aaa; font-style:italic;">
                        Tidak ada data laporan stok harian pada periode ini.
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
