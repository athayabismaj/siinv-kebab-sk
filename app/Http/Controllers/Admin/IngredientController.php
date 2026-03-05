<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    /*** Helper Konversi Unit */
    private function convertToBaseUnit($unit, $value) {
        return match($unit) {
            'kg' => $value * 1000,
            'g'  => $value,
            'l'  => $value * 1000,
            'ml' => $value,
            'pcs'=> $value,
            default => $value,
        };
    }

    private function getBaseUnit($unit) {
        return match($unit) {
            'kg', 'g'  => 'g',
            'l', 'ml'  => 'ml',
            'pcs'      => 'pcs',
            default    => $unit,
        };
    }

    /*** INDEX + FILTER + SEARCH */
    public function index(Request $request)
    {
        $query = Ingredient::with('category');

        // Filter kategori
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Search nama
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $ingredients = $query->latest()->paginate(10)
            ->withQueryString();

        $categories = IngredientCategory::orderBy('name')->get();

        return view('admin.ingredients.index', compact('ingredients', 'categories'));
    }

    /*** CREATE */
    public function create()
    {
        $categories = IngredientCategory::orderBy('name')->get();
        return view('admin.ingredients.create', compact('categories'));
    }

    /*** STORE */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:ingredient_categories,id',
            'display_unit' => 'required|in:g,kg,ml,l,pcs',
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $baseUnit = $this->getBaseUnit($request->display_unit);

        Ingredient::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'display_unit' => $request->display_unit,
            'base_unit' => $baseUnit,
            'stock' => $this->convertToBaseUnit(
                $request->display_unit,
                $request->stock
            ),
            'minimum_stock' => $this->convertToBaseUnit(
                $request->display_unit,
                $request->minimum_stock
            ),
        ]);

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan berhasil ditambahkan.');
    }

    /*** EDIT */
    public function edit(Ingredient $ingredient)
    {
        $categories = IngredientCategory::orderBy('name')->get();
        return view('admin.ingredients.edit', compact('ingredient', 'categories'));
    }

    /*** UPDATE */
    public function update(Request $request, Ingredient $ingredient)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:ingredient_categories,id',
            'display_unit' => 'required|in:g,kg,ml,l,pcs',
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $baseUnit = $this->getBaseUnit($request->display_unit);

        $ingredient->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'display_unit' => $request->display_unit,
            'base_unit' => $baseUnit,
            'stock' => $this->convertToBaseUnit(
                $request->display_unit,
                $request->stock
            ),
            'minimum_stock' => $this->convertToBaseUnit(
                $request->display_unit,
                $request->minimum_stock
            ),
        ]);

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan berhasil diperbarui.');
    }

    /*** DESTROY */
    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan dinonaktifkan.');
    }

    /*** ARCHIVE */
    public function archive(Request $request)
    {
        $query = Ingredient::onlyTrashed()->with('category');

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

        $categories = IngredientCategory::orderBy('name')->get();

        $view = $request->routeIs('owner.*')
            ? 'owner.ingredients.archive'
            : 'admin.ingredients.archive';

        return view($view, compact('ingredients', 'categories'));
    }

    /*** RESTORE */
    public function restore(Request $request, $id)
    {
        $ingredient = Ingredient::onlyTrashed()->findOrFail($id);
        $ingredient->restore();

        $routeName = $request->routeIs('owner.*')
            ? 'owner.ingredients.archive'
            : 'admin.ingredients.archive';

        return redirect()
            ->route($routeName)
            ->with('success', 'Bahan berhasil diaktifkan kembali.');
    }
}
