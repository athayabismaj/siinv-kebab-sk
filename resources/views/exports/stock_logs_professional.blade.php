@php
    $reportTitle = 'LAPORAN RIWAYAT PERUBAHAN STOK BAHAN BAKU';
    $metaRows = [
        ['Filter Log', $typeLabel, 'Total Log', number_format($summary['total'] ?? 0, 0, ',', '.')],
    ];
    $excelMetaRows = [
        ['Filter Log', $typeLabel],
        ['Total Log', number_format($summary['total'] ?? 0, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 7])
        <tr>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">No</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Waktu</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Bahan Baku</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Jenis Mutasi</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Jumlah</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Sumber</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Catatan</th>
        </tr>
        @forelse($logs as $index => $log)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                <td style="border: 1px solid #000000;">{{ $log->ingredient->name ?? '-' }}</td>
                <td style="border: 1px solid #000000;">{{ $log->display_type_label ?? '-' }}</td>
                <td style="text-align: right; border: 1px solid #000000;">
                    {{ $log->display_qty_prefix ?? '' }}{{ $log->display_qty ?? '0' }} {{ strtoupper($log->display_unit ?? '-') }}
                    @if(!empty($log->display_pack_text))
                        ({{ $log->display_pack_text }})
                    @endif
                </td>
                <td style="border: 1px solid #000000;">{{ $log->display_source ?? '-' }}</td>
                <td style="border: 1px solid #000000;">{{ $log->note ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; border: 1px solid #000000;">Tidak ada data riwayat stok pada periode ini.</td>
            </tr>
        @endforelse
    </table>
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Riwayat Perubahan Stok - Kebab SK</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; background: #fff; padding: 30px 35px; }
        table { border-collapse: collapse; }
    </style>
</head>
<body>
    @include('exports.partials.report_header_html')

    <table style="width: 100%;">
        <thead>
            <tr style="background-color: #f0f0f0; border-top: 1px solid #bbb; border-bottom: 1px solid #bbb;">
                <th style="width:5%; padding:8px 10px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333;">No</th>
                <th style="width:16%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333;">Waktu</th>
                <th style="width:18%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333;">Bahan Baku</th>
                <th style="width:16%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333;">Jenis Mutasi</th>
                <th style="width:16%; padding:8px 10px; text-align:right; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333;">Jumlah</th>
                <th style="width:14%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333;">Sumber</th>
                <th style="width:15%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $index => $log)
                <tr style="border-bottom:1px solid #eee; {{ $loop->even ? 'background-color:#f9f9f9;' : '' }}">
                    <td style="padding:7px 10px; text-align:center; color:#bbb; font-size:10px;">{{ $index + 1 }}</td>
                    <td style="padding:7px 10px; text-align:left; color:#222;">{{ optional($log->created_at)->format('d/m/y H:i') }}</td>
                    <td style="padding:7px 10px; text-align:left; font-weight:600; color:#222;">{{ $log->ingredient->name ?? '-' }}</td>
                    <td style="padding:7px 10px; text-align:left; color:#222;">{{ $log->display_type_label ?? '-' }}</td>
                    <td style="padding:7px 10px; text-align:right; color:#222;">
                        {{ $log->display_qty_prefix ?? '' }}{{ $log->display_qty ?? '0' }} {{ strtoupper($log->display_unit ?? '-') }}
                        @if(!empty($log->display_pack_text))
                            <span style="color:#666;">({{ $log->display_pack_text }})</span>
                        @endif
                    </td>
                    <td style="padding:7px 10px; text-align:left; color:#555;">{{ $log->display_source ?? '-' }}</td>
                    <td style="padding:7px 10px; text-align:left; color:#777;">{{ $log->note ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:20px; text-align:center; color:#aaa; font-style:italic;">
                        Tidak ada data riwayat stok pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div style="border-top:1px solid #bbb;"></div>

    <table style="width:100%; margin-top:16px; border-top:1px solid #ddd;">
        <tr>
            <td style="padding-top:8px; font-size:9.5px; color:#999;">
                Kebab SK - Laporan Riwayat Perubahan Stok Bahan Baku
            </td>
            <td style="padding-top:8px; font-size:9.5px; color:#999; text-align:right;">
                {{ now()->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>
</body>
</html>
@endif
