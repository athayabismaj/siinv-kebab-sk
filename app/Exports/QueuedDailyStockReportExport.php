<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QueuedDailyStockReportExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize
{
    private int $number = 0;
    public function __construct(private readonly Builder $query) {}
    public function query(): Builder { return $this->query; }
    public function headings(): array { return ['No','Tanggal & Kasir','Status','Total Item Aktif','Bawa','Sisa','Terpakai','Est. Nilai Modal','Est. Nilai Terjual']; }
    public function map($session): array { return [++$this->number, $session->session_date->format('d/m/Y').' - '.($session->cashier->name ?? 'User Tidak Diketahui'), $session->status === 'closed' ? 'Selesai' : 'Aktif', (int) ($session->items_count ?? 0), (float) ($session->total_opening ?? 0), (float) ($session->total_remaining ?? 0), (float) ($session->total_used ?? 0), (int) round((float) ($session->total_value ?? 0)), (int) round((float) ($session->total_revenue ?? 0))]; }
    public function chunkSize(): int { return 250; }
}
