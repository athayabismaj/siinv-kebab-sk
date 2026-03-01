<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller {
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

    /*** INDEX */
    public function index() {
        $ingredients = Ingredient::latest()->paginate(10);
        return view('admin.ingredients.index', compact('ingredients'));
    }

    /*** CREATE */
    public function create() {
        return view('admin.ingredients.create');
    }

    /*** STORE */
    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:150',
            'display_unit' => 'required|in:g,kg,ml,l,pcs',
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $baseUnit = $this->getBaseUnit($request->display_unit);
        $convertedStock = $this->convertToBaseUnit(
            $request->display_unit,
            $request->stock
        );

        Ingredient::create([
            'name' => $request->name,
            'display_unit' => $request->display_unit,
            'base_unit' => $baseUnit,
            'stock' => $convertedStock,
            'minimum_stock' => $convertedStock < $request->minimum_stock
                ? $this->convertToBaseUnit($request->display_unit, $request->minimum_stock)
                : $this->convertToBaseUnit($request->display_unit, $request->minimum_stock),
        ]);

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan berhasil ditambahkan.');
    }

    /*** EDIT */
    public function edit(Ingredient $ingredient) {
        return view('admin.ingredients.edit', compact('ingredient'));
    }

    /*** UPDATE */
    public function update(Request $request, Ingredient $ingredient) {
        $request->validate([
            'name' => 'required|string|max:150',
            'display_unit' => 'required|in:g,kg,ml,l,pcs',
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $baseUnit = $this->getBaseUnit($request->display_unit);
        $convertedStock = $this->convertToBaseUnit(
            $request->display_unit,
            $request->stock
        );

        $ingredient->update([
            'name' => $request->name,
            'display_unit' => $request->display_unit,
            'base_unit' => $baseUnit,
            'stock' => $convertedStock,
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
    public function destroy(Ingredient $ingredient) {
        $ingredient->delete();

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan dinonaktifkan.');
    }

    /*** ARCHIVE */
    public function archive() {
        $ingredients = Ingredient::onlyTrashed()
            ->latest()
            ->paginate(10);

        return view('owner.ingredients.archive', compact('ingredients'));
    }

    /*** RESTORE */
    public function restore($id) {
        $ingredient = Ingredient::withTrashed()->findOrFail($id);
        $ingredient->restore();

        return redirect()
            ->route('owner.ingredients.archive')
            ->with('success', 'Bahan berhasil diaktifkan kembali.');
    }
}