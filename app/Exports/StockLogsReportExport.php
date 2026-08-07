<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use App\Support\Utf8ExportSanitizer;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockLogsReportExport implements FromView, WithStyles, ShouldAutoSize
{
    private $logs;
    private array $summary;
    private string $periode;
    private string $periodLabel;
    private string $typeLabel;
    private $branch;

    public function __construct(
        $logs,
        array $summary,
        string $periode,
        string $periodLabel,
        string $typeLabel,
        $branch = null
    ) {
        $this->raiseMemoryLimit();

        $this->logs = Utf8ExportSanitizer::clean($logs);
        $this->summary = Utf8ExportSanitizer::clean($summary);
        $this->periode = Utf8ExportSanitizer::clean($periode);
        $this->periodLabel = Utf8ExportSanitizer::clean($periodLabel);
        $this->typeLabel = Utf8ExportSanitizer::clean($typeLabel);
        $this->branch = $branch;
    }

    public function view(): View
    {
        return view('exports.stock_logs_professional', [
            'logs' => $this->logs,
            'summary' => $this->summary,
            'periode' => $this->periode,
            'periodLabel' => $this->periodLabel,
            'typeLabel' => $this->typeLabel,
            'branch' => $this->branch,
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

    private function raiseMemoryLimit(): void
    {
        $currentLimit = ini_get('memory_limit');

        if ($currentLimit === false || $currentLimit === '-1') {
            return;
        }

        if ($this->memoryLimitToBytes($currentLimit) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
    }

    private function memoryLimitToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ($number * 1024 * 1024 * 1024),
            'm' => (int) ($number * 1024 * 1024),
            'k' => (int) ($number * 1024),
            default => (int) $number,
        };
    }
}
