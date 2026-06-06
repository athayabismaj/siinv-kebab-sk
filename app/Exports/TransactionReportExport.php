<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Exports\Concerns\HasReportLogoDrawing;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionReportExport implements FromView, WithDrawings, WithStyles
{
    use HasReportLogoDrawing;

    private $viewData;

    public function __construct(array $viewData)
    {
        $this->raiseMemoryLimit();

        $this->viewData = Utf8ExportSanitizer::clean($viewData);
        $this->logoPath = $viewData['logoPath'] ?? null;
    }

    public function view(): View
    {
        return view('exports.transaction_professional', $this->viewData);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getRowDimension(1)->setRowHeight(48);
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(24);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(18);

        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
        ];
    }
}
