@php
    $reportTitle = 'LAPORAN RIWAYAT TRANSAKSI';
    $metaRows = [
        ['Jumlah Transaksi', number_format($summary['total_transactions'] ?? 0, 0, ',', '.') . ' Transaksi', 'Total Omzet', 'Rp ' . number_format($summary['total_revenue'] ?? 0, 0, ',', '.')],
        ['Rata-rata Transaksi', 'Rp ' . number_format($summary['avg_transaction'] ?? 0, 0, ',', '.'), '', ''],
    ];
    $excelMetaRows = [
        ['Jumlah Transaksi', number_format($summary['total_transactions'] ?? 0, 0, ',', '.') . ' Transaksi'],
        ['Total Omzet', 'Rp ' . number_format($summary['total_revenue'] ?? 0, 0, ',', '.')],
        ['Rata-rata Transaksi', 'Rp ' . number_format($summary['avg_transaction'] ?? 0, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 7])
        <tr>
            <th style="font-weight: bold; border: 1px solid #000000;">Tanggal &amp; Waktu</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Kode Transaksi</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Kasir</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Pembayaran</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Status</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Item</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Total</th>
        </tr>
        @forelse($transactions as $trx)
            @php $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount; @endphp
            <tr>
                <td style="border: 1px solid #000000;">{{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d M Y H:i') }}</td>
                <td style="border: 1px solid #000000;">{{ $trx->transaction_code }}</td>
                <td style="border: 1px solid #000000;">{{ $trx->user->name ?? '-' }}</td>
                <td style="border: 1px solid #000000;">{{ $trx->paymentMethod->name ?? '-' }}</td>
                <td style="border: 1px solid #000000;">{{ $isPaid ? 'Lunas' : 'Kurang' }}</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $trx->details_count }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ (float) $trx->total_amount }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; border: 1px solid #000000;">Tidak ada riwayat transaksi pada periode ini.</td>
            </tr>
        @endforelse
    </table>
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Transaksi - Kebab SK</title>
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
                <th style="width:15%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Waktu</th>
                <th style="width:20%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Kode</th>
                <th style="width:15%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Kasir</th>
                <th style="width:15%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Pembayaran</th>
                <th style="width:10%; padding:8px 10px; text-align:left; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Status</th>
                <th style="width:10%; padding:8px 10px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Item</th>
                <th style="width:15%; padding:8px 10px; text-align:right; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $trx)
                @php $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount; @endphp
                <tr style="border-bottom:1px solid #eee; {{ $index % 2 === 1 ? 'background-color:#f9f9f9;' : '' }}">
                    <td style="padding:7px 10px; text-align:left; font-weight:600; color:#222;">
                        {{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d M Y') }}
                        <div style="font-size: 9px; font-weight: normal; color: #666; margin-top: 2px;">{{ $trx->created_at->format('H:i') }}</div>
                    </td>
                    <td style="padding:7px 10px; text-align:left; color:#333; font-weight:600;">{{ $trx->transaction_code }}</td>
                    <td style="padding:7px 10px; text-align:left; color:#555;">{{ $trx->user->name ?? '-' }}</td>
                    <td style="padding:7px 10px; text-align:left; color:#555;">{{ $trx->paymentMethod->name ?? '-' }}</td>
                    <td style="padding:7px 10px; text-align:left; font-weight:bold; {{ $isPaid ? 'color:#10b981;' : 'color:#ef4444;' }}">{{ $isPaid ? 'Lunas' : 'Kurang' }}</td>
                    <td style="padding:7px 10px; text-align:center; color:#555;">{{ $trx->details_count }}</td>
                    <td style="padding:7px 10px; text-align:right; color:#222; font-weight:bold;">Rp {{ number_format((float) $trx->total_amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:20px; text-align:center; color:#aaa; font-style:italic;">
                        Tidak ada riwayat transaksi pada periode ini.
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
