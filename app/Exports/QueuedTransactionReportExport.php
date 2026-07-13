<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QueuedTransactionReportExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize
{
    public function __construct(private readonly Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Tanggal & Waktu',
            'Kode Transaksi',
            'Kasir',
            'Metode Pembayaran',
            'Status',
            'Jumlah Item',
            'Total Transaksi',
        ];
    }

    /**
     * @param Transaction $transaction
     */
    public function map($transaction): array
    {
        return [
            $transaction->created_at?->translatedFormat('d M Y') . ' ' . $transaction->created_at?->format('H:i'),
            $transaction->transaction_code,
            $transaction->user?->name ?? '-',
            $this->paymentLabel($transaction->paymentMethod?->name),
            $this->statusLabel($transaction->status),
            $transaction->details_count,
            (float) $transaction->total_amount,
        ];
    }

    public function chunkSize(): int
    {
        return 250;
    }

    private function paymentLabel(?string $name): string
    {
        return in_array(strtolower(trim((string) $name)), ['cash', 'tunai'], true)
            ? 'Tunai'
            : ((string) $name ?: '-');
    }

    private function statusLabel(?string $status): string
    {
        return strtoupper(trim((string) $status)) === 'SUCCESS'
            ? 'Berhasil'
            : (strtoupper(trim((string) $status)) === 'VOID'
                ? 'Dibatalkan'
                : ucwords(str_replace('_', ' ', strtolower((string) $status))));
    }
}
