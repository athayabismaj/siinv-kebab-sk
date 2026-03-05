<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {

        $categoriesQuery = IngredientCategory::with([
            'ingredients' => function ($query) use ($request) {

                if ($request->filled('search')) {
                    $this->applyNameSearch($query, $request->search);
                }

                $query->orderBy('name');
            }
        ]);


        // FILTER CATEGORY
        if ($request->filled('category')) {

            $categoriesQuery->where(
                'id',
                $request->category
            );
        }


        // PAGINATION CATEGORY
        $categories = $categoriesQuery
            ->orderBy('name')
            ->paginate(5)
            ->withQueryString();


        // LIST CATEGORY UNTUK DROPDOWN + PENANDA STOK
        $allCategories = IngredientCategory::query()
            ->withCount('ingredients')
            ->withCount([
                'ingredients as out_of_stock_count' => function ($query) {
                    $query->where('stock', '<=', 0);
                },
                'ingredients as low_stock_count' => function ($query) {
                    $query->where('stock', '>', 0)
                        ->whereColumn('stock', '<=', 'minimum_stock');
                },
            ])
            ->orderBy('name')
            ->get();


        // HITUNG STOK RENDAH (SELARAS DENGAN FILTER AKTIF)
        $lowStockQuery = Ingredient::query()
            ->whereColumn('stock', '<=', 'minimum_stock');

        if ($request->filled('search')) {
            $this->applyNameSearch($lowStockQuery, $request->search);
        }

        if ($request->filled('category')) {
            $lowStockQuery->where('category_id', $request->category);
        }

        $lowStockCount = $lowStockQuery->count();


        return view(
            'admin.stocks.index',
            compact(
                'categories',
                'allCategories',
                'lowStockCount'
            )
        );
    }



    /*
    |--------------------------------------------------------------------------
    | RESTOCK FORM
    |--------------------------------------------------------------------------
    */

    public function restockForm(Ingredient $ingredient)
    {
        return view(
            'admin.stocks.restock',
            compact('ingredient')
        );
    }



    /*
    |--------------------------------------------------------------------------
    | RESTOCK
    |--------------------------------------------------------------------------
    */

    public function restock(Request $request, Ingredient $ingredient)
    {
        try {
            $request->validate([
                'quantity' => 'required|numeric|min:0.01',
                'note' => 'nullable|string|max:255'
            ]);

            $quantityInBaseUnit = $this->convertToBaseUnit(
                $ingredient->display_unit,
                (float) $request->quantity
            );

            DB::transaction(function () use ($request, $ingredient, $quantityInBaseUnit) {
                $ingredient->increment(
                    'stock',
                    $quantityInBaseUnit
                );

                StockLog::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'in',
                    'quantity' => $quantityInBaseUnit,
                    'note' => $request->note
                ]);
            });

            return redirect()
                ->route('admin.stocks.index')
                ->with(
                    'success',
                    'Restok berhasil dilakukan.'
                );
        } catch (\Throwable $e) {
            Log::error('Gagal restok bahan', [
                'ingredient_id' => $ingredient->id,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat restok. Coba lagi.');
        }
    }



    /*
    |--------------------------------------------------------------------------
    | ADJUST FORM
    |--------------------------------------------------------------------------
    */

    public function adjustForm(Ingredient $ingredient)
    {
        return view(
            'admin.stocks.adjust',
            compact('ingredient')
        );
    }



    /*
    |--------------------------------------------------------------------------
    | ADJUST
    |--------------------------------------------------------------------------
    */

    public function adjust(Request $request, Ingredient $ingredient)
    {
        try {
            $request->validate([
                'new_stock' => 'required|numeric|min:0',
                'note' => 'required|string|max:255'
            ]);

            $newStockInBaseUnit = $this->convertToBaseUnit(
                $ingredient->display_unit,
                (float) $request->new_stock
            );

            if (round($newStockInBaseUnit, 2) === round((float) $ingredient->stock, 2)) {
                return back()
                    ->withInput()
                    ->with('error', 'Stok baru sama dengan stok saat ini. Tidak ada perubahan yang disimpan.');
            }

            DB::transaction(function () use ($request, $ingredient, $newStockInBaseUnit) {
                $difference =
                    $newStockInBaseUnit
                    - $ingredient->stock;

                $ingredient->update([
                    'stock' => $newStockInBaseUnit
                ]);

                StockLog::create([
                    'ingredient_id' => $ingredient->id,
                    'type' => 'adjustment',
                    'quantity' => $difference,
                    'note' => $request->note
                ]);
            });

            return redirect()
                ->route('admin.stocks.index')
                ->with(
                    'success',
                    'Penyesuaian stok berhasil.'
                );
        } catch (\Throwable $e) {
            Log::error('Gagal penyesuaian stok', [
                'ingredient_id' => $ingredient->id,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat penyesuaian stok. Coba lagi.');
        }
    }



    /*
    |--------------------------------------------------------------------------
    | LOGS
    |--------------------------------------------------------------------------
    */

    public function logs(Request $request)
    {
        $logsQuery = StockLog::with('ingredient')->latest();

        if ($request->filled('type') && in_array($request->type, ['in', 'out', 'adjustment'], true)) {
            $logsQuery->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            if ($search !== '') {
                $driver = DB::connection()->getDriverName();
                $logsQuery->whereHas('ingredient', function ($query) use ($search, $driver) {
                    if ($driver === 'pgsql') {
                        $query->where('name', 'ILIKE', "%{$search}%");
                        return;
                    }
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
                });
            }
        }

        $logs = $logsQuery
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.stocks.logs',
            compact('logs')
        );
    }

    private function applyNameSearch($query, string $search): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $query->where('name', 'ILIKE', "%{$search}%");
            return;
        }

        $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
    }

    private function convertToBaseUnit(string $displayUnit, float $value): float
    {
        return match (strtolower(trim($displayUnit))) {
            'kg' => $value * 1000,
            'l' => $value * 1000,
            default => $value,
        };
    }
}
