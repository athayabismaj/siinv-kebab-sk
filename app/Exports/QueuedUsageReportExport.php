<?php

namespace App\Exports;

use App\Support\UsageQuantityFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QueuedUsageReportExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize
{
    private int $number = 0;
    public function __construct(private readonly Builder $query) {}
    public function query(): Builder { return $this->query; }
    public function headings(): array { return ['No', 'Nama Bahan Baku', 'Total Pemakaian', 'Jumlah Pemakaian', 'Terakhir Dipakai']; }
    public function map($item): array
    {
        $parts = UsageQuantityFormatter::parts((float) $item->total_quantity, (string) $item->base_unit, (string) $item->display_unit, (int) $item->pack_size);
        return [++$this->number, $item->ingredient_name, trim($parts['quantity'] . ' ' . $parts['pack']), number_format((int) $item->usage_count, 0, ',', '.') . ' kali', $item->last_used_at ? Carbon::parse($item->last_used_at)->format('d/m/Y H:i') : '-'];
    }
    public function chunkSize(): int { return 250; }
}
