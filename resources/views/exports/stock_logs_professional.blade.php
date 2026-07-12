@php
    $reportTitle = 'LAPORAN RIWAYAT PERUBAHAN STOK BAHAN BAKU';
    $metaRows = [
        ['Filter Riwayat', $typeLabel, 'Total Riwayat', number_format($summary['total'] ?? 0, 0, ',', '.')],
    ];
    $excelMetaRows = [
        ['Filter Riwayat', $typeLabel],
        ['Total Riwayat', number_format($summary['total'] ?? 0, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 7])

        {{-- Table Header --}}
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">No</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Waktu</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Bahan Baku</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Jenis Mutasi</th>
            <th style="font-weight: bold; text-align: right; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Jumlah</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Sumber</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Catatan</th>
        </tr>

        {{-- Table Body --}}
        @forelse($logs as $index => $log)
            @php $rowBg = $loop->even ? '#f8f9fc' : '#ffffff'; @endphp
            <tr>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #888888;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #1a1a2e;">{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #1a1a2e; font-weight: bold;">{{ $log->ingredient->name ?? '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #333333;">{{ $log->display_type_label ?? '-' }}</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; font-weight: bold;">
                    {{ $log->display_qty_prefix ?? '' }}{{ $log->display_qty ?? '0' }} {{ $log->display_unit ?? '-' }}
                    @if(!empty($log->display_pack_text))
                        ({{ $log->display_pack_text }})
                    @endif
                </td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #555555;">{{ $log->display_source ?? '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #777777;">{{ $log->display_note ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; border: 1px solid #d0d4e0; padding: 14px 10px; color: #999999; font-style: italic;">Tidak ada data riwayat stok pada periode ini.</td>
            </tr>
        @endforelse

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
    <title>Riwayat Perubahan Stok - Kebab SK</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; padding: 28px 32px; }
        table { border-collapse: collapse; }

        .header-bar { background: #1a1a2e; color: #fff; padding: 18px 24px; border-radius: 6px; margin-bottom: 20px; }
        .header-bar .brand { font-size: 18px; font-weight: 700; letter-spacing: 1px; }
        .header-bar .subtitle { font-size: 10px; color: #a0a0b8; margin-top: 2px; }
        .header-bar .report-title { font-size: 13px; font-weight: 600; color: #c8c8e0; margin-top: 8px; letter-spacing: 0.5px; text-transform: uppercase; }

        .info-grid { width: 100%; margin-bottom: 18px; }
        .info-grid td { padding: 0; }
        .info-card { background: #f8f9fc; border: 1px solid #e8eaf0; border-radius: 5px; padding: 12px 16px; }
        .info-card .label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #888; margin-bottom: 4px; }
        .info-card .value { font-size: 14px; font-weight: 700; color: #1a1a2e; }

        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #555; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #1a1a2e; }

        .data-table { width: 100%; border: 1px solid #d8dce6; border-radius: 4px; overflow: hidden; }
        .data-table thead tr { background: #f0f2f8; }
        .data-table th { padding: 9px 12px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #555; border-bottom: 2px solid #d0d4e0; text-align: left; }
        .data-table th:first-child { text-align: center; width: 40px; }
        .data-table th:nth-child(5) { text-align: right; }
        .data-table td { padding: 8px 12px; border-bottom: 1px solid #eef0f4; font-size: 10.5px; color: #333; vertical-align: middle; }
        .data-table td:first-child { text-align: center; color: #aaa; font-size: 10px; }
        .data-table td:nth-child(5) { text-align: right; font-weight: 700; color: #1a1a2e; }
        .data-table tbody tr:nth-child(even) { background: #fafbfd; }
        .data-table tbody tr:hover { background: #f0f4ff; }
        .data-table .cell-name { font-weight: 600; color: #1a1a2e; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e0e0e0; }
        .footer td { font-size: 9px; color: #999; padding-top: 6px; }
    </style>
</head>
<body>

    {{-- ===== HEADER BAR ===== --}}
    <table style="width:100%;">
        <tr>
            <td>
                <div class="header-bar">
                    <table style="width:100%;">
                        <tr>
                            <td style="vertical-align:middle; width:66px;">
                                @if(!empty($logoDataUri))
                                    <img src="{{ $logoDataUri }}" alt="Logo" style="width:52px; height:52px; object-fit:contain; border-radius:6px; background:#fff; padding:4px;">
                                @endif
                            </td>
                            <td style="vertical-align:middle; padding-left:12px;">
                                <div class="brand">KEBAB SK</div>
                                <div class="subtitle">Sistem Manajemen Inventory & Penjualan</div>
                                <div class="report-title">{{ $reportTitle }}</div>
                            </td>
                            <td style="vertical-align:middle; text-align:right;">
                                <div style="font-size:10px; color:#a0a0b8;">Periode</div>
                                <div style="font-size:13px; font-weight:700; color:#fff; margin-top:2px;">{{ $periode ?? $dateDisplay ?? '' }}</div>
                                <div style="font-size:10px; color:#a0a0b8; margin-top:4px;">Cabang: <span style="color:#fff; font-weight:600;">{{ $branchName ?? 'Semua Cabang' }}</span></div>
                                <div style="font-size:10px; color:#a0a0b8; margin-top:4px;">Mode: <span style="color:#fff; font-weight:600;">{{ $typeLabel ?? '-' }}</span></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== SUMMARY CARDS ===== --}}
    <table class="info-grid" cellspacing="0">
        <tr>
            <td style="width:20%; padding:0 4px 8px 0;">
                <div class="info-card">
                    <div class="label">Total Riwayat</div>
                    <div class="value" style="font-size:16px;">{{ number_format($summary['total'] ?? 0, 0, ',', '.') }} Entri</div>
                </div>
            </td>
            <td style="width:20%; padding:0 4px 8px 4px;">
                <div class="info-card" style="border-left: 3px solid #0d8a53;">
                    <div class="label">Restok</div>
                    <div class="value" style="font-size:16px; color: #0d8a53;">{{ number_format($summary['restock'] ?? 0, 0, ',', '.') }} Entri</div>
                </div>
            </td>
            <td style="width:20%; padding:0 4px 8px 4px;">
                <div class="info-card" style="border-left: 3px solid #d32f2f;">
                    <div class="label">Pemakaian</div>
                    <div class="value" style="font-size:16px; color: #d32f2f;">{{ number_format($summary['usage'] ?? 0, 0, ',', '.') }} Entri</div>
                </div>
            </td>
            <td style="width:20%; padding:0 4px 8px 4px;">
                <div class="info-card" style="border-left: 3px solid #0288d1;">
                    <div class="label">Pengembalian</div>
                    <div class="value" style="font-size:16px; color: #0288d1;">{{ number_format($summary['return'] ?? 0, 0, ',', '.') }} Entri</div>
                </div>
            </td>
            <td style="width:20%; padding:0 0 8px 4px;">
                <div class="info-card" style="border-left: 3px solid #f57c00;">
                    <div class="label">Penyesuaian</div>
                    <div class="value" style="font-size:16px; color: #f57c00;">{{ number_format($summary['adjustment'] ?? 0, 0, ',', '.') }} Entri</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== DATA TABLE ===== --}}
    <div style="margin-top: 10px;">
        <div class="section-title">RINCIAN RIWAYAT STOK</div>
        <table class="data-table" cellspacing="0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Waktu</th>
                    <th>Bahan Baku</th>
                    <th>Jenis Mutasi</th>
                    <th>Jumlah</th>
                    <th>Sumber</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ optional($log->created_at)->format('d/m/y H:i') }}</td>
                        <td><div class="cell-name">{{ $log->ingredient->name ?? '-' }}</div></td>
                        <td>{{ $log->display_type_label ?? '-' }}</td>
                        <td>
                            {{ $log->display_qty_prefix ?? '' }}{{ $log->display_qty ?? '0' }} {{ $log->display_unit ?? '-' }}
                            @if(!empty($log->display_pack_text))
                                <span style="color:#666;">({{ $log->display_pack_text }})</span>
                            @endif
                        </td>
                        <td>{{ $log->display_source ?? '-' }}</td>
                        <td style="color:#777;">{{ $log->display_note ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:24px 10px; color:#999; font-style:italic;">Tidak ada data riwayat stok pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===== FOOTER ===== --}}
    <table class="footer" style="width:100%;" cellspacing="0">
        <tr>
            <td style="width:50%;">Dicetak oleh: {{ auth()->user() ? auth()->user()->name : 'Sistem' }}</td>
            <td style="width:50%; text-align:right;">Dicetak pada: {{ now()->translatedFormat('d F Y, H:i:s') }}</td>
        </tr>
    </table>

</body>
</html>
@endif
