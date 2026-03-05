<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IngredientCategory;
use Illuminate\Http\Request;

class IngredientCategoryController extends Controller
{
    public function index()
    {
        $categories = IngredientCategory::latest()->paginate(10);
        return view('admin.ingredient_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.ingredient_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100|unique:ingredient_categories,name',
        ]);

        IngredientCategory::create($request->only('name'));

        return redirect()
            ->route('admin.ingredient-categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(IngredientCategory $ingredientCategory)
    {
        return view('admin.ingredient_categories.edit',
            compact('ingredientCategory'));
    }

    public function update(Request $request, IngredientCategory $ingredientCategory)
    {
        $request->validate([
            'name' => 'required|max:100|unique:ingredient_categories,name,' . $ingredientCategory->id,
        ]);

        $ingredientCategory->update($request->only('name'));

        return redirect()
            ->route('admin.ingredient-categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(IngredientCategory $ingredientCategory)
    {
        // Cegah hapus jika masih dipakai
        if ($ingredientCategory->ingredients()->exists()) {
            return back()->withErrors([
                'error' => 'Kategori tidak bisa dihapus karena masih digunakan.'
            ]);
        }

        $ingredientCategory->delete();

        return redirect()
            ->route('admin.ingredient-categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}