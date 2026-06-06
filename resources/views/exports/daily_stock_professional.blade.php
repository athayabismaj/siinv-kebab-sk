@php
    $reportTitle = 'LAPORAN STOK HARIAN';
    $metaRows = [
        ['Jumlah Sesi', number_format($summary['sessions_count'] ?? 0, 0, ',', '.') . ' Sesi', 'Est. Nilai Modal', 'Rp ' . number_format($summary['total_value'] ?? 0, 0, ',', '.')],
        ['Est. Nilai Terjual', 'Rp ' . number_format($summary['total_revenue'] ?? 0, 0, ',', '.'), '', ''],
    ];
    $excelMetaRows = [
        ['Jumlah Sesi', number_format($summary['sessions_count'] ?? 0, 0, ',', '.') . ' Sesi'],
        ['Est. Nilai Modal', 'Rp ' . number_format($summary['total_value'] ?? 0, 0, ',', '.')],
        ['Est. Nilai Terjual', 'Rp ' . number_format($summary['total_revenue'] ?? 0, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 9])
        <tr>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">No</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Tanggal &amp; Kasir</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Status</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Total Item Aktif</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Bawa</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Sisa</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Terpakai</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Est. Nilai Modal</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Est. Nilai Terjual</th>
        </tr>
        @forelse($sessions as $index => $session)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ $session->session_date->translatedFormat('d M Y') }} - {{ $session->cashier->name ?? 'User Tidak Diketahui' }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $session->status === 'closed' ? 'Selesai' : 'Aktif' }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ (int) ($session->items_count ?? 0) }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ (float) ($session->total_opening ?? 0) }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ (float) ($session->total_remaining ?? 0) }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ (float) ($session->total_used ?? 0) }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ (int) round((float) ($session->total_value ?? 0)) }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ (int) round((float) ($session->total_revenue ?? 0)) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" style="text-align: center; border: 1px solid #000000;">Tidak ada data laporan stok harian pada periode ini.</td>
            </tr>
        @endforelse
    </table>
@else
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

    {{-- DATA TABLE --}}
    <table style="width: 100%;">
        <thead>
            <tr style="background-color: #f0f0f0; border-top: 1px solid #bbb; border-bottom: 1px solid #bbb;">
                <th style="width:4%;  padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">No</th>
                <th style="width:20%; padding:8px 8px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Tanggal &amp; Kasir</th>
                <th style="width:9%;  padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Status</th>
                <th style="width:8%;  padding:8px 8px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Item</th>
                <th style="width:9%;  padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Bawa</th>
                <th style="width:9%;  padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Sisa</th>
                <th style="width:9%;  padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Terpakai</th>
                <th style="width:16%; padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#e67e22; border:none;">Est. Modal</th>
                <th style="width:16%; padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#c0392b; border:none;">Est. Terjual</th>
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
                    <td style="padding:7px 8px; text-align:right; color:#222;">{{ rtrim(rtrim(number_format((float) ($session->total_opening ?? 0), 2, ',', '.'), '0'), ',') }}</td>
                    <td style="padding:7px 8px; text-align:right; color:#222;">{{ rtrim(rtrim(number_format((float) ($session->total_remaining ?? 0), 2, ',', '.'), '0'), ',') }}</td>
                    <td style="padding:7px 8px; text-align:right; color:#222; font-weight:bold;">{{ rtrim(rtrim(number_format((float) ($session->total_used ?? 0), 2, ',', '.'), '0'), ',') }}</td>
                    <td style="padding:7px 8px; text-align:right; color:#e67e22; font-weight:bold;">Rp {{ number_format((float) ($session->total_value ?? 0), 0, ',', '.') }}</td>
                    <td style="padding:7px 8px; text-align:right; color:#c0392b; font-weight:bold;">Rp {{ number_format((float) ($session->total_revenue ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="padding:20px; text-align:center; color:#aaa; font-style:italic;">
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
