@php
    $reportTitle = 'LAPORAN PENJUALAN ' . strtoupper($type === 'daily' ? 'HARIAN' : ($type === 'weekly' ? 'MINGGUAN' : 'BULANAN'));
    $resolvedBranchName = isset($branch) && $branch ? $branch->name : ($branchName ?? 'Semua Cabang');
    $metaRows = [
        ['Cabang', $resolvedBranchName, 'Total Omzet', 'Rp ' . number_format($totalRevenue ?? 0, 0, ',', '.')],
        ['Jumlah Transaksi', number_format($totalTransactions ?? 0, 0, ',', '.') . ' Transaksi', 'Rata-rata Transaksi', 'Rp ' . number_format($avgTransaction ?? 0, 0, ',', '.')],
    ];
@endphp

@if(isset($isExcel) && $isExcel)
    <table>
        @include('exports.partials.report_header_excel', ['columns' => 7])
        
        @if($type === 'daily')
            <tr>
                <th colspan="2" style="font-weight: bold; text-align: left; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Nama Menu</th>
                <th colspan="2" style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Jumlah Terjual</th>
                <th colspan="3" style="font-weight: bold; text-align: right; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Total Penjualan</th>
            </tr>
            @forelse($contributions ?? [] as $row)
                @php $rowBg = $loop->even ? '#f8f9fc' : '#ffffff'; @endphp
                <tr>
                    <td colspan="2" style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ $row->menu_name }}</td>
                    <td colspan="2" style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ $row->total_qty }}</td>
                    <td colspan="3" style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px; font-weight: bold; color: #0d8a53;">{{ $row->total_sales }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; border: 1px solid #d0d4e0; font-size: 11px; padding: 12px 10px; color: #999999; font-style: italic;">Belum ada transaksi pada periode ini.</td>
                </tr>
            @endforelse
        @elseif($type === 'weekly')
            <tr>
                <th colspan="2" style="font-weight: bold; text-align: left; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Tanggal</th>
                <th colspan="2" style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Transaksi</th>
                <th colspan="3" style="font-weight: bold; text-align: right; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Omzet</th>
            </tr>
            @forelse($weeklyBreakdown ?? [] as $row)
                @php $rowBg = $loop->even ? '#f8f9fc' : '#ffffff'; @endphp
                <tr>
                    <td colspan="2" style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</td>
                    <td colspan="2" style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ $row->trx_count }}</td>
                    <td colspan="3" style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px; font-weight: bold; color: #0d8a53;">{{ $row->revenue }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; border: 1px solid #d0d4e0; font-size: 11px; padding: 12px 10px; color: #999999; font-style: italic;">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        @else
            <tr>
                <th colspan="2" style="font-weight: bold; text-align: left; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Tanggal</th>
                <th colspan="2" style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Transaksi</th>
                <th colspan="3" style="font-weight: bold; text-align: right; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Omzet</th>
            </tr>
            @forelse($dailyBreakdown ?? [] as $row)
                @php $rowBg = $loop->even ? '#f8f9fc' : '#ffffff'; @endphp
                <tr>
                    <td colspan="2" style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</td>
                    <td colspan="2" style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ $row->trx_count }}</td>
                    <td colspan="3" style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px; font-weight: bold; color: #0d8a53;">{{ $row->revenue }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; border: 1px solid #d0d4e0; font-size: 11px; padding: 12px 10px; color: #999999; font-style: italic;">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        @endif

        {{-- Spacer --}}
        <tr><td colspan="7"></td></tr>
        
        <tr>
            <th colspan="7" style="font-weight: bold; text-align: left; font-size: 12px; padding: 10px 0; color: #1a1a2e; border-bottom: 2px solid #1a1a2e;">Riwayat Transaksi Penjualan</th>
        </tr>
        <tr>
            <th style="font-weight: bold; text-align: left; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Kode / Resi</th>
            <th style="font-weight: bold; text-align: left; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Waktu</th>
            <th style="font-weight: bold; text-align: left; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Kasir</th>
            <th style="font-weight: bold; text-align: left; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Metode Pembayaran</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Jumlah Item</th>
            <th style="font-weight: bold; text-align: right; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Total Transaksi</th>
            <th style="font-weight: bold; text-align: center; background-color: #1a1a2e; color: #ffffff; border: 1px solid #1a1a2e; padding: 8px 10px; font-size: 11px;">Status</th>
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
                $rowBg = $loop->even ? '#f8f9fc' : '#ffffff';
            @endphp
            <tr>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ $transaction->transaction_code ?? '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ \Carbon\Carbon::parse($transaction->created_at)->translatedFormat('d M Y, H:i') }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ $transaction->cashier_name ?? '-' }}</td>
                <td style="border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ $paymentMethodLabel }}</td>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px;">{{ number_format((float) $transaction->item_count, 0, ',', '.') }}</td>
                <td style="text-align: right; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px; font-weight: bold; color: #0d8a53;">{{ $transaction->total_amount }}</td>
                <td style="text-align: center; border: 1px solid #d0d4e0; background-color: {{ $rowBg }}; font-size: 11px; padding: 7px 10px; color: {{ $isCanceled ? '#d32f2f' : '#0d8a53' }}; font-weight: bold;">{{ $statusLabel }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; border: 1px solid #d0d4e0; font-size: 11px; padding: 12px 10px; color: #999999; font-style: italic;">Belum ada transaksi pada periode ini.</td>
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
    <title>{{ $reportTitle }} - Kebab SK</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; padding: 28px 32px; }
        table { border-collapse: collapse; }

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
        .data-table .cell-amount.green { color: #0d8a53; }

        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #e0e0e0; }
        .footer td { font-size: 9px; color: #999; padding-top: 6px; }
    </style>
</head>
<body>

    @include('exports.partials.report_header_html')

    {{-- ===== DATA TABLE ===== --}}
    <div class="section-title" style="margin-top: 15px;">
        @if($type === 'daily') Rincian Penjualan Menu @else Rincian Penjualan Harian @endif
    </div>
    
    <table class="data-table" style="margin-bottom: 25px;">
        <thead>
            <tr>
                @if($type === 'daily')
                    <th style="width:5%;" class="center">No</th>
                    <th style="width:50%;">Nama Menu</th>
                    <th class="center" style="width:20%;">Jumlah Terjual</th>
                    <th class="right" style="width:25%;">Total Penjualan</th>
                @else
                    <th style="width:5%;" class="center">No</th>
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
                        <td class="center" style="color: #888;">{{ $index + 1 }}</td>
                        <td><div class="cell-date">{{ $row->menu_name }}</div></td>
                        <td class="center">{{ number_format($row->total_qty) }}</td>
                        <td class="right"><span class="cell-amount green">Rp {{ number_format($row->total_sales, 0, ',', '.') }}</span></td>
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
                        <td class="center" style="color: #888;">{{ $index + 1 }}</td>
                        <td><div class="cell-date">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</div></td>
                        <td class="center">{{ number_format($row->trx_count) }}</td>
                        <td class="right"><span class="cell-amount green">Rp {{ number_format($row->revenue, 0, ',', '.') }}</span></td>
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
                        <td class="center" style="color: #888;">{{ $index + 1 }}</td>
                        <td><div class="cell-date">{{ \Carbon\Carbon::parse($row->date)->translatedFormat('d F Y') }}</div></td>
                        <td class="center">{{ number_format($row->trx_count) }}</td>
                        <td class="right"><span class="cell-amount green">Rp {{ number_format($row->revenue, 0, ',', '.') }}</span></td>
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
                    <td class="right"><span class="cell-amount green">Rp {{ number_format((float) $transaction->total_amount, 0, ',', '.') }}</span></td>
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
