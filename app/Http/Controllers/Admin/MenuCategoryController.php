<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Support\AdminCache;
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
            'is_addon' => 'nullable|boolean',
        ]);

        MenuCategory::create([
            'name' => $request->name,
            'is_addon' => $request->boolean('is_addon'),
        ]);

        AdminCache::bumpCatalog();
        AdminCache::bumpTransactions();

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
            'is_addon' => 'nullable|boolean',
        ]);

        $menuCategory->update([
            'name' => $request->name,
            'is_addon' => $request->boolean('is_addon'),
        ]);

        AdminCache::bumpCatalog();
        AdminCache::bumpTransactions();

        return redirect()
            ->route('admin.menu-categories.index')
            ->with('success', 'Kategori menu berhasil diperbarui.');
    }

    public function destroy(Request $request, MenuCategory $menuCategory)
    {
        $request->validate([
            'destroy_confirmation' => ['required', 'string', 'in:hapus'],
        ], [
            'destroy_confirmation.required' => 'Ketik hapus untuk mengonfirmasi.',
            'destroy_confirmation.in' => 'Konfirmasi tidak sesuai. Ketik hapus.',
        ]);
        if ($menuCategory->menus()->exists()) {
            return back()->withErrors([
                'error' => 'Kategori tidak bisa dihapus karena masih digunakan.'
            ]);
        }

        $menuCategory->delete();
        AdminCache::bumpCatalog();
        AdminCache::bumpTransactions();

        return redirect()
            ->route('admin.menu-categories.index')
            ->with('success', 'Kategori menu berhasil dihapus.');
    }
}
