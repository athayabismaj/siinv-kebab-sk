<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashflowEntry;
use App\Models\Transaction;
use App\Support\AdminCache;
use App\Support\ReportPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CashflowController extends Controller
{
    public function index(Request $request)
    {
        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type);

        $baseQuery = $this->baseExpenseQuery($dateFrom->toDateString(), $dateTo->toDateString());
        $this->applySearch($baseQuery, $request);

        $entries = (clone $baseQuery)->paginate(10)->withQueryString();
        $groupedEntries = $entries->getCollection()->groupBy(fn ($entry) => $entry->entry_date->toDateString());

        $summary = $this->summary($type, $dateFrom->toDateString(), $dateTo->toDateString(), $request, $baseQuery);

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            ReportPeriod::buildNavigator($type, $dateFrom);

        $salesRevenue = (float) ($summary['salesRevenue'] ?? 0);
        $expenseTotal = (float) ($summary['expenseTotal'] ?? 0);
        $expenseCount = (int) ($summary['expenseCount'] ?? 0);
        $netCash = (float) ($summary['netCash'] ?? 0);

        return view('admin.reports.expenses.index', compact(
            'entries',
            'groupedEntries',
            'type',
            'dateFrom',
            'dateTo',
            'prevFrom',
            'prevTo',
            'nextFrom',
            'nextTo',
            'isFuture',
            'inputValue',
            'inputType',
            'salesRevenue',
            'expenseTotal',
            'netCash',
            'expenseCount'
        ));
    }

    public function create(Request $request)
    {
        $entryDate = now()->toDateString();

        return view('admin.reports.expenses.create', compact('entryDate'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'source' => 'required|string|max:120',
            'note' => 'nullable|string|max:255',
        ]);

        $entryDate = now()->toDateString();

        CashflowEntry::create([
            'entry_date' => $entryDate,
            'type' => 'expense',
            'amount' => $validated['amount'],
            'source' => $validated['source'],
            'note' => $validated['note'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AdminCache::bumpCashflow();

        return redirect()
            ->route('admin.reports.cashflow', [
                'type' => 'daily',
                'date_from' => $entryDate,
                'date_to' => $entryDate,
            ])
            ->with('success', 'Pengeluaran berhasil disimpan.');
    }

    public function export(Request $request)
    {
        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type);

        $query = $this->baseExpenseQuery($dateFrom->toDateString(), $dateTo->toDateString());
        $this->applySearch($query, $request);

        $rows = $query->get();

        $filename = 'pengeluaran-' . $type . '-' . $dateFrom->toDateString() . '_sd_' . $dateTo->toDateString() . '.csv';

        return response()->streamDownload(function () use ($rows, $dateFrom, $dateTo) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

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

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function baseExpenseQuery(string $dateFrom, string $dateTo): Builder
    {
        return CashflowEntry::query()
            ->with('creator:id,name')
            ->where('type', 'expense')
            ->whereBetween('entry_date', [$dateFrom, $dateTo])
            ->latest('entry_date')
            ->latest('id');
    }

    private function summary(string $type, string $dateFrom, string $dateTo, Request $request, Builder $baseQuery): array
    {
        $summaryKey = AdminCache::key('cashflow', 'summary:' . md5(json_encode([
            'type' => $type,
            'from' => $dateFrom,
            'to' => $dateTo,
            'search' => trim((string) $request->input('search', '')),
        ])));

        return Cache::remember($summaryKey, now()->addSeconds(45), function () use ($dateFrom, $dateTo, $baseQuery) {
            $salesRevenue = (float) Transaction::query()
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->sum('total_amount');

            $expenseTotal = (float) (clone $baseQuery)->toBase()->sum('amount');
            $expenseCount = (int) (clone $baseQuery)->toBase()->count();

            return [
                'salesRevenue' => $salesRevenue,
                'expenseTotal' => $expenseTotal,
                'expenseCount' => $expenseCount,
                'netCash' => $salesRevenue - $expenseTotal,
            ];
        });
    }

    private function applySearch(Builder $query, Request $request): void
    {
        if (! $request->filled('search')) {
            return;
        }

        $search = trim((string) $request->input('search'));
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('source', 'like', "%{$search}%")
                ->orWhere('note', 'like', "%{$search}%")
                ->orWhereHas('creator', function (Builder $u) use ($search) {
                    $u->where('name', 'like', "%{$search}%");
                });
        });
    }
}
