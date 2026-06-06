@php
    $reportTitle = 'LAPORAN PENGELUARAN';
    $metaRows = [
        ['Jumlah Entri', number_format($summary['expenseCount'] ?? 0, 0, ',', '.') . ' Entri', 'Total Pengeluaran', 'Rp ' . number_format($summary['expenseTotal'] ?? 0, 0, ',', '.')],
    ];
    $excelMetaRows = [
        ['Jumlah Entri', number_format($summary['expenseCount'] ?? 0, 0, ',', '.') . ' Entri'],
        ['Total Pengeluaran', 'Rp ' . number_format($summary['expenseTotal'] ?? 0, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 6])
        <tr>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">No</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Tanggal &amp; Waktu</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Kategori / Sumber</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Catatan</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Diinput Oleh</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Nominal Pengeluaran</th>
        </tr>
        @forelse($entries as $index => $entry)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ \Carbon\Carbon::parse($entry->entry_date)->translatedFormat('d M Y') }} {{ $entry->created_at ? $entry->created_at->format('H:i') : '' }}</td>
                <td style="border: 1px solid #000000;">{{ $entry->source ?: '-' }}</td>
                <td style="border: 1px solid #000000;">{{ $entry->note ?: '-' }}</td>
                <td style="border: 1px solid #000000;">{{ $entry->creator->name ?? 'System' }}</td>
                <td style="text-align: right; border: 1px solid #000000;">{{ (int) round((float) $entry->amount) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align: center; border: 1px solid #000000;">Tidak ada data laporan pengeluaran pada periode ini.</td>
            </tr>
        @endforelse
    </table>
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengeluaran - Kebab SK</title>
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
                <th style="width:5%;  padding:8px 10px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">No</th>
                <th style="width:20%; padding:8px 10px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Tanggal &amp; Waktu</th>
                <th style="width:20%; padding:8px 10px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Kategori / Sumber</th>
                <th style="width:25%; padding:8px 10px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Catatan</th>
                <th style="width:15%; padding:8px 10px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Diinput Oleh</th>
                <th style="width:15%; padding:8px 10px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $index => $entry)
                <tr style="border-bottom:1px solid #eee; {{ $loop->even ? 'background-color:#f9f9f9;' : '' }}">
                    <td style="padding:7px 10px; text-align:center; color:#bbb; font-size:10px;">{{ $index + 1 }}</td>
                    <td style="padding:7px 10px; text-align:left; font-weight:600; color:#222;">
                        {{ \Carbon\Carbon::parse($entry->entry_date)->translatedFormat('d M Y') }}
                        @if($entry->created_at)
                            <div style="font-size: 9px; font-weight: normal; color: #666; margin-top: 2px;">{{ $entry->created_at->format('H:i') }}</div>
                        @endif
                    </td>
                    <td style="padding:7px 10px; text-align:left; color:#333; font-weight:600;">{{ $entry->source ?: '-' }}</td>
                    <td style="padding:7px 10px; text-align:left; color:#555; font-size: 10px; line-height: 1.3;">{{ $entry->note ?: '-' }}</td>
                    <td style="padding:7px 10px; text-align:left; color:#555;">{{ $entry->creator->name ?? 'System' }}</td>
                    <td style="padding:7px 10px; text-align:right; color:#222; font-weight:bold;">Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding:20px; text-align:center; color:#aaa; font-style:italic;">
                        Tidak ada data laporan pengeluaran pada periode ini.
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
