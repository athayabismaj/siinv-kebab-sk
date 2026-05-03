<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsageReportExport implements FromView, ShouldAutoSize, WithStyles
{
    private $items;
    private $periode;
    private $summary;
    private $periodLabel;

    public function __construct($items, string $periode, array $summary, string $periodLabel = '')
    {
        $this->items = $items;
        $this->periode = $periode;
        $this->summary = $summary;
        $this->periodLabel = $periodLabel;
    }

    public function view(): View
    {
        return view('exports.usage_professional', [
            'items' => $this->items,
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
