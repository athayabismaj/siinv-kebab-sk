<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $menus = Menu::query()
            ->select([
                'id',
                'category_id',
                'name',
                'description',
                'image_path',
                'is_active',
                'sort_order',
                'created_at',
            ])
            ->with(['category:id,name'])
            ->withCount('variants')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(10);

        return view('admin.menus.index', compact('menus'));
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $categories = Cache::remember(
            'menu_categories:list',
            now()->addMinutes(2),
            fn () => MenuCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        return view('admin.menus.create', compact('categories'));
    }


    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|max:150|unique:menus,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $imagePath = $file->storeAs('menus', $filename, 'public');
        }

        // Jika kosong → otomatis urutan terakhir + 1
        $sortOrder = isset($validated['sort_order'])
            ? (int) $validated['sort_order']
            : (Menu::max('sort_order') + 1);

        Menu::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image_path' => $imagePath,
            'sort_order' => $sortOrder ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil ditambahkan.');
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */
    public function edit(Menu $menu)
    {
        $categories = Cache::remember(
            'menu_categories:list',
            now()->addMinutes(2),
            fn () => MenuCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        return view('admin.menus.edit', compact('menu', 'categories'));
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:menu_categories,id',
            'name' => 'required|max:150|unique:menus,name,' . $menu->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'sort_order' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean'
        ]);

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => isset($validated['sort_order'])
                ? (int) $validated['sort_order']
                : 0,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {

            if ($menu->image_path &&
                Storage::disk('public')->exists($menu->image_path)) {
                Storage::disk('public')->delete($menu->image_path);
            }

            $file = $request->file('image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $data['image_path'] = $file->storeAs('menus', $filename, 'public');
        }

        $menu->update($data);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil diperbarui.');
    }


    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */
    public function destroy(Menu $menu)
    {
        $menu->delete();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil diarsipkan.');
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVE
    |--------------------------------------------------------------------------
    */
    public function archive(Request $request)
    {
        $query = Menu::onlyTrashed()
            ->select([
                'id',
                'category_id',
                'name',
                'description',
                'image_path',
                'is_active',
                'sort_order',
                'deleted_at',
            ])
            ->with(['category:id,name'])
            ->withCount('variants');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $menus = $query
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        $categories = Cache::remember(
            'menu_categories:list',
            now()->addMinutes(2),
            fn () => MenuCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );

        return view('admin.menus.archive', compact('menus', 'categories'));
    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE
    |--------------------------------------------------------------------------
    */
    public function restore($id)
    {
        $menu = Menu::onlyTrashed()->findOrFail($id);
        $menu->restore();

        return redirect()
            ->route('admin.menus.archive')
            ->with('success', 'Menu berhasil diaktifkan kembali.');
    }
}
