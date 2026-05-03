<?php

namespace App\Http\Controllers\Concerns;

trait DirectExportResponse
{
    protected function exportByFormat(
        string $format,
        string $view,
        array $viewData,
        string $fileName,
        callable $excelDownload,
        string $pdfPaper = 'A4',
        string $pdfOrientation = 'portrait'
    ) {
        if ($format === 'html') {
            return view($view, $viewData);
        }

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $viewData)
                ->setPaper($pdfPaper, $pdfOrientation);
            return $pdf->download($fileName . '.pdf');
        }

        if ($format === 'excel') {
            return $excelDownload();
        }

        abort(400, 'Format tidak didukung');
    }
}
