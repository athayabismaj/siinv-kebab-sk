<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\CashflowEntry;
use App\Models\Transaction;
use App\Services\ReportExportDispatchService;
use App\Services\Shared\PeriodFilterService;
use App\Support\AdminCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CashflowController extends Controller
{
    public function __construct(
        private readonly PeriodFilterService $periodFilter,
        private readonly ReportExportDispatchService $exportDispatch
    ) {}

    public function index(Request $request)
    {
        $type = $this->periodFilter->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->periodFilter->resolveDateRange($request, $type);

        $baseQuery = $this->baseExpenseQuery($dateFrom->toDateString(), $dateTo->toDateString());
        $this->applySearch($baseQuery, $request);

        $entries = (clone $baseQuery)->paginate(10)->withQueryString();
        $groupedEntries = $entries->getCollection()->groupBy(fn ($entry) => $entry->entry_date->toDateString());

        $summary = $this->summary(
            $baseQuery,
            $dateFrom->toDateTimeString(),
            $dateTo->copy()->endOfDay()->toDateTimeString(),
            $request
        );

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            $this->periodFilter->buildNavigator($type, $dateFrom);

        $salesRevenue = (float) ($summary['salesRevenue'] ?? 0);
        $expenseTotal = (float) ($summary['expenseTotal'] ?? 0);
        $expenseCount = (int) ($summary['expenseCount'] ?? 0);
        $netCash = (float) ($summary['netCash'] ?? 0);

        return view('owner.reports.expenses.index', compact(
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

    public function export(Request $request)
    {
        $export = $this->exportDispatch->dispatch(
            $request->user(),
            'owner',
            'owner.cashflow',
            $request->query()
        );

        $message = 'Export pengeluaran masuk antrian. ID: #' . $export->id;
        if ($export->scheduled_for) {
            $message .= ' Diproses setelah jam operasional (' . $export->scheduled_for->format('d/m/Y H:i') . ').';
        }

        return redirect()
            ->route('owner.exports.index')
            ->with('success', $message);
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

    private function summary(Builder $baseQuery, string $trxFrom, string $trxTo, Request $request): array
    {
        $summaryKey = AdminCache::key('cashflow', 'owner:expense:summary:' . md5(json_encode([
            'from' => $trxFrom,
            'to' => $trxTo,
            'search' => trim((string) $request->input('search', '')),
        ])));

        return Cache::remember($summaryKey, now()->addSeconds(90), function () use ($baseQuery, $trxFrom, $trxTo) {
            $salesRevenue = (float) Transaction::query()
                ->whereBetween('created_at', [$trxFrom, $trxTo])
                ->sum('total_amount');

            $expenseTotal = (float) (clone $baseQuery)->sum('amount');
            $expenseCount = (int) (clone $baseQuery)->count();

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

