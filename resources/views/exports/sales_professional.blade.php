@php
    $reportTitle = 'LAPORAN PENJUALAN ' . strtoupper($type === 'daily' ? 'HARIAN' : ($type === 'weekly' ? 'MINGGUAN' : 'BULANAN'));
    $branchName = \App\Support\BranchScope::requestBranchId((int) request('branch_id')) 
        ? \App\Models\Branch::find((int) request('branch_id'))->name 
        : 'Semua Cabang';
    $metaRows = [
        ['Cabang', $branchName, 'Total Omzet', 'Rp ' . number_format($totalRevenue ?? 0, 0, ',', '.')],
        ['Jumlah Transaksi', number_format($totalTransactions ?? 0, 0, ',', '.') . ' Transaksi', 'Rata-rata Transaksi', 'Rp ' . number_format($avgTransaction ?? 0, 0, ',', '.')],
    ];
    $excelMetaRows = [
        ['Cabang', $branchName],
        ['Total Omzet', 'Rp ' . number_format($totalRevenue ?? 0, 0, ',', '.')],
        ['Jumlah Transaksi', number_format($totalTransactions ?? 0, 0, ',', '.') . ' Transaksi'],
        ['Rata-rata Transaksi', 'Rp ' . number_format($avgTransaction ?? 0, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 7])
        
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

        {{-- Spacer --}}
        <tr><td colspan="7"></td></tr>
        
        <tr>
            <th colspan="7" style="font-weight: bold; text-align: left; font-size: 14px; padding: 10px 0;">Riwayat Transaksi Penjualan</th>
        </tr>
        <tr>
            <th style="font-weight: bold; text-align: left; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Kode / Resi</th>
            <th style="font-weight: bold; text-align: left; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Waktu</th>
            <th style="font-weight: bold; text-align: left; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Kasir</th>
            <th style="font-weight: bold; text-align: left; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Metode Pembayaran</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Jumlah Item</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Total Transaksi</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000; font-size: 12px; padding: 8px 10px;">Status</th>
        </tr>
        @forelse($salesTransactions ?? [] as $transaction)
            @php
                $statusRaw = strtolower((string) ($transaction->status ?? 'success'));
                $isCanceled = $statusRaw === 'void';
                $statusLabel = $isCanceled ? 'Dibatalkan' : 'Berhasil';
                $paymentMethodRaw = trim((string) ($transaction->payment_method_name ?? ''));
                $paymentMethodLabel = str_contains(strtolower($paymentMethodRaw), 'cash') || str_contains(strtolower($paymentMethodRaw), 'tunai')
                    ? 'Tunai'
                    : ($paymentMethodRaw !== '' ? $paymentMethodRaw : '-');
            @endphp
            <tr>
                <td style="border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $transaction->transaction_code ?? '-' }}</td>
                <td style="border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ \Carbon\Carbon::parse($transaction->created_at)->translatedFormat('d M Y, H:i') }}</td>
                <td style="border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $transaction->cashier_name ?? '-' }}</td>
                <td style="border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $paymentMethodLabel }}</td>
                <td style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ number_format((float) $transaction->item_count, 0, ',', '.') }}</td>
                <td style="text-align: right; border: 1px solid #000000; font-size: 11px; padding: 7px 10px;">{{ $transaction->total_amount }}</td>
                <td style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 7px 10px; color: {{ $isCanceled ? '#d32f2f' : '#0d8a53' }}; font-weight: bold;">{{ $statusLabel }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; border: 1px solid #000000; font-size: 11px; padding: 12px 10px;">Belum ada transaksi pada periode ini.</td>
            </tr>
        @endforelse
    </table>
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} - Kebab SK</title>
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
        .info-card .value.blue { color: #1565c0; }
        .info-card .value.orange { color: #e65100; }

        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #555; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #1a1a2e; }

        .data-table { width: 100%; border: 1px solid #d8dce6; border-radius: 4px; overflow: hidden; }
        .data-table thead tr { background: #f0f2f8; }
        .data-table th { padding: 9px 12px; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #555; border-bottom: 2px solid #d0d4e0; text-align: left; }
        .data-table th.center { text-align: center; }
        .data-table th.right { text-align: right; }
        .data-table td { padding: 8px 12px; border-bottom: 1px solid #eef0f4; font-size: 10.5px; color: #333; vertical-align: top; }
        .data-table td.center { text-align: center; }
        .data-table td.right { text-align: right; }
        .data-table tbody tr:nth-child(even) { background: #fafbfd; }
        .data-table tbody tr:hover { background: #f0f4ff; }
        .data-table .cell-date { font-weight: 600; color: #1a1a2e; }
        .data-table .cell-amount { font-weight: 700; color: #1565c0; white-space: nowrap; }

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
                                @php
                                    $branchName = \App\Support\BranchScope::requestBranchId((int) request('branch_id')) 
                                        ? \App\Models\Branch::find((int) request('branch_id'))->name 
                                        : 'Semua Cabang';
                                @endphp
                                <div style="font-size:10px; color:#a0a0b8; margin-top:4px;">Cabang: <span style="color:#fff; font-weight:600;">{{ $branchName }}</span></div>
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
                    <div class="label">Total Omzet</div>
                    <div class="value blue">Rp {{ number_format($totalRevenue ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width:33.33%; padding:0 4px 8px 4px;">
                <div class="info-card">
                    <div class="label">Jumlah Transaksi</div>
                    <div class="value green">{{ number_format($totalTransactions ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width:33.33%; padding:0 0 8px 4px;">
                <div class="info-card">
                    <div class="label">Rata-rata Transaksi</div>
                    <div class="value orange">Rp {{ number_format($avgTransaction ?? 0, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ===== DATA TABLE ===== --}}
    <div class="section-title">
        @if($type === 'daily') Rincian Penjualan Menu @else Rincian Penjualan Harian @endif
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                @if($type === 'daily')
                    <th>No</th>
                    <th style="width:50%;">Nama Menu</th>
                    <th class="center" style="width:20%;">Jumlah Terjual</th>
                    <th class="right" style="width:25%;">Total Penjualan</th>
                @else
                    <th>No</th>
                    <th style="width:50%;">Tanggal</th>
                    <th class="center" style="width:20%;">Jumlah Transaksi</th>
                    <th class="right" style="width:25%;">Total Omzet</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @if($type === 'daily')
                @forelse($contributions ?? [] as $index => $row)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td><div class="cell-date">{{ $row->menu_name }}</div></td>
                        <td class="center">{{ number_format($row->total_qty) }}</td>
                        <td class="right"><span class="cell-amount">Rp {{ number_format($row->total_sales, 0, ',', '.') }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:28px; text-align:center; color:#aaa; font-style:italic;">
                            Belum ada transaksi pada periode ini.
                        </td>
                    </tr>
                @endforelse
            @elseif($type === 'weekly')
                @forelse($weeklyBreakdown ?? [] as $index => $row)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td><div class="cell-date">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</div></td>
                        <td class="center">{{ number_format($row->trx_count) }}</td>
                        <td class="right"><span class="cell-amount">Rp {{ number_format($row->revenue, 0, ',', '.') }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:28px; text-align:center; color:#aaa; font-style:italic;">
                            Tidak ada data untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            @else
                @forelse($dailyBreakdown ?? [] as $index => $row)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td><div class="cell-date">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</div></td>
                        <td class="center">{{ number_format($row->trx_count) }}</td>
                        <td class="right"><span class="cell-amount">Rp {{ number_format($row->revenue, 0, ',', '.') }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding:28px; text-align:center; color:#aaa; font-style:italic;">
                            Tidak ada data untuk periode ini.
                        </td>
                    </tr>
                @endforelse
            @endif
        </tbody>
    </table>

    {{-- ===== RIWAYAT TRANSAKSI PENJUALAN ===== --}}
    <div style="margin-top: 30px;">
        <div class="section-title">Riwayat Transaksi Penjualan</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:14%;">Kode / Resi</th>
                    <th style="width:16%;">Waktu</th>
                    <th style="width:15%;">Kasir</th>
                    <th style="width:15%;">Metode</th>
                    <th class="center" style="width:10%;">Item</th>
                    <th class="right" style="width:18%;">Total Transaksi</th>
                    <th class="center" style="width:12%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesTransactions ?? [] as $transaction)
                    @php
                        $statusRaw = strtolower((string) ($transaction->status ?? 'success'));
                        $isCanceled = $statusRaw === 'void';
                        $statusLabel = $isCanceled ? 'Dibatalkan' : 'Berhasil';
                        $paymentMethodRaw = trim((string) ($transaction->payment_method_name ?? ''));
                        $paymentMethodLabel = str_contains(strtolower($paymentMethodRaw), 'cash') || str_contains(strtolower($paymentMethodRaw), 'tunai')
                            ? 'Tunai'
                            : ($paymentMethodRaw !== '' ? $paymentMethodRaw : '-');
                    @endphp
                    <tr>
                        <td><div class="cell-date">{{ $transaction->transaction_code ?? '-' }}</div></td>
                        <td>
                            <div class="cell-date">{{ \Carbon\Carbon::parse($transaction->created_at)->translatedFormat('d M Y') }}</div>
                            <div style="font-size: 9px; color: #888; margin-top: 1px;">{{ \Carbon\Carbon::parse($transaction->created_at)->format('H:i') }} WIB</div>
                        </td>
                        <td>{{ $transaction->cashier_name ?? '-' }}</td>
                        <td><span style="font-weight: 600; color: #333;">{{ $paymentMethodLabel }}</span></td>
                        <td class="center">{{ number_format((float) $transaction->item_count, 0, ',', '.') }}</td>
                        <td class="right"><span class="cell-amount">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</span></td>
                        <td class="center">
                            @if($isCanceled)
                                <span style="color: #d32f2f; font-weight: bold; font-size: 10px; padding: 2px 6px; background: #fef5f5; border-radius: 4px; border: 1px solid #f5c6c6;">{{ $statusLabel }}</span>
                            @else
                                <span style="color: #0d8a53; font-weight: bold; font-size: 10px; padding: 2px 6px; background: #f0faf5; border-radius: 4px; border: 1px solid #b8e6cd;">{{ $statusLabel }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:28px; text-align:center; color:#aaa; font-style:italic;">
                            Belum ada transaksi pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===== FOOTER ===== --}}
    <table class="footer" style="width:100%;">
        <tr>
            <td>
                Dicetak oleh: <strong style="color:#444;">{{ auth()->user() ? auth()->user()->name : 'Sistem' }}</strong>
            </td>
            <td style="text-align:right;">
                {{ now()->translatedFormat('d F Y, H:i:s') }}
            </td>
        </tr>
    </table>

</body>
</html>
@endif
