<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Exports\Concerns\HasReportLogoDrawing;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseReportExport implements FromView, WithDrawings, WithStyles
{
    use HasReportLogoDrawing;

    private $entries;
    private $periode;
    private $summary;
    private $periodLabel;

    public function __construct($entries, string $periode, array $summary, string $periodLabel = '', ?string $logoPath = null)
    {
        $this->raiseMemoryLimit();

        $this->entries = Utf8ExportSanitizer::clean($entries);
        $this->periode = Utf8ExportSanitizer::clean($periode);
        $this->summary = Utf8ExportSanitizer::clean($summary);
        $this->periodLabel = Utf8ExportSanitizer::clean($periodLabel);
        $this->logoPath = $logoPath;
    }

    public function view(): View
    {
        return view('exports.expense_professional', [
            'entries' => $this->entries,
            'periode' => $this->periode,
            'periodLabel' => $this->periodLabel,
            'summary' => $this->summary,
            'isExcel' => true,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(48);
        $sheet->getColumnDimension('A')->setWidth(13);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(20);

        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
        ];
    }
}
