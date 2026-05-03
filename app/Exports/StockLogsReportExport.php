<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockLogsReportExport implements FromView, ShouldAutoSize, WithStyles
{
    private $logs;
    private array $summary;
    private string $periode;
    private string $periodLabel;
    private string $typeLabel;

    public function __construct(
        $logs,
        array $summary,
        string $periode,
        string $periodLabel,
        string $typeLabel
    ) {
        $this->logs = $logs;
        $this->summary = $summary;
        $this->periode = $periode;
        $this->periodLabel = $periodLabel;
        $this->typeLabel = $typeLabel;
    }

    public function view(): View
    {
        return view('exports.stock_logs_professional', [
            'logs' => $this->logs,
            'summary' => $this->summary,
            'periode' => $this->periode,
            'periodLabel' => $this->periodLabel,
            'typeLabel' => $this->typeLabel,
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
