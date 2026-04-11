<?php

namespace App\Services;

use App\Models\CashflowEntry;
use App\Models\DailyStockSession;
use App\Models\ReportExport;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Services\Owner\SalesReportQueryService;
use App\Services\Owner\TransactionHistoryQueryService;
use App\Support\ReportPeriod;
use App\Support\StockLogView;
use App\Support\UsageQuantityFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ReportExportService
{
    public function generate(ReportExport $export): array
    {
        $filters = $export->filters ?? [];

        return match ($export->type) {
            'admin.cashflow' => $this->generateAdminCashflow($export, $filters),
            'admin.usage' => $this->generateAdminUsage($export, $filters),
            'admin.daily_stock' => $this->generateAdminDailyStock($export, $filters),
            'admin.stock_logs' => $this->generateAdminStockLogs($export, $filters),
            'owner.cashflow' => $this->generateOwnerCashflow($export, $filters),
            'owner.usage' => $this->generateAdminUsage($export, $filters),
            'owner.transactions' => $this->generateOwnerTransactions($export, $filters),
            'owner.sales' => $this->generateOwnerSales($export, $filters),
            default => throw new \RuntimeException('Jenis export tidak didukung: ' . $export->type),
        };
    }

    private function generateAdminCashflow(ReportExport $export, array $filters): array
    {
        $type = ReportPeriod::resolveType((string) ($filters['type'] ?? 'daily'));
        [$dateFrom, $dateTo] = $this->resolveAdminRange($type, $filters);

        $query = CashflowEntry::query()
            ->with('creator:id,name')
            ->where('type', 'expense')
            ->whereBetween('entry_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->latest('entry_date')
            ->latest('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $rows = $query->get();
        $fileName = 'pengeluaran-' . $type . '-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return $this->writeCsv($export, $fileName, function ($output) use ($rows, $dateFrom, $dateTo) {
            fputcsv($output, ['Periode', $dateFrom->toDateString() . ' s/d ' . $dateTo->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, ['Tanggal', 'Nominal', 'Kategori', 'Catatan', 'Input Oleh', 'Waktu Input']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    optional($row->entry_date)->format('Y-m-d'),
                    (float) $row->amount,
                    $row->source ?? '-',
                    $row->note ?? '-',
                    optional($row->creator)->name ?? '-',
                    optional($row->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    private function generateAdminUsage(ReportExport $export, array $filters): array
    {
        $type = ReportPeriod::resolveType((string) ($filters['type'] ?? 'daily'));
        [$dateFrom, $dateTo] = $this->resolveAdminRange($type, $filters, true);

        $rows = StockLog::query()
            ->join('ingredients', 'ingredients.id', '=', 'stock_logs.ingredient_id')
            ->where('stock_logs.type', 'out')
            ->whereBetween('stock_logs.created_at', [$dateFrom->copy()->startOfDay(), $dateTo->copy()->endOfDay()])
            ->selectRaw(
                'ingredients.name as ingredient_name,
                SUM(ABS(stock_logs.quantity)) as total_quantity,
                ingredients.base_unit,
                ingredients.display_unit,
                ingredients.pack_size,
                COUNT(*) as usage_count,
                MAX(stock_logs.created_at) as last_used_at'
            )
            ->groupBy(
                'ingredients.name',
                'ingredients.base_unit',
                'ingredients.display_unit',
                'ingredients.pack_size'
            )
            ->orderByDesc('total_quantity')
            ->get();

        $fileName = 'laporan-pemakaian-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return $this->writeCsv($export, $fileName, function ($output) use ($rows, $dateFrom, $dateTo) {
            fputcsv($output, ['Laporan Pemakaian Bahan']);
            fputcsv($output, ['Periode', $dateFrom->toDateString() . ' s/d ' . $dateTo->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, ['Bahan', 'Total Pemakaian', 'Satuan', 'Frekuensi', 'Terakhir Digunakan']);

            foreach ($rows as $item) {
                $quantityLabel = UsageQuantityFormatter::formatLabel(
                    (float) $item->total_quantity,
                    (string) ($item->base_unit ?? ''),
                    (string) ($item->display_unit ?? ''),
                    (int) ($item->pack_size ?? 1)
                );

                fputcsv($output, [
                    $item->ingredient_name,
                    $quantityLabel,
                    strtolower((string) ($item->display_unit ?? $item->base_unit ?? '')),
                    $item->usage_count,
                    $item->last_used_at,
                ]);
            }
        });
    }

    private function generateAdminStockLogs(ReportExport $export, array $filters): array
    {
        $period = StockLogView::normalizePeriod((string) ($filters['period'] ?? 'daily'));
        $selectedDate = StockLogView::parseSelectedDate($filters['date'] ?? null);
        [$rangeStart, $rangeEnd] = StockLogView::resolveRange($period, $selectedDate);

        $query = StockLog::with('ingredient')
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->latest();

        $typeFilter = $filters['type'] ?? null;
        if ($typeFilter && in_array($typeFilter, ['in', 'out', 'adjustment'], true)) {
            $query->where('type', $typeFilter);
        }

        $rows = $query->get();
        $fileName = sprintf(
            'riwayat-stok-%s-%s_sd_%s.csv',
            $period,
            $rangeStart->toDateString(),
            $rangeEnd->toDateString()
        );

        return $this->writeCsv($export, $fileName, function ($output) use ($rows, $period, $rangeStart, $rangeEnd) {
            fputcsv($output, ['Riwayat Stok']);
            fputcsv($output, ['Periode', strtoupper($period)]);
            fputcsv($output, ['Rentang', $rangeStart->toDateString() . ' s/d ' . $rangeEnd->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, ['Tanggal', 'Bahan', 'Tipe', 'Jumlah', 'Sumber', 'Catatan']);

            foreach ($rows as $log) {
                $typeConfig = StockLogView::typeConfig($log);

                fputcsv($output, [
                    optional($log->created_at)->format('Y-m-d H:i:s'),
                    $log->ingredient->name ?? '-',
                    $typeConfig['label'],
                    StockLogView::exportQuantity($log),
                    $typeConfig['source'],
                    $log->note ?? '-',
                ]);
            }
        });
    }

    private function generateAdminDailyStock(ReportExport $export, array $filters): array
    {
        $type = ReportPeriod::resolveType((string) ($filters['type'] ?? 'daily'));
        [$dateFrom, $dateTo] = $this->resolveAdminRange($type, $filters, true);

        $rows = DailyStockSession::query()
            ->with('cashier:id,name')
            ->withSum('items as total_opening', 'opening_qty')
            ->withSum('items as total_remaining', 'remaining_qty')
            ->withSum('items as total_used', 'used_qty')
            ->withCount('items')
            ->whereBetween('session_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->get();

        $fileName = 'laporan-stok-harian-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return $this->writeCsv($export, $fileName, function ($output) use ($rows, $dateFrom, $dateTo) {
            fputcsv($output, ['Laporan Stok Harian Kasir']);
            fputcsv($output, ['Periode', $dateFrom->toDateString() . ' s/d ' . $dateTo->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, ['Tanggal', 'Kasir', 'Status', 'Jumlah Item', 'Dibawa (qty dasar)', 'Sisa (qty dasar)', 'Terpakai (qty dasar)', 'Nilai']);

            foreach ($rows as $row) {
                $opening = (float) ($row->total_opening ?? 0);
                $remaining = (float) ($row->total_remaining ?? 0);
                $used = (float) ($row->total_used ?? 0);
                $value = $used;

                fputcsv($output, [
                    optional($row->session_date)->format('Y-m-d'),
                    optional($row->cashier)->name ?? '-',
                    strtoupper((string) $row->status),
                    (int) ($row->items_count ?? 0),
                    $opening,
                    $remaining,
                    $used,
                    $value,
                ]);
            }
        });
    }

    private function generateOwnerCashflow(ReportExport $export, array $filters): array
    {
        $type = ReportPeriod::resolveType((string) ($filters['type'] ?? 'daily'));
        [$dateFrom, $dateTo] = $this->resolveOwnerRange($type, $filters);

        $query = CashflowEntry::query()
            ->with('creator:id,name')
            ->where('type', 'expense')
            ->whereBetween('entry_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->latest('entry_date')
            ->latest('id');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('creator', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $rows = $query->get();
        $fileName = 'owner-pengeluaran-' . $type . '-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return $this->writeCsv($export, $fileName, function ($output) use ($rows, $dateFrom, $dateTo) {
            fputcsv($output, ['Periode', $dateFrom->toDateString() . ' s/d ' . $dateTo->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, ['Tanggal', 'Nominal', 'Kategori', 'Catatan', 'Input Oleh', 'Waktu Input']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    optional($row->entry_date)->format('Y-m-d'),
                    (float) $row->amount,
                    $row->source ?? '-',
                    $row->note ?? '-',
                    optional($row->creator)->name ?? '-',
                    optional($row->created_at)->format('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    private function generateOwnerTransactions(ReportExport $export, array $filters): array
    {
        $periodFilter = new \App\Services\Shared\PeriodFilterService();
        $type = $periodFilter->resolveType((string) ($filters['type'] ?? 'daily'));
        [$dateFrom, $dateTo] = $this->resolveOwnerRange($type, $filters);

        $queryService = app(TransactionHistoryQueryService::class);
        $query = $queryService
            ->applyFilters(
                $queryService->baseListQuery($dateFrom, $dateTo),
                [
                    'search' => $filters['search'] ?? null,
                    'user_id' => $filters['user_id'] ?? null,
                    'payment_method_id' => $filters['payment_method_id'] ?? null,
                ]
            )
            ->latest();

        $rows = $query->get();
        $fileName = 'riwayat-transaksi-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return $this->writeCsv($export, $fileName, function ($output) use ($rows, $dateFrom, $dateTo) {
            fputcsv($output, ['Periode', $dateFrom->toDateString() . ' s/d ' . $dateTo->toDateString()]);
            fputcsv($output, []);
            fputcsv($output, ['Kode', 'Kasir', 'Metode Pembayaran', 'Status', 'Jumlah Item', 'Total', 'Dibayar', 'Kembalian', 'Waktu']);

            foreach ($rows as $trx) {
                $isPaid = (float) $trx->paid_amount >= (float) $trx->total_amount;
                fputcsv($output, [
                    $trx->transaction_code,
                    optional($trx->user)->name ?? '-',
                    optional($trx->paymentMethod)->name ?? '-',
                    $isPaid ? 'Lunas' : 'Kurang',
                    (int) $trx->details_count,
                    (float) $trx->total_amount,
                    (float) $trx->paid_amount,
                    (float) $trx->change_amount,
                    $trx->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
        });
    }

    private function generateOwnerSales(ReportExport $export, array $filters): array
    {
        $type = in_array(($filters['type'] ?? 'daily'), ['daily', 'weekly', 'monthly'], true)
            ? (string) $filters['type']
            : 'daily';

        $queryService = app(SalesReportQueryService::class);

        if ($type === 'monthly') {
            $month = $this->resolveMonth($filters['month'] ?? null);
            $summary = $queryService->buildMonthlySummary($month);
            $fileName = 'laporan-penjualan-bulanan-' . $month->format('Y-m') . '.csv';

            return $this->writeCsv($export, $fileName, function ($output) use ($month, $summary) {
                fputcsv($output, ['Jenis Laporan', 'Bulanan']);
                fputcsv($output, ['Bulan', $month->format('Y-m')]);
                fputcsv($output, ['Total Omzet', (string) $summary['totalRevenue']]);
                fputcsv($output, ['Jumlah Transaksi', (string) $summary['totalTransactions']]);
                fputcsv($output, ['Rata-rata Transaksi', (string) round($summary['avgTransaction'], 2)]);
                fputcsv($output, []);
                fputcsv($output, ['Tanggal', 'Jumlah Transaksi', 'Omzet']);

                foreach ($summary['dailyBreakdown'] as $row) {
                    fputcsv($output, [$row->date, (int) $row->trx_count, (float) $row->revenue]);
                }
            });
        }

        if ($type === 'weekly') {
            $weekAnchor = $this->resolveDate($filters['week_date'] ?? null);
            $weekStart = $weekAnchor->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $weekAnchor->copy()->endOfWeek(Carbon::SUNDAY);
            $summary = $queryService->buildWeeklySummary($weekAnchor);
            $analytics = $queryService->buildPeriodMenuAnalytics($weekStart, $weekEnd, false);

            $fileName = 'laporan-penjualan-mingguan-' . $weekStart->toDateString() . '-sampai-' . $weekEnd->toDateString() . '.csv';

            return $this->writeCsv($export, $fileName, function ($output) use ($summary, $analytics, $weekStart, $weekEnd) {
                fputcsv($output, ['Jenis Laporan', 'Mingguan']);
                fputcsv($output, ['Periode', $weekStart->format('Y-m-d') . ' s/d ' . $weekEnd->format('Y-m-d')]);
                fputcsv($output, ['Total Omzet', (string) $summary['totalRevenue']]);
                fputcsv($output, ['Jumlah Transaksi', (string) $summary['totalTransactions']]);
                fputcsv($output, ['Rata-rata Transaksi', (string) round($summary['avgTransaction'], 2)]);
                fputcsv($output, []);
                fputcsv($output, ['Menu', 'Qty', 'Kontribusi (%)', 'Penjualan']);

                foreach ($analytics['contributions'] as $item) {
                    fputcsv($output, [
                        $item->menu_name,
                        (int) $item->total_qty,
                        (float) $item->contribution,
                        (float) $item->total_sales,
                    ]);
                }
            });
        }

        $selectedDate = $this->resolveDate($filters['date'] ?? null);
        $summary = $queryService->buildDailySummary($selectedDate);
        $analytics = $queryService->buildPeriodMenuAnalytics($selectedDate, $selectedDate, false);
        $fileName = 'laporan-penjualan-harian-' . $selectedDate->toDateString() . '.csv';

        return $this->writeCsv($export, $fileName, function ($output) use ($summary, $analytics, $selectedDate) {
            fputcsv($output, ['Jenis Laporan', 'Harian']);
            fputcsv($output, ['Tanggal', $selectedDate->format('Y-m-d')]);
            fputcsv($output, ['Total Omzet', (string) $summary['totalRevenue']]);
            fputcsv($output, ['Jumlah Transaksi', (string) $summary['totalTransactions']]);
            fputcsv($output, ['Rata-rata Transaksi', (string) round($summary['avgTransaction'], 2)]);
            fputcsv($output, []);
            fputcsv($output, ['Menu', 'Qty', 'Kontribusi (%)', 'Penjualan']);

            foreach ($analytics['contributions'] as $item) {
                fputcsv($output, [
                    $item->menu_name,
                    (int) $item->total_qty,
                    (float) $item->contribution,
                    (float) $item->total_sales,
                ]);
            }
        });
    }

    private function resolveAdminRange(string $type, array $filters, bool $align = false): array
    {
        $request = new \Illuminate\Http\Request($filters);

        return ReportPeriod::resolveDateRange($request, $type, $align);
    }

    private function resolveOwnerRange(string $type, array $filters): array
    {
        $today = now()->startOfDay();
        $fromRaw = $filters['date_from'] ?? null;
        $toRaw = $filters['date_to'] ?? null;

        if (empty($fromRaw) || empty($toRaw)) {
            return match ($type) {
                'monthly' => [$today->copy()->startOfMonth(), $today->copy()],
                'weekly' => [$today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()],
                default => [$today->copy(), $today->copy()],
            };
        }

        $from = Carbon::parse((string) $fromRaw)->startOfDay();
        $to = Carbon::parse((string) $toRaw)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->greaterThan($today)) {
            $from = $today->copy();
        }

        if ($to->greaterThan($today)) {
            $to = $today->copy();
        }

        return [$from, $to];
    }

    private function resolveDate(mixed $value): Carbon
    {
        try {
            $date = $value ? Carbon::parse((string) $value)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            $date = now()->startOfDay();
        }

        $today = now()->startOfDay();
        return $date->greaterThan($today) ? $today : $date;
    }

    private function resolveMonth(mixed $value): Carbon
    {
        try {
            $date = $value ? Carbon::createFromFormat('Y-m', (string) $value)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            $date = now()->startOfMonth();
        }

        $thisMonth = now()->startOfMonth();
        return $date->greaterThan($thisMonth) ? $thisMonth : $date;
    }

    private function writeCsv(ReportExport $export, string $fileName, callable $writer): array
    {
        $dir = 'exports/' . $export->scope;
        $path = $dir . '/report-export-' . $export->id . '.csv';

        Storage::disk('local')->makeDirectory($dir);
        $absolute = Storage::disk('local')->path($path);
        $handle = fopen($absolute, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        $writer($handle);
        fclose($handle);

        return [$path, $fileName];
    }
}
