<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyStockReportExport implements FromView, ShouldAutoSize, WithStyles
{
    private $sessions;
    private $periode;
    private $summary;
    private $periodLabel;

    public function __construct($sessions, string $periode, array $summary, string $periodLabel = '')
    {
        $this->sessions = Utf8ExportSanitizer::clean($sessions);
        $this->periode = Utf8ExportSanitizer::clean($periode);
        $this->summary = Utf8ExportSanitizer::clean($summary);
        $this->periodLabel = Utf8ExportSanitizer::clean($periodLabel);
    }

    public function view(): View
    {
        return view('exports.daily_stock_professional', [
            'sessions' => $this->sessions,
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
