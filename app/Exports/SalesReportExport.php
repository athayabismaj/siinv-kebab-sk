<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromView, WithStyles, ShouldAutoSize
{
    private $viewData;

    public function __construct(array $viewData)
    {
        ini_set('memory_limit', '512M');

        $this->viewData = Utf8ExportSanitizer::clean($viewData);
    }

    public function view(): View
    {
        return view('exports.sales_professional', $this->viewData);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(48);

        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
        ];
    }
}
