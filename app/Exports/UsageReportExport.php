<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Exports\Concerns\HasReportLogoDrawing;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsageReportExport implements FromView, WithDrawings, WithStyles, ShouldAutoSize
{
    use HasReportLogoDrawing;

    private $items;
    private $periode;
    private $summary;
    private $periodLabel;

    public function __construct($items, string $periode, array $summary, string $periodLabel = '', ?string $logoPath = null)
    {
        $this->raiseMemoryLimit();

        $this->items = Utf8ExportSanitizer::clean($items);
        $this->periode = Utf8ExportSanitizer::clean($periode);
        $this->summary = Utf8ExportSanitizer::clean($summary);
        $this->periodLabel = Utf8ExportSanitizer::clean($periodLabel);
        $this->logoPath = $logoPath;
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
        $sheet->getRowDimension(1)->setRowHeight(48);

        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
        ];
    }
}
