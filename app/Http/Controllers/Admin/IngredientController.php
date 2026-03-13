<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
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

        $ingredients = $query->latest()->paginate(10)
            ->withQueryString();

        $categories = Cache::remember(
            'ingredient_categories:list',
            now()->addMinutes(2),
            fn () => IngredientCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        return view('admin.ingredients.index', compact('ingredients', 'categories'));
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
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        Ingredient::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'display_unit' => $request->display_unit,
            'base_unit' => IngredientUnit::baseUnit((string) $request->display_unit),
            'stock' => IngredientUnit::toBase((string) $request->display_unit, (float) $request->stock),
            'minimum_stock' => IngredientUnit::toBase((string) $request->display_unit, (float) $request->minimum_stock),
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
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $ingredient->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'display_unit' => $request->display_unit,
            'base_unit' => IngredientUnit::baseUnit((string) $request->display_unit),
            'stock' => IngredientUnit::toBase((string) $request->display_unit, (float) $request->stock),
            'minimum_stock' => IngredientUnit::toBase((string) $request->display_unit, (float) $request->minimum_stock),
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
}
