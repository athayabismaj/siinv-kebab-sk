<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashflowEntry;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CashflowController extends Controller
{
    public function index(Request $request)
    {
        $type = $this->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, $type);

        $baseQuery = CashflowEntry::query()
            ->with('creator:id,name')
            ->where('type', 'expense')
            ->whereDate('entry_date', '>=', $dateFrom->toDateString())
            ->whereDate('entry_date', '<=', $dateTo->toDateString())
            ->latest('entry_date')
            ->latest('id');

        $this->applySearch($baseQuery, $request);

        $entries = (clone $baseQuery)->paginate(10)->withQueryString();
        $groupedEntries = $entries->getCollection()->groupBy(fn ($entry) => $entry->entry_date->toDateString());

        $salesRevenue = (float) Transaction::query()
            ->whereDate('created_at', '>=', $dateFrom->toDateString())
            ->whereDate('created_at', '<=', $dateTo->toDateString())
            ->sum('total_amount');

        $expenseTotal = (float) (clone $baseQuery)->toBase()->sum('amount');
        $netCash = $salesRevenue - $expenseTotal;
        $expenseCount = (clone $baseQuery)->toBase()->count();

        [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType] =
            $this->buildNavigator($type, $dateFrom);

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
        $type = $this->resolveType((string) $request->input('type', 'daily'));
        [$dateFrom, $dateTo] = $this->resolveDateRange($request, $type);

        $query = CashflowEntry::query()
            ->with('creator:id,name')
            ->where('type', 'expense')
            ->whereDate('entry_date', '>=', $dateFrom->toDateString())
            ->whereDate('entry_date', '<=', $dateTo->toDateString())
            ->latest('entry_date')
            ->latest('id');

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

    private function resolveDateRange(Request $request, string $type): array
    {
        $today = now()->startOfDay();

        if (! $request->filled('date_from') || ! $request->filled('date_to')) {
            if ($type === 'monthly') {
                $from = $today->copy()->startOfMonth();
                $to = $today->copy()->endOfMonth();
            } elseif ($type === 'weekly') {
                $from = $today->copy()->startOfWeek(Carbon::MONDAY);
                $to = $today->copy()->endOfWeek(Carbon::SUNDAY);
            } else {
                $from = $today->copy();
                $to = $today->copy();
            }

            if ($to->greaterThan($today)) {
                $to = $today->copy();
            }

            return [$from, $to];
        }

        $from = Carbon::parse($request->input('date_from'))->startOfDay();
        $to = Carbon::parse($request->input('date_to'))->startOfDay();

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

    private function buildNavigator(string $type, Carbon $dateFrom): array
    {
        $today = now()->startOfDay();

        if ($type === 'monthly') {
            $prevFrom = $dateFrom->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subMonth()->endOfMonth()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addMonth()->startOfMonth()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addMonth()->endOfMonth()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addMonth()->startOfMonth()->isAfter($today);
            $inputValue = $dateFrom->format('Y-m');
            $inputType = 'month';
        } elseif ($type === 'weekly') {
            $prevFrom = $dateFrom->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addWeek()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->isAfter($today);
            $inputValue = $dateFrom->format('Y-m-d');
            $inputType = 'date';
        } else {
            $prevFrom = $dateFrom->copy()->subDay()->format('Y-m-d');
            $prevTo = $dateFrom->copy()->subDay()->format('Y-m-d');
            $nextFrom = $dateFrom->copy()->addDay()->format('Y-m-d');
            $nextTo = $dateFrom->copy()->addDay()->format('Y-m-d');
            $isFuture = $dateFrom->copy()->addDay()->isAfter($today);
            $inputValue = $dateFrom->format('Y-m-d');
            $inputType = 'date';
        }

        return [$prevFrom, $prevTo, $nextFrom, $nextTo, $isFuture, $inputValue, $inputType];
    }

    private function resolveType(string $type): string
    {
        return in_array($type, ['daily', 'weekly', 'monthly'], true) ? $type : 'daily';
    }
}
