<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseReportExport implements FromView, WithStyles, ShouldAutoSize
{
    private $entries;
    private $periode;
    private $summary;
    private $periodLabel;
    private $branch;

    public function __construct($entries, string $periode, array $summary, string $periodLabel = '', ?string $logoPath = null, $branch = null)
    {
        ini_set('memory_limit', '512M');

        $this->entries = Utf8ExportSanitizer::clean($entries);
        $this->periode = Utf8ExportSanitizer::clean($periode);
        $this->summary = Utf8ExportSanitizer::clean($summary);
        $this->periodLabel = Utf8ExportSanitizer::clean($periodLabel);
        $this->logoPath = $logoPath;
        $this->branch = $branch;
    }

    public function view(): View
    {
        return view('exports.expense_professional', [
            'entries' => $this->entries,
            'periode' => $this->periode,
            'periodLabel' => $this->periodLabel,
            'summary' => $this->summary,
            'isExcel' => true,
            'branch' => $this->branch,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(48);
        $sheet->getColumnDimension('A')->setWidth(12);

        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
        ];
    }
}
