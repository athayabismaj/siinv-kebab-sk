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

        {{-- Table Header --}}
        <tr>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Tanggal &amp; Waktu</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Kode Transaksi</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Kasir</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Pembayaran</th>
            <th style="font-weight: bold; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Status</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Item</th>
            <th style="font-weight: bold; text-align: right; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Total</th>
        </tr>

        {{-- Table Body --}}
        @forelse($transactions as $index => $trx)
            @php
                $statusRaw = strtolower(trim((string) ($trx->status ?? 'success')));
                $isVoid = $statusRaw === 'void';
                $isSuccess = $statusRaw === 'success';
                $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount;
                $voidReasonLabel = match (strtolower((string) $trx->void_reason)) {
                    'restock' => 'Kembali Stok',
                    'waste' => 'Waste',
                    default => null,
                };
                $statusLabel = $isVoid ? 'Pembatalan' : ($isSuccess ? ($isPaid ? 'Lunas' : 'Kurang') : strtoupper(str_replace('_', ' ', (string) $trx->status)));
                $exportStatusLabel = $isVoid && $voidReasonLabel ? $statusLabel . ' - ' . $voidReasonLabel : $statusLabel;
                $rowBg = $loop->even ? '#f8f9fc' : '#ffffff';
            @endphp
            <tr>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #1a1a2e; font-weight: bold;">{{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d M Y') }} {{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #333333;">{{ $trx->transaction_code }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #555555;">{{ $trx->user->name ?? '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #555555;">{{ $trx->paymentMethod->name ?? '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #555555;">{{ $exportStatusLabel }}</td>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #555555;">{{ $trx->details_count }}</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; padding: 6px 10px; color: #0d8a53; font-weight: bold;">Rp {{ number_format((float) $trx->total_amount, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; border: 1px solid #d0d4e0; padding: 14px 10px; color: #999999; font-style: italic;">Tidak ada riwayat transaksi pada periode ini.</td>
            </tr>
        @endforelse

        {{-- Total Row --}}
        @if(count($transactions) > 0)
            <tr>
                <td colspan="6" style="text-align: right; border: 1px solid #d0d4e0; background-color: #f0f2f8; padding: 8px 10px; font-weight: bold; color: #1a1a2e; font-size: 11px;">TOTAL OMZET</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: #f0f2f8; padding: 8px 10px; font-weight: bold; color: #0d8a53; font-size: 12px;">Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}</td>
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
    <title>Laporan Riwayat Transaksi - Kebab SK</title>
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
        .data-table th:last-child { text-align: right; }
        .data-table td { padding: 8px 12px; border-bottom: 1px solid #eef0f4; font-size: 10.5px; color: #333; vertical-align: top; }
        .data-table td:last-child { text-align: right; }
        .data-table tbody tr:nth-child(even) { background: #fafbfd; }
        .data-table tbody tr:hover { background: #f0f4ff; }
        .data-table .cell-date { font-weight: 600; color: #1a1a2e; }
        .data-table .cell-time { font-size: 9px; color: #888; margin-top: 1px; }
        .data-table .cell-source { font-weight: 600; color: #333; }
        .data-table .cell-note { color: #666; font-size: 10px; line-height: 1.4; max-width: 220px; }
        .data-table .cell-amount { font-weight: 700; white-space: nowrap; }
        .data-table .cell-amount.green { color: #0d8a53; }
        .data-table .total-row { background: #f0f2f8; border-top: 2px solid #d0d4e0; }
        .data-table .total-row td { font-weight: 700; font-size: 11px; color: #1a1a2e; padding: 10px 12px; }

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
    <table class="info-grid" cellspacing="0">
        <tr>
            <td style="width:33.33%; padding:0 4px 8px 0;">
                <div class="info-card">
                    <div class="label">Jumlah Transaksi</div>
                    <div class="value" style="font-size:16px;">{{ number_format($summary['total_transactions'] ?? 0, 0, ',', '.') }} Transaksi</div>
                </div>
            </td>
            <td style="width:33.33%; padding:0 4px 8px 4px;">
                <div class="info-card" style="border-left: 3px solid #0d8a53;">
                    <div class="label">Total Omzet</div>
                    <div class="value green" style="font-size:16px;">Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width:33.33%; padding:0 0 8px 4px;">
                <div class="info-card" style="border-left: 3px solid #1565c0;">
                    <div class="label">Rata-rata Transaksi</div>
                    <div class="value blue" style="font-size:16px;">Rp {{ number_format($summary['avg_transaction'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== DATA TABLE ===== --}}
    <div style="margin-top: 10px;">
        <div class="section-title">RINCIAN TRANSAKSI</div>
        <table class="data-table" cellspacing="0">
            <thead>
                <tr>
                    <th style="width: 15%;">Tanggal & Waktu</th>
                    <th style="width: 18%;">Kode</th>
                    <th style="width: 15%;">Kasir</th>
                    <th style="width: 15%;">Pembayaran</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 8%; text-align: center;">Item</th>
                    <th style="width: 14%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $trx)
                    @php
                        $statusRaw = strtolower(trim((string) ($trx->status ?? 'success')));
                        $isVoid = $statusRaw === 'void';
                        $isSuccess = $statusRaw === 'success';
                        $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount;
                        $voidReasonLabel = match (strtolower((string) $trx->void_reason)) {
                            'restock' => 'Kembali Stok',
                            'waste' => 'Waste',
                            default => null,
                        };
                        $statusLabel = $isVoid ? 'Pembatalan' : ($isSuccess ? ($isPaid ? 'Lunas' : 'Kurang') : strtoupper(str_replace('_', ' ', (string) $trx->status)));
                        $statusColor = $isVoid ? '#d97706' : ($isPaid ? '#10b981' : '#ef4444');
                        $exportStatusLabel = $isVoid && $voidReasonLabel ? $statusLabel . ' - ' . $voidReasonLabel : $statusLabel;
                    @endphp
                    <tr>
                        <td>
                            <div class="cell-date">{{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d M Y') }}</div>
                            <div class="cell-time">{{ \Carbon\Carbon::parse($trx->created_at)->format('H:i') }}</div>
                        </td>
                        <td><div class="cell-source">{{ $trx->transaction_code }}</div></td>
                        <td>{{ $trx->user->name ?? '-' }}</td>
                        <td>{{ $trx->paymentMethod->name ?? '-' }}</td>
                        <td style="color: {{ $statusColor }}; font-weight: 600;">{{ $exportStatusLabel }}</td>
                        <td style="text-align: center;">{{ $trx->details_count }}</td>
                        <td class="cell-amount green">Rp {{ number_format((float) $trx->total_amount, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; padding:24px 10px; color:#999; font-style:italic;">Tidak ada riwayat transaksi pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            
            @if(count($transactions) > 0)
                <tr class="total-row">
                    <td colspan="6" style="text-align:right; border-right:1px solid #d0d4e0;">TOTAL OMZET</td>
                    <td class="cell-amount green" style="font-size:12px;">Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}</td>
                </tr>
            @endif
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
