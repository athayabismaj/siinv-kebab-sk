<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use Illuminate\Http\Request;

class MenuCategoryController extends Controller
{
    public function index()
    {
        // withCount supaya tidak query berulang di blade
        $categories = MenuCategory::withCount('menus')
            ->latest()
            ->paginate(10);

        return view('admin.menu_categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.menu_categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:100|unique:menu_categories,name',
        ]);

        MenuCategory::create([
            'name' => $request->name
        ]);

        return redirect()
            ->route('admin.menu-categories.index')
            ->with('success', 'Kategori menu berhasil ditambahkan.');
    }

    public function edit(MenuCategory $menuCategory)
    {
        return view('admin.menu_categories.edit', compact('menuCategory'));
    }

    public function update(Request $request, MenuCategory $menuCategory)
    {
        $request->validate([
            'name' => 'required|max:100|unique:menu_categories,name,' . $menuCategory->id,
        ]);

        $menuCategory->update([
            'name' => $request->name
        ]);

        return redirect()
            ->route('admin.menu-categories.index')
            ->with('success', 'Kategori menu berhasil diperbarui.');
    }

    public function destroy(MenuCategory $menuCategory)
    {
        if ($menuCategory->menus()->exists()) {
            return back()->withErrors([
                'error' => 'Kategori tidak bisa dihapus karena masih digunakan.'
            ]);
        }

        $menuCategory->delete();

        return redirect()
            ->route('admin.menu-categories.index')
            ->with('success', 'Kategori menu berhasil dihapus.');
    }
}