<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\DirectExportResponse;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateExpenseExport;
use App\Models\Branch;
use App\Models\CashflowEntry;
use App\Models\GeneratedExport;
use App\Models\Transaction;
use App\Support\AdminCache;
use App\Support\BranchScope;
use App\Support\ReportBrand;
use App\Support\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CashflowController extends Controller
{
    use DirectExportResponse;

    private const DIRECT_EXPORT_LIMIT = 250;

    public function index(Request $request)
    {
        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type);

        $baseQuery = $this->baseExpenseQuery($dateFrom->toDateString(), $dateTo->toDateString());
        $this->applySearch($baseQuery, $request);

        $entries = (clone $baseQuery)->paginate(10)->withQueryString();
        $groupedEntries = $entries->getCollection()->groupBy(fn ($entry) => $entry->entry_date->toDateString());

        $summary = $this->summary($type, $dateFrom->toDateString(), $dateTo->toDateString(), $request, $baseQuery);
        $summary['branchName'] = $this->branchLabel(BranchScope::scopedBranchIdFor(auth()->user()));

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
            'entry_date' => 'nullable|date',
            'amount' => 'required|numeric|min:1',
            'source' => 'required|string|max:120',
            'note' => 'nullable|string|max:255',
        ]);

        $entryDate = now()->toDateString();

        if (isset($validated['entry_date']) && Carbon::parse((string) $validated['entry_date'])->toDateString() !== $entryDate) {
            return back()
                ->withInput()
                ->withErrors(['entry_date' => 'Tanggal pencatatan dikunci ke hari ini. Pengeluaran tidak dapat dicatat mundur.']);
        }

        CashflowEntry::create([
            'entry_date' => $entryDate,
            'branch_id' => BranchScope::scopedBranchIdFor(auth()->user()),
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
        $format = (string) $request->query('format', 'excel');
        return $this->exportDirect($request, $format);
    }

    private function exportDirect(Request $request, string $format)
    {
        $type = ReportPeriod::resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = ReportPeriod::resolveDateRange($request, $type, true);

        $baseQuery = $this->baseExpenseQuery($dateFrom->toDateString(), $dateTo->toDateString());
        $this->applySearch($baseQuery, $request);

        $dateSuffix = $dateFrom->isSameDay($dateTo)
            ? $dateFrom->format('dMY')
            : $dateFrom->format('dM') . '-' . $dateTo->format('dMY');
        $fileName = 'Pengeluaran_' . $dateSuffix;
        $branchId = BranchScope::scopedBranchIdFor($request->user());
        $total = (clone $baseQuery)->toBase()->count();

        if ($format === 'excel' && $total > self::DIRECT_EXPORT_LIMIT) {
            $generatedExport = GeneratedExport::query()->create([
                'requested_by' => $request->user()->id,
                'branch_id' => $branchId,
                'type' => 'expense_report',
                'format' => 'excel',
                'filters' => [
                    'date_from' => $dateFrom->toDateString(),
                    'date_to' => $dateTo->toDateString(),
                    'type' => $type,
                    'search' => trim((string) $request->input('search', '')),
                ],
                'status' => GeneratedExport::STATUS_PENDING,
                'original_filename' => $fileName . '.xlsx',
                'expires_at' => now()->addDays(7),
            ]);

            GenerateExpenseExport::dispatch($generatedExport->id)->onConnection('database');

            return redirect()->route('admin.generated-exports.show', $generatedExport)
                ->with('success', 'Ekspor sedang diproses. File akan tersedia setelah selesai.');
        }

        if ($format !== 'excel' && $total > self::DIRECT_EXPORT_LIMIT) {
            return redirect()->back()
                ->withErrors(['export' => 'Ekspor HTML atau PDF dibatasi hingga 250 data. Gunakan Excel untuk data lebih besar.']);
        }

        $entries = (clone $baseQuery)->get();
        $summary = $this->summary($type, $dateFrom->toDateString(), $dateTo->toDateString(), $request, $baseQuery);
        $summary['branchName'] = $this->branchLabel(BranchScope::scopedBranchIdFor(auth()->user()));

        $periodeLabel = $dateFrom->translatedFormat('d F Y') . ' s/d ' . $dateTo->translatedFormat('d F Y');
        if ($dateFrom->toDateString() === $dateTo->toDateString()) {
            $periodeLabel = $dateFrom->translatedFormat('d F Y');
        }

        $periodLabels = [
            'daily' => 'HARIAN',
            'weekly' => 'MINGGUAN',
            'monthly' => 'BULANAN',
            'custom' => 'KUSTOM'
        ];
        $periodLabelText = $periodLabels[$type] ?? strtoupper($type);

        $viewData = [
            'entries' => $entries,
            'periode' => $periodeLabel,
            'periodLabel' => $periodLabelText,
            'summary' => $summary,
            'logoDataUri' => ReportBrand::logoDataUri(),
            'isExcel' => $format === 'excel',
        ];

        return $this->exportByFormat(
            $format,
            'exports.expense_professional',
            $viewData,
            $fileName,
            fn () => \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ExpenseReportExport($entries, $periodeLabel, $summary, $periodLabelText, ReportBrand::logoPath()),
                $fileName . '.xlsx'
            )
        );
    }

    private function baseExpenseQuery(string $dateFrom, string $dateTo): Builder
    {
        return CashflowEntry::query()
            ->with(['creator:id,name', 'branch:id,name'])
            ->where('type', 'expense')
            ->when(BranchScope::scopedBranchIdFor(auth()->user()), fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->whereBetween('entry_date', [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ])
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
            'branch_id' => BranchScope::scopedBranchIdFor(auth()->user()),
        ])));

        return Cache::remember($summaryKey, now()->addSeconds(90), function () use ($dateFrom, $dateTo, $baseQuery) {
            $salesRevenue = (float) Transaction::query()
                ->successful()
                ->when(BranchScope::scopedBranchIdFor(auth()->user()), fn ($query, $branchId) => $query->where('branch_id', $branchId))
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
                ->when(BranchScope::scopedBranchIdFor(auth()->user()), fn ($query, $branchId) => $query->where('dss.branch_id', $branchId))
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
                'netCash'      => $salesRevenue - $expenseTotal,
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

    private function branchLabel(?int $branchId): string
    {
        if (($branchId ?? 0) <= 0) {
            return 'Semua Cabang';
        }

        return Branch::query()->whereKey($branchId)->value('name') ?: 'Cabang tidak ditemukan';
    }
}
