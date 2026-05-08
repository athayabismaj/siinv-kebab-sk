<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionReportExport implements FromView, ShouldAutoSize, WithStyles
{
    private $viewData;

    public function __construct(array $viewData)
    {
        $this->viewData = Utf8ExportSanitizer::clean($viewData);
    }

    public function view(): View
    {
        return view('exports.transaction_professional', $this->viewData);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
        ];
    }
}
