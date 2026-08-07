@php
    $reportTitle = 'LAPORAN PENGELUARAN ' . ($periodLabel ?? 'KUSTOM');
    $netCash = ($summary['salesRevenue'] ?? 0) - ($summary['expenseTotal'] ?? 0);
    $branchName = isset($branch) && $branch ? $branch->name : 'Semua Cabang';
    $metaRows = [
        ['Cabang', $branchName, 'Total Pengeluaran', 'Rp ' . number_format($summary['expenseTotal'] ?? 0, 0, ',', '.')],
        ['Omzet Kotor', 'Rp ' . number_format($summary['salesRevenue'] ?? 0, 0, ',', '.'), 'Selisih Bersih', 'Rp ' . number_format($netCash, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 6])

        {{-- Table Header --}}
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">No</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Tanggal &amp; Waktu</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Kategori / Sumber</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Catatan</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Diinput Oleh</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Nominal Pengeluaran</th>
        </tr>

        {{-- Table Body --}}
        @forelse($entries as $index => $entry)
            @php $rowBg = $loop->even ? '#f8f9fc' : '#ffffff'; @endphp
            <tr>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #888888;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #1a1a2e; font-weight: bold;">{{ \Carbon\Carbon::parse($entry->entry_date)->translatedFormat('d M Y') }} {{ $entry->created_at ? $entry->created_at->format('H:i') : '' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #333333;">{{ $entry->branch->name ?? '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #333333; font-weight: bold;">{{ $entry->source ?: '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #555555;">{{ $entry->note ?: '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #555555;">{{ $entry->creator->name ?? 'Sistem' }}</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #d32f2f; font-weight: bold;">Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; border: 1px solid #d0d4e0; padding: 14px 10px; color: #999999; font-style: italic;">Tidak ada data laporan pengeluaran operasional pada periode ini.</td>
            </tr>
        @endforelse

        {{-- Total Row --}}
        @if($entries->count() > 0)
            <tr>
                <td colspan="6" style="text-align: right; border: 1px solid #d0d4e0; background-color: #f0f2f8; padding: 8px 10px; font-weight: bold; color: #1a1a2e; font-size: 11px;">TOTAL PENGELUARAN</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: #f0f2f8; padding: 8px 10px; font-weight: bold; color: #d32f2f; font-size: 12px;">Rp {{ number_format($summary['expenseTotal'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        @endif

        {{-- Spacer --}}
        <tr><td colspan="7"></td></tr>

        {{-- Footer --}}
        <tr>
            <td colspan="4" style="color: #999999; font-size: 9px;">Dicetak oleh: {{ auth()->user() ? auth()->user()->name : 'Sistem' }}</td>
            <td colspan="3" style="text-align: right; color: #999999; font-size: 9px;">{{ now()->translatedFormat('d F Y, H:i:s') }}</td>
        </tr>
    </table>
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengeluaran Operasional - Kebab SK</title>
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
                <th style="width:16%; padding:8px 8px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Tanggal &amp; Waktu</th>
                <th style="width:15%; padding:8px 8px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Kategori</th>
                <th style="width:25%; padding:8px 8px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Catatan</th>
                <th style="width:20%; padding:8px 8px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Diinput Oleh</th>
                <th style="width:20%; padding:8px 8px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#c0392b; border:none;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $index => $entry)
                <tr style="border-bottom:1px solid #eee; {{ $loop->even ? 'background-color:#f9f9f9;' : '' }}">
                    <td style="padding:7px 8px; text-align:center; color:#bbb; font-size:10px;">{{ $index + 1 }}</td>
                    <td style="padding:7px 8px; text-align:left; font-weight:600; color:#222;">
                        {{ \Carbon\Carbon::parse($entry->entry_date)->translatedFormat('d M Y') }}
                        @if($entry->created_at)
                            <br><span style="font-size: 9px; font-weight: normal; color: #666;">{{ $entry->created_at->format('H:i') }} WIB</span>
                        @endif
                    </td>
                    <td style="padding:7px 8px; text-align:left; font-weight:600; color:#222;">{{ $entry->source ?: '-' }}</td>
                    <td style="padding:7px 8px; text-align:left; color:#555;">{{ $entry->note ?: '-' }}</td>
                    <td style="padding:7px 8px; text-align:left; color:#555;">{{ $entry->creator->name ?? 'Sistem' }}</td>
                    <td style="padding:7px 8px; text-align:right; color:#c0392b; font-weight:bold;">Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="padding:20px; text-align:center; color:#aaa; font-style:italic;">
                        Tidak ada data laporan pengeluaran operasional pada periode ini.
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

