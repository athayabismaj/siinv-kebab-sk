<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseReportExport implements FromView, ShouldAutoSize, WithStyles
{
    private $entries;
    private $periode;
    private $summary;
    private $periodLabel;

    public function __construct($entries, string $periode, array $summary, string $periodLabel = '')
    {
        $this->entries = Utf8ExportSanitizer::clean($entries);
        $this->periode = Utf8ExportSanitizer::clean($periode);
        $this->summary = Utf8ExportSanitizer::clean($summary);
        $this->periodLabel = Utf8ExportSanitizer::clean($periodLabel);
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
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
        ];
    }
}
