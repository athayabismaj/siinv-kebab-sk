<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashflowEntry;
use App\Models\Transaction;
use App\Services\ReportExportDispatchService;
use App\Support\AdminCache;
use App\Support\ReportPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CashflowController extends Controller
{
    public function __construct(
        private readonly ReportExportDispatchService $exportDispatch
    ) {
    }

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
        $hpp          = (float) ($summary['hpp'] ?? 0);
        $netCash      = (float) ($summary['netCash'] ?? 0);

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
            'hpp',
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
        try {
            $export = $this->exportDispatch->dispatch(
                $request->user(),
                'admin',
                'admin.cashflow',
                $request->query()
            );

            $message = 'Export pengeluaran masuk antrian. ID: #' . $export->id;
            if ($export->scheduled_for) {
                $message .= ' Diproses setelah jam operasional (' . $export->scheduled_for->format('d/m/Y H:i') . ').';
            }

            return redirect()
                ->route('admin.exports.index')
                ->with('success', $message);
        } catch (\Throwable) {
            return redirect()
                ->route('admin.exports.index')
                ->with('error', 'Export gagal diproses. Pastikan migrasi dan worker queue sudah aktif.');
        }
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

        return Cache::remember($summaryKey, now()->addSeconds(90), function () use ($dateFrom, $dateTo, $baseQuery) {
            $salesRevenue = (float) Transaction::query()
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->sum('total_amount');

            $expenseTotal = (float) (clone $baseQuery)->toBase()->sum('amount');
            $expenseCount = (int) (clone $baseQuery)->toBase()->count();

            // HPP: estimasi nilai bahan terpakai pada periode ini
            // Dihitung dari daily_stock_items dengan konversi satuan (kg/l ÷ 1000, pcs ÷ pack_size)
            $hpp = (float) \Illuminate\Support\Facades\DB::table('daily_stock_sessions as dss')
                ->join('daily_stock_items as dsi', 'dsi.daily_stock_session_id', '=', 'dss.id')
                ->join('ingredients', 'ingredients.id', '=', 'dsi.ingredient_id')
                ->whereBetween('dss.session_date', [$dateFrom, $dateTo])
                ->where('dss.status', 'closed')
                ->selectRaw("COALESCE(SUM(
                    CASE ingredients.display_unit
                        WHEN 'kg'  THEN (dsi.used_qty / 1000.0) * ingredients.selling_price
                        WHEN 'l'   THEN (dsi.used_qty / 1000.0) * ingredients.selling_price
                        WHEN 'pcs' THEN (dsi.used_qty / GREATEST(COALESCE(ingredients.pack_size, 1), 1)) * ingredients.selling_price
                        ELSE            dsi.used_qty * ingredients.selling_price
                    END
                ), 0) as hpp_total")
                ->value('hpp_total');

            return [
                'salesRevenue' => $salesRevenue,
                'expenseTotal' => $expenseTotal,
                'expenseCount' => $expenseCount,
                'hpp'          => $hpp,
                'netCash'      => $salesRevenue - $hpp - $expenseTotal,
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

