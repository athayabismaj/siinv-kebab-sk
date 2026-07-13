<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class QueuedExpenseReportExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithCustomStartCell, WithEvents
{
    private int $number = 0;

    public function __construct(
        private readonly Builder $query,
        private readonly string $period,
        private readonly string $periodType,
        private readonly string $branchName,
        private readonly array $summary,
    ) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function startCell(): string
    {
        return 'A7';
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal & Waktu',
            'Cabang',
            'Kategori / Sumber',
            'Catatan',
            'Diinput Oleh',
            'Nominal Pengeluaran',
        ];
    }

    public function map($entry): array
    {
        return [
            ++$this->number,
            Carbon::parse($entry->entry_date)->translatedFormat('d M Y') . ($entry->created_at ? ' ' . $entry->created_at->format('H:i') : ''),
            $entry->branch?->name ?? '-',
            $entry->source ?: '-',
            $entry->note ?: '-',
            $entry->creator?->name ?? 'Sistem',
            'Rp ' . number_format((float) $entry->amount, 0, ',', '.'),
        ];
    }

    public function chunkSize(): int
    {
        return 250;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->setCellValue('A1', 'KEBAB SK');
                $sheet->setCellValue('A2', 'LAPORAN PENGELUARAN ' . $this->periodType);
                $sheet->setCellValue('A3', 'Periode: ' . $this->period);
                $sheet->setCellValue('A4', 'Cabang: ' . $this->branchName);
                $sheet->setCellValue('A5', 'Total Pengeluaran: Rp ' . number_format((float) ($this->summary['expenseTotal'] ?? 0), 0, ',', '.'));
                $sheet->setCellValue('D5', 'Total Entri: ' . number_format((int) ($this->summary['expenseCount'] ?? 0), 0, ',', '.'));
                $sheet->getStyle('A1:G2')->getFont()->setBold(true);
                $sheet->getStyle('A7:G7')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A2E']],
                ]);
                $sheet->freezePane('A8');
            },
        ];
    }
}
