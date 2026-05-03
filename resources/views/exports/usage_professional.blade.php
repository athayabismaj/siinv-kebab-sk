@if(isset($isExcel) && $isExcel)
    <table>
        <tr>
            <td colspan="5" style="text-align: center; font-weight: bold; font-size: 16px;">LAPORAN PEMAKAIAN BAHAN</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: center;">Kebab SK</td>
        </tr>
        <tr>
            <td colspan="5"></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Periode Data</td>
            <td colspan="4">: {{ $periode }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Mode Periode</td>
            <td colspan="4">: {{ $periodLabel ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Total Bahan Terpakai</td>
            <td colspan="4">: {{ number_format($summary['ingredients_count'] ?? 0, 0, ',', '.') }} Jenis Bahan</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Jumlah Pemakaian</td>
            <td colspan="4">: {{ number_format($summary['logs_count'] ?? 0, 0, ',', '.') }} Transaksi</td>
        </tr>
        <tr>
            <td colspan="6"></td>
        </tr>
        <tr>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">No</th>
            <th style="font-weight: bold; border: 1px solid #000000;">Nama Bahan</th>
            <th style="font-weight: bold; text-align: right; border: 1px solid #000000;">Total Pemakaian</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Frekuensi Pemakaian</th>
            <th style="font-weight: bold; text-align: center; border: 1px solid #000000;">Terakhir Digunakan</th>
        </tr>
        @forelse($items as $index => $item)
            <tr>
                <td style="text-align: center; border: 1px solid #000000;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ $item->ingredient_name }}</td>
                <td style="text-align: right; border: 1px solid #000000;">
                    @php
                        $qtyLabel = \App\Support\UsageQuantityFormatter::parts(
                            (float) $item->total_quantity,
                            (string) ($item->base_unit ?? ''),
                            (string) ($item->display_unit ?? ''),
                            (int) ($item->pack_size ?? 1)
                        )['quantity'];
                    @endphp
                    {{ $qtyLabel }}
                </td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $item->usage_count }}x</td>
                <td style="text-align: center; border: 1px solid #000000;">{{ $item->last_used_at ? \Carbon\Carbon::parse($item->last_used_at)->format('d/m/Y H:i') : '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center; border: 1px solid #000000;">Tidak ada data pemakaian bahan pada periode ini.</td>
            </tr>
        @endforelse
    </table>
@else
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pemakaian Bahan - Kebab SK</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; background: #fff; padding: 30px 35px; }
        table { border-collapse: collapse; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div style="text-align: center; margin-bottom: 12px;">
        <div style="font-size: 20px; font-weight: bold; color: #111; text-transform: uppercase; letter-spacing: 2px;">LAPORAN PEMAKAIAN BAHAN</div>
        <div style="font-size: 11px; color: #666; letter-spacing: 0.5px; margin-top: 4px; text-transform: uppercase;">Kebab SK</div>
    </div>
    <div style="border-top: 2px solid #111; margin-bottom: 2px;"></div>
    <div style="border-top: 1px solid #111; margin-bottom: 14px;"></div>

    {{-- META INFO --}}
    <table style="width: 100%; margin-bottom: 16px;">
        <tr>
            <td style="width: 140px; color: #555; padding: 2.5px 0;">Periode Data</td>
            <td style="width: 8px;  color: #555; padding: 2.5px 0;">:</td>
            <td style="color: #222; padding: 2.5px 0;">{{ $periode }}</td>
        </tr>
        <tr>
            <td style="color: #555; padding: 2.5px 0;">Mode Periode</td>
            <td style="color: #555; padding: 2.5px 0;">:</td>
            <td style="color: #222; padding: 2.5px 0;">{{ $periodLabel ?? '-' }}</td>
        </tr>
        <tr>
            <td style="color: #555; padding: 2.5px 0;">Total Bahan Terpakai</td>
            <td style="color: #555; padding: 2.5px 0;">:</td>
            <td style="color: #222; padding: 2.5px 0;">{{ number_format($summary['ingredients_count'] ?? 0, 0, ',', '.') }} Jenis Bahan</td>
        </tr>
        <tr>
            <td style="color: #555; padding: 2.5px 0;">Jumlah Pemakaian</td>
            <td style="color: #555; padding: 2.5px 0;">:</td>
            <td style="color: #222; padding: 2.5px 0;">{{ number_format($summary['logs_count'] ?? 0, 0, ',', '.') }} Transaksi</td>
        </tr>
    </table>

    {{-- DATA TABLE --}}
    <table style="width: 100%;">
        <thead>
            <tr style="background-color: #f0f0f0; border-top: 1px solid #bbb; border-bottom: 1px solid #bbb;">
                <th style="width:5%;  padding:8px 10px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">No</th>
                <th style="width:40%; padding:8px 10px; text-align:left;   font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Nama Bahan</th>
                <th style="width:25%; padding:8px 10px; text-align:right;  font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Total Pemakaian</th>
                <th style="width:15%; padding:8px 10px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Frekuensi</th>
                <th style="width:15%; padding:8px 10px; text-align:center; font-size:10px; font-weight:bold; text-transform:uppercase; color:#333; border:none;">Terakhir Digunakan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr style="border-bottom:1px solid #eee; {{ $loop->even ? 'background-color:#f9f9f9;' : '' }}">
                    <td style="padding:7px 10px; text-align:center; color:#bbb; font-size:10px;">{{ $index + 1 }}</td>
                    <td style="padding:7px 10px; text-align:left; font-weight:600; color:#222;">{{ $item->ingredient_name }}</td>
                    <td style="padding:7px 10px; text-align:right; color:#222;">
                        @php
                            $qtyLabel = \App\Support\UsageQuantityFormatter::parts(
                                (float) $item->total_quantity,
                                (string) ($item->base_unit ?? ''),
                                (string) ($item->display_unit ?? ''),
                                (int) ($item->pack_size ?? 1)
                            )['quantity'];
                        @endphp
                        {{ $qtyLabel }}
                    </td>
                    <td style="padding:7px 10px; text-align:center; color:#555;">{{ $item->usage_count }}&times;</td>
                    <td style="padding:7px 10px; text-align:center; color:#888; font-size:10px;">{{ $item->last_used_at ? \Carbon\Carbon::parse($item->last_used_at)->format('d/m/y H:i') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding:20px; text-align:center; color:#aaa; font-style:italic;">
                        Tidak ada data pemakaian pada periode ini.
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

