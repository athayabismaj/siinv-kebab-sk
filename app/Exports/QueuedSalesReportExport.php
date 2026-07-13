<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class QueuedSalesReportExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithCustomStartCell, WithEvents, WithColumnWidths
{
    public function __construct(
        private readonly Builder $query,
        private readonly string $period,
        private readonly string $periodType,
        private readonly string $branchName,
        private readonly array $summary,
        private readonly array $breakdown,
    ) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function startCell(): string
    {
        return 'A' . $this->historyHeaderRow();
    }

    public function headings(): array
    {
        return ['Kode / Resi', 'Waktu', 'Kasir', 'Metode Pembayaran', 'Jumlah Item', 'Total Transaksi', 'Status'];
    }

    public function map($transaction): array
    {
        $payment = trim((string) ($transaction->paymentMethod?->name ?? ''));
        $payment = str_contains(strtolower($payment), 'cash') || str_contains(strtolower($payment), 'tunai')
            ? 'Tunai'
            : ($payment !== '' ? $payment : '-');
        $status = strtolower((string) $transaction->status) === 'void' ? 'Dibatalkan' : 'Berhasil';

        return [
            $transaction->transaction_code ?: '-',
            Carbon::parse($transaction->created_at)->translatedFormat('d M Y, H:i'),
            $transaction->user?->name ?? '-',
            $payment,
            number_format((int) $transaction->details_count, 0, ',', '.'),
            'Rp ' . number_format((float) $transaction->total_amount, 0, ',', '.'),
            $status,
        ];
    }

    public function chunkSize(): int
    {
        return 250;
    }

    public function columnWidths(): array
    {
        return ['A' => 28, 'B' => 23, 'C' => 24, 'D' => 20, 'E' => 15, 'F' => 22, 'G' => 16];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->setCellValue('A1', 'KEBAB SK');
                $sheet->setCellValue('A2', 'LAPORAN PENJUALAN ' . $this->periodType);
                $sheet->setCellValue('A3', 'Periode: ' . $this->period);
                $sheet->setCellValue('A4', 'Cabang: ' . $this->branchName);
                $sheet->setCellValue('D3', 'Total Omzet: Rp ' . number_format((float) ($this->summary['totalRevenue'] ?? 0), 0, ',', '.'));
                $sheet->setCellValue('D4', 'Jumlah Transaksi: ' . number_format((int) ($this->summary['totalTransactions'] ?? 0), 0, ',', '.'));
                $sheet->setCellValue('D5', 'Rata-rata Transaksi: Rp ' . number_format((float) ($this->summary['avgTransaction'] ?? 0), 0, ',', '.'));
                $sheet->getStyle('A1:G2')->getFont()->setBold(true);

                $breakdownHeaderRow = 7;
                $sheet->setCellValue('A' . $breakdownHeaderRow, $this->periodType === 'HARIAN' ? 'Nama Menu' : 'Tanggal');
                $sheet->setCellValue('B' . $breakdownHeaderRow, $this->periodType === 'HARIAN' ? 'Jumlah Terjual' : 'Jumlah Transaksi');
                $sheet->setCellValue('C' . $breakdownHeaderRow, $this->periodType === 'HARIAN' ? 'Total Penjualan' : 'Total Omzet');
                foreach ($this->breakdown as $index => $row) {
                    $rowNumber = $breakdownHeaderRow + $index + 1;
                    $sheet->setCellValue('A' . $rowNumber, $row['label']);
                    $sheet->setCellValue('B' . $rowNumber, $row['count']);
                    $sheet->setCellValue('C' . $rowNumber, 'Rp ' . number_format((float) $row['revenue'], 0, ',', '.'));
                }

                $historyTitleRow = $this->historyHeaderRow() - 2;
                $sheet->setCellValue('A' . $historyTitleRow, 'Riwayat Transaksi Penjualan');
                $sheet->getStyle('A' . $breakdownHeaderRow . ':C' . $breakdownHeaderRow)->applyFromArray($this->headerStyle());
                $sheet->getStyle('A' . $this->historyHeaderRow() . ':G' . $this->historyHeaderRow())->applyFromArray($this->headerStyle());
                $sheet->getStyle('A' . $historyTitleRow)->getFont()->setBold(true);
                $sheet->freezePane('A' . ($this->historyHeaderRow() + 1));
            },
        ];
    }

    private function historyHeaderRow(): int
    {
        return 10 + count($this->breakdown);
    }

    private function headerStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A2E']],
        ];
    }
}
