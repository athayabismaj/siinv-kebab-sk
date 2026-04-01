<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $category = $request->input('category');

        $query = Ingredient::query()
            ->with('category:id,name')
            ->select('id', 'category_id', 'name', 'display_unit', 'base_unit', 'stock', 'minimum_stock', 'updated_at')
            ->orderBy('name');

        if ($search !== '') {
            if (DB::getDriverName() === 'pgsql') {
                $query->where('name', 'ILIKE', '%' . $search . '%');
            } else {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
            }
        }

        if (! empty($category)) {
            $query->where('category_id', (int) $category);
        }

        $ingredients = $query
            ->paginate(12)
            ->withQueryString();

        $categories = IngredientCategory::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $summaryRow = Ingredient::query()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN stock <= minimum_stock THEN 1 ELSE 0 END) as low,
                SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out
            ')
            ->first();

        $summary = [
            'total' => (int) ($summaryRow->total ?? 0),
            'low' => (int) ($summaryRow->low ?? 0),
            'out' => (int) ($summaryRow->out ?? 0),
        ];

        return view('owner.stocks.index', compact(
            'ingredients',
            'categories',
            'summary',
            'search',
            'category'
        ));
    }
}
