<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuVariant;
use Illuminate\Http\Request;

class MenuVariantController extends Controller {
    /*** INDEX */
    public function index(Menu $menu) {
        $variants = $menu->variants()
            ->orderBy('sort_order')
            ->get();

        return view('admin.menu_variants.index', compact('menu', 'variants'));
    }

    /*** CREATE */
    public function create(Menu $menu) {
        return view('admin.menu_variants.create', compact('menu'));
    }

    /*** STORE */
    public function store(Request $request, Menu $menu) {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
        ]);

        // Auto default sort order jika kosong
        $sortOrder = isset($validated['sort_order'])
            ? (int) $validated['sort_order']
            : ($menu->variants()->max('sort_order') + 1);

        $menu->variants()->create([
            'name' => $validated['name'],
            'price' => (int) $validated['price'],
            'sort_order' => $sortOrder ?? 0,
            'is_available' => $request->boolean('is_available'),
        ]);

        return redirect()
            ->route('admin.menu-variants.index', $menu->id)
            ->with('success', 'Variant berhasil ditambahkan.');
    }

    /*** EDIT */
    public function edit(Menu $menu, MenuVariant $menuVariant)
    {
        abort_unless($menuVariant->menu_id === $menu->id, 404);

        return view('admin.menu_variants.edit', compact('menu', 'menuVariant'));
    }

    /*** UPDATE */
    public function update(Request $request, Menu $menu, MenuVariant $menuVariant)
    {
        abort_unless($menuVariant->menu_id === $menu->id, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
        ]);

        $menuVariant->update([
            'name' => $validated['name'],
            'price' => (int) $validated['price'],
            'sort_order' => isset($validated['sort_order'])
                ? (int) $validated['sort_order']
                : $menuVariant->sort_order,
            'is_available' => $request->boolean('is_available'),
        ]);

        return redirect()
            ->route('admin.menu-variants.index', $menu->id)
            ->with('success', 'Variant berhasil diperbarui.');
    }

    // DESTROY
    public function destroy(Menu $menu, MenuVariant $menuVariant)
    {
        abort_unless($menuVariant->menu_id === $menu->id, 404);

        $menuVariant->delete();

        return redirect()
            ->route('admin.menu-variants.index', $menu->id)
            ->with('success', 'Variant berhasil dihapus.');
    }
}