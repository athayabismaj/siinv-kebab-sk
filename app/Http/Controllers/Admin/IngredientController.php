<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Support\IngredientStockView;
use App\Support\IngredientUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingredient::query()
            ->select([
                'id',
                'name',
                'category_id',
                'display_unit',
                'base_unit',
                'pack_size',
                'stock',
                'minimum_stock',
                'created_at',
            ])
            ->with('category:id,name');

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $lowStockQuery = Ingredient::query();
        if ($request->filled('category')) {
            $lowStockQuery->where('category_id', $request->category);
        }
        if ($request->filled('search')) {
            $lowStockQuery->where('name', 'like', '%' . $request->search . '%');
        }
        $lowStockCount = $lowStockQuery
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        $ingredients = $query->latest()->paginate(10)
            ->withQueryString();

        $ingredients->setCollection(
            $ingredients->getCollection()->map(function (Ingredient $ingredient) {
                $ingredient->stock_meta = IngredientStockView::fromIngredient($ingredient);
                return $ingredient;
            })
        );

        $categories = Cache::remember(
            'ingredient_categories:list',
            now()->addMinutes(2),
            fn () => IngredientCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        return view('admin.ingredients.index', compact('ingredients', 'categories', 'lowStockCount'));
    }

    public function create()
    {
        $categories = Cache::remember(
            'ingredient_categories:list',
            now()->addMinutes(2),
            fn () => IngredientCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        return view('admin.ingredients.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:ingredient_categories,id',
            'display_unit' => 'required|in:g,kg,ml,l,pcs',
            'pack_size' => 'required|integer|min:1',
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $packSize = (int) $request->pack_size;
        $displayUnit = (string) $request->display_unit;
        $stock = (float) $request->stock;
        $minimumStock = (float) $request->minimum_stock;

        Ingredient::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'display_unit' => $displayUnit,
            'base_unit' => IngredientUnit::baseUnit($displayUnit),
            'pack_size' => $packSize,
            'stock' => $this->normalizeStockInput($displayUnit, $stock, $packSize),
            'minimum_stock' => $this->normalizeStockInput($displayUnit, $minimumStock, $packSize),
        ]);

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan berhasil ditambahkan.');
    }

    public function edit(Ingredient $ingredient)
    {
        $categories = Cache::remember(
            'ingredient_categories:list',
            now()->addMinutes(2),
            fn () => IngredientCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        return view('admin.ingredients.edit', compact('ingredient', 'categories'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:ingredient_categories,id',
            'display_unit' => 'required|in:g,kg,ml,l,pcs',
            'pack_size' => 'required|integer|min:1',
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $packSize = (int) $request->pack_size;
        $displayUnit = (string) $request->display_unit;
        $stock = (float) $request->stock;
        $minimumStock = (float) $request->minimum_stock;

        $ingredient->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'display_unit' => $displayUnit,
            'base_unit' => IngredientUnit::baseUnit($displayUnit),
            'pack_size' => $packSize,
            'stock' => $this->normalizeStockInput($displayUnit, $stock, $packSize),
            'minimum_stock' => $this->normalizeStockInput($displayUnit, $minimumStock, $packSize),
        ]);

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan berhasil diperbarui.');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan dinonaktifkan.');
    }

    public function archive(Request $request)
    {
        $query = Ingredient::onlyTrashed()
            ->select([
                'id',
                'name',
                'category_id',
                'display_unit',
                'base_unit',
                'pack_size',
                'stock',
                'minimum_stock',
                'deleted_at',
            ])
            ->with('category:id,name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $ingredients = $query
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        $categories = Cache::remember(
            'ingredient_categories:list',
            now()->addMinutes(2),
            fn () => IngredientCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        return view('admin.ingredients.archive', compact('ingredients', 'categories'));
    }

    public function restore($id)
    {
        $ingredient = Ingredient::onlyTrashed()->findOrFail($id);
        $ingredient->restore();

        return redirect()
            ->route('admin.ingredients.archive')
            ->with('success', 'Bahan berhasil diaktifkan kembali.');
    }

    private function normalizeStockInput(string $displayUnit, float $value, int $packSize): float
    {
        if ($displayUnit === 'pcs') {
            return $value * max(1, $packSize);
        }

        return IngredientUnit::toBase($displayUnit, $value);
    }
}
