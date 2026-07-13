<?php

namespace App\Exports;

use App\Models\StockLog;
use App\Support\StockLogView;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QueuedStockLogExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize
{
    private int $number = 0;

    public function __construct(private readonly Builder $query)
    {
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['No', 'Waktu', 'Bahan Baku', 'Jenis Mutasi', 'Jumlah', 'Sumber', 'Catatan'];
    }

    /** @param StockLog $log */
    public function map($log): array
    {
        $log = StockLogView::decorate($log);

        return [
            ++$this->number,
            $log->created_at->format('d/m/Y H:i'),
            $log->ingredient->name ?? '-',
            $log->display_type ?? $log->type,
            $log->display_quantity ?? $log->quantity,
            $log->display_source ?? '-',
            $log->display_note ?? '-',
        ];
    }

    public function chunkSize(): int
    {
        return 250;
    }
}
