<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use Illuminate\Http\Request;

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
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
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

        $summary = [
            'total' => Ingredient::query()->count(),
            'low' => Ingredient::query()->whereColumn('stock', '<=', 'minimum_stock')->count(),
            'out' => Ingredient::query()->where('stock', '<=', 0)->count(),
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
