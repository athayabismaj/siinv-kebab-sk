<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyStockReportExport implements FromView, WithStyles, ShouldAutoSize
{
    private $sessions;
    private $periode;
    private $summary;
    private $periodLabel;
    private $branch;

    public function __construct($sessions, string $periode, array $summary, string $periodLabel = '', ?string $logoPath = null, $branch = null)
    {
        ini_set('memory_limit', '512M');

        $this->sessions = Utf8ExportSanitizer::clean($sessions);
        $this->periode = Utf8ExportSanitizer::clean($periode);
        $this->summary = Utf8ExportSanitizer::clean($summary);
        $this->periodLabel = Utf8ExportSanitizer::clean($periodLabel);
        $this->logoPath = $logoPath;
        $this->branch = $branch;
    }

    public function view(): View
    {
        return view('exports.daily_stock_professional', [
            'sessions' => $this->sessions,
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
