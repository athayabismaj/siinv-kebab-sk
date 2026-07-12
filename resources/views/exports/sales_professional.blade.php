@php
    $reportTitle = 'LAPORAN PENJUALAN ' . strtoupper($type === 'daily' ? 'HARIAN' : ($type === 'weekly' ? 'MINGGUAN' : 'BULANAN'));
    $metaRows = [
        ['Total Omzet', 'Rp ' . number_format($totalRevenue ?? 0, 0, ',', '.'), 'Jumlah Transaksi', number_format($totalTransactions ?? 0, 0, ',', '.') . ' Transaksi'],
        ['Rata-rata Transaksi', 'Rp ' . number_format($avgTransaction ?? 0, 0, ',', '.'), '', ''],
    ];
    $excelMetaRows = [
        ['Total Omzet', 'Rp ' . number_format($totalRevenue ?? 0, 0, ',', '.')],
        ['Jumlah Transaksi', number_format($totalTransactions ?? 0, 0, ',', '.') . ' Transaksi'],
        ['Rata-rata Transaksi', 'Rp ' . number_format($avgTransaction ?? 0, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 3])
        
        @if($type === 'daily')
            <tr>
                <th style="font-weight: bold; text-align: left; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Nama Menu</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Jumlah Terjual</th>
                <th style="font-weight: bold; text-align: right; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Total Penjualan</th>
            </tr>
            @forelse($contributions ?? [] as $row)
                <tr>
                    <td style="border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $row->menu_name }}</td>
                    <td style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $row->total_qty }}</td>
                    <td style="text-align: right; border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $row->total_sales }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 12px 10px;">Belum ada transaksi pada periode ini.</td>
                </tr>
            @endforelse
        @elseif($type === 'weekly')
            <tr>
                <th style="font-weight: bold; text-align: left; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Tanggal</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Transaksi</th>
                <th style="font-weight: bold; text-align: right; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Omzet</th>
            </tr>
            @forelse($weeklyBreakdown ?? [] as $row)
                <tr>
                    <td style="border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</td>
                    <td style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $row->trx_count }}</td>
                    <td style="text-align: right; border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $row->revenue }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 12px 10px;">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        @else
            <tr>
                <th style="font-weight: bold; text-align: left; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Tanggal</th>
                <th style="font-weight: bold; text-align: center; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Transaksi</th>
                <th style="font-weight: bold; text-align: right; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Omzet</th>
            </tr>
            @forelse($dailyBreakdown ?? [] as $row)
                <tr>
                    <td style="border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</td>
                    <td style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $row->trx_count }}</td>
                    <td style="text-align: right; border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $row->revenue }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 12px 10px;">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        @endif
    </table>
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan {{ ucfirst($type) }} - Kebab SK</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12.5px; color: #222; background: #fff; padding: 36px 42px; line-height: 1.45; }
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
                @if($type === 'daily')
                    <th style="width:50%; padding:10px 12px; text-align:left; font-size:11px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Nama Menu</th>
                    <th style="width:20%; padding:10px 12px; text-align:center; font-size:11px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Jumlah Terjual</th>
                    <th style="width:30%; padding:10px 12px; text-align:right; font-size:11px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Total Penjualan</th>
                @else
                    <th style="width:50%; padding:10px 12px; text-align:left; font-size:11px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Tanggal</th>
                    <th style="width:20%; padding:10px 12px; text-align:center; font-size:11px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Transaksi</th>
                    <th style="width:30%; padding:10px 12px; text-align:right; font-size:11px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Omzet</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @if($type === 'daily')
                @forelse($contributions ?? [] as $index => $row)
                    <tr style="border-bottom:1px solid #eee; {{ $index % 2 === 1 ? 'background-color:#f9f9f9;' : '' }}">
                        <td style="padding:9px 12px; text-align:left; color:#333; font-weight:600; font-size: 12px;">{{ $row->menu_name }}</td>
                        <td style="padding:9px 12px; text-align:center; color:#555; font-size: 12px;">{{ number_format($row->total_qty) }}</td>
                        <td style="padding:9px 12px; text-align:right; color:#222; font-weight:bold; font-size: 12px;">Rp {{ number_format($row->total_sales, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="padding:26px 12px; text-align:center; color:#888; font-style:italic; font-size: 12px;">
                            Belum ada transaksi pada periode ini.
                        </td>
                    </tr>
                @endforelse
            @elseif($type === 'weekly')
                @forelse($weeklyBreakdown ?? [] as $index => $row)
                    <tr style="border-bottom:1px solid #eee; {{ $index % 2 === 1 ? 'background-color:#f9f9f9;' : '' }}">
                        <td style="padding:9px 12px; text-align:left; color:#333; font-weight:600; font-size: 12px;">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</td>
                        <td style="padding:9px 12px; text-align:center; color:#555; font-size: 12px;">{{ number_format($row->trx_count) }}</td>
                        <td style="padding:9px 12px; text-align:right; color:#222; font-weight:bold; font-size: 12px;">Rp {{ number_format($row->revenue, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="padding:26px 12px; text-align:center; color:#888; font-style:italic; font-size: 12px;">
                            Tidak ada data untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            @else
                @forelse($dailyBreakdown ?? [] as $index => $row)
                    <tr style="border-bottom:1px solid #eee; {{ $index % 2 === 1 ? 'background-color:#f9f9f9;' : '' }}">
                        <td style="padding:9px 12px; text-align:left; color:#333; font-weight:600; font-size: 12px;">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</td>
                        <td style="padding:9px 12px; text-align:center; color:#555; font-size: 12px;">{{ number_format($row->trx_count) }}</td>
                        <td style="padding:9px 12px; text-align:right; color:#222; font-weight:bold; font-size: 12px;">Rp {{ number_format($row->revenue, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="padding:26px 12px; text-align:center; color:#888; font-style:italic; font-size: 12px;">
                            Tidak ada data untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            @endif
        </tbody>
    </table>
    <div style="border-top:1px solid #bbb;"></div>

    {{-- FOOTER --}}
    <table style="width:100%; margin-top:20px; border-top:1px solid #ddd;">
        <tr>
            <td style="padding-top:10px; font-size:10.5px; color:#999;">
                Dicetak oleh: <strong style="color:#555;">{{ auth()->user() ? auth()->user()->name : 'Sistem' }}</strong>
            </td>
            <td style="padding-top:10px; font-size:10.5px; color:#999; text-align:right;">
                {{ now()->translatedFormat('d F Y, H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
@endif
