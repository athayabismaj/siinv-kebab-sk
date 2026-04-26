<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Support\AdminCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StockMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $search   = trim((string) $request->input('search', ''));
        $category = $request->input('category');
        $hasPrice = $request->input('has_price'); // '' = semua, '1' = ada harga, '0' = belum ada harga
        $driver   = DB::getDriverName();

        $query = Ingredient::query()
            ->with('category:id,name')
            ->select('id', 'category_id', 'name', 'display_unit', 'base_unit', 'pack_size', 'stock', 'minimum_stock', 'selling_price', 'updated_at')
            ->orderBy('name');

        if ($search !== '') {
            if ($driver === 'pgsql') {
                $query->where('name', 'ILIKE', '%' . $search . '%');
            } else {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            }
        }

        if (! empty($category)) {
            $query->where('category_id', (int) $category);
        }

        if ($hasPrice === '1') {
            $query->where('selling_price', '>', 0);
        } elseif ($hasPrice === '0') {
            $query->where(fn ($q) => $q->whereNull('selling_price')->orWhere('selling_price', '<=', 0));
        }

        $ingredients = $query
            ->paginate(10)
            ->withQueryString();

        $categories = Cache::remember(
            AdminCache::key('stock', 'owner:categories:list'),
            now()->addSeconds(120),
            fn () => IngredientCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        $summary = Cache::remember(
            AdminCache::key('stock', 'owner:summary'),
            now()->addSeconds(60),
            function () {
                $summaryRow = Ingredient::query()
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN stock <= minimum_stock THEN 1 ELSE 0 END) as low,
                        SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out
                    ')
                    ->first();

                return [
                    'total' => (int) ($summaryRow->total ?? 0),
                    'low' => (int) ($summaryRow->low ?? 0),
                    'out' => (int) ($summaryRow->out ?? 0),
                ];
            }
        );

        return view('owner.stocks.index', compact(
            'ingredients',
            'categories',
            'summary',
            'search',
            'category',
            'hasPrice'
        ));
    }
}
