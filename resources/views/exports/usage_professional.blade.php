@php
    $reportTitle = 'LAPORAN PEMAKAIAN BAHAN';
    $metaRows = [
        ['Total Bahan Terpakai', number_format($summary['ingredients_count'] ?? 0, 0, ',', '.') . ' Jenis Bahan', 'Jumlah Pemakaian', number_format($summary['logs_count'] ?? 0, 0, ',', '.') . ' kali'],
    ];
    $excelMetaRows = [
        ['Total Bahan Terpakai', number_format($summary['ingredients_count'] ?? 0, 0, ',', '.') . ' Jenis Bahan'],
        ['Jumlah Pemakaian', number_format($summary['logs_count'] ?? 0, 0, ',', '.') . ' kali'],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 5])

        {{-- Table Header --}}
        <tr>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">No</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Nama Bahan Baku</th>
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
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pemakaian Bahan - Kebab SK</title>
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
        .info-card .value.green { color: #0d8a53; }
        .info-card .value.red { color: #d32f2f; }
        .info-card .value.blue { color: #1565c0; }

        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #555; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #1a1a2e; }

        .data-table { width: 100%; border: 1px solid #d8dce6; border-radius: 4px; overflow: hidden; }
        .data-table thead tr { background: #f0f2f8; }
        .data-table th { padding: 9px 12px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #555; border-bottom: 2px solid #d0d4e0; text-align: left; }
        .data-table th:first-child { text-align: center; width: 40px; }
        .data-table th:nth-child(3) { text-align: right; }
        .data-table th:nth-child(4) { text-align: center; }
        .data-table th:last-child { text-align: center; }
        .data-table td { padding: 8px 12px; border-bottom: 1px solid #eef0f4; font-size: 10.5px; color: #333; vertical-align: middle; }
        .data-table td:first-child { text-align: center; color: #aaa; font-size: 10px; }
        .data-table td:nth-child(3) { text-align: right; font-weight: 700; color: #0d8a53; }
        .data-table td:nth-child(4) { text-align: center; }
        .data-table td:last-child { text-align: center; font-size: 9.5px; color: #888; }
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
                                <div style="font-size:13px; font-weight:700; color:#fff; margin-top:2px;">{{ $periode }}</div>
                                <div style="font-size:10px; color:#a0a0b8; margin-top:4px;">Cabang: <span style="color:#fff; font-weight:600;">{{ $branchName ?? 'Semua Cabang' }}</span></div>
                                <div style="font-size:10px; color:#a0a0b8; margin-top:4px;">Mode: <span style="color:#fff; font-weight:600;">{{ $periodLabel ?? '-' }}</span></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== SUMMARY CARDS ===== --}}
    @php
        $unitColors = ['#0d8a53', '#f57c00', '#7b1fa2', '#0097a7', '#c2185b'];
        $dynamicUnits = $summary['by_unit'] ?? [];
        $totalCards = 2 + count($dynamicUnits);
        $tdWidth = (100 / $totalCards) . '%';
    @endphp
    <table class="info-grid" cellspacing="0">
        <tr>
            <td style="width:{{ $tdWidth }}; padding:0 2px 8px 0;">
                <div class="info-card">
                    <div class="label">Total Bahan Terpakai</div>
                    <div class="value" style="font-size:16px;">{{ number_format($summary['ingredients_count'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width:{{ $tdWidth }}; padding:0 2px 8px 2px;">
                <div class="info-card" style="border-left: 3px solid #1565c0;">
                    <div class="label">Jumlah Pemakaian</div>
                    <div class="value blue" style="font-size:16px;">{{ number_format($summary['logs_count'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            @php $unitIdx = 0; @endphp
            @foreach($dynamicUnits as $unitData)
                @php $color = $unitColors[$unitIdx % count($unitColors)]; $unitIdx++; @endphp
                <td style="width:{{ $tdWidth }}; padding:0 2px 8px 2px;">
                    <div class="info-card" style="border-left: 3px solid {{ $color }};">
                        <div class="label">Total {{ $unitData['unit'] }}</div>
                        <div class="value" style="font-size:16px; color: {{ $color }};">{{ number_format($unitData['total'], 2, ',', '.') }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- ===== DATA TABLE ===== --}}
    <div style="margin-top: 10px;">
        <div class="section-title">RINCIAN PEMAKAIAN BAHAN</div>
        <table class="data-table" cellspacing="0">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Bahan</th>
                    <th>Total Pemakaian</th>
                    <th>Jumlah Pemakaian</th>
                    <th>Terakhir Dipakai</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><div class="cell-name">{{ $item->ingredient_name }}</div></td>
                        <td>
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
                        <td>{{ number_format($item->usage_count, 0, ',', '.') }} kali</td>
                        <td>{{ $item->last_used_at ? \Carbon\Carbon::parse($item->last_used_at)->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding:24px 10px; color:#999; font-style:italic;">Tidak ada data pemakaian pada periode ini.</td>
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
