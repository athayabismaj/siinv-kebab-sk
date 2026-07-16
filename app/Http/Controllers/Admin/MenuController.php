<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Support\AdminCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $query = Menu::query()
            ->select([
                'id',
                'category_id',
                'name',
                'description',
                'is_active',
                'sort_order',
                'created_at',
            ])
            ->with(['category:id,name'])
            ->withCount('variants');

        if ($request->filled('search')) {
            $this->applyMenuSearch($query, (string) $request->input('search'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        $menus = $query
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.menus.index', compact('menus'));
    }

    public function create()
    {
        $categories = $this->menuCategoryOptions();

        return view('admin.menus.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $sortOrder = isset($validated['sort_order'])
            ? (int) $validated['sort_order']
            : ((int) Menu::max('sort_order') + 1);

        Menu::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $sortOrder,
            'is_active' => $request->boolean('is_active'),
        ]);

        AdminCache::bumpDashboard();
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    public function edit(Menu $menu)
    {
        $categories = $this->menuCategoryOptions();

        return view('admin.menus.edit', compact('menu', 'categories'));
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate($this->rules($menu));

        $data = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => isset($validated['sort_order'])
                ? (int) $validated['sort_order']
                : (int) $menu->sort_order,
            'is_active' => $request->boolean('is_active'),
        ];

        $menu->update($data);
        AdminCache::bumpDashboard();
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        AdminCache::bumpDashboard();
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil diarsipkan.');
    }

    public function archive(Request $request)
    {
        $query = Menu::onlyTrashed()
            ->select([
                'id',
                'category_id',
                'name',
                'description',
                'is_active',
                'sort_order',
                'deleted_at',
            ])
            ->with(['category:id,name'])
            ->withCount('variants');

        if ($request->filled('search')) {
            $this->applyMenuSearch($query, (string) $request->input('search'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        $menus = $query
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        $categories = $this->menuCategoryOptions();

        return view('admin.menus.archive', compact('menus', 'categories'));
    }

    public function restore($id)
    {
        $menu = Menu::onlyTrashed()->findOrFail($id);
        $menu->restore();
        AdminCache::bumpDashboard();
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.menus.archive')
            ->with('success', 'Menu berhasil diaktifkan kembali.');
    }

    private function rules(?Menu $menu = null): array
    {
        $uniqueNameRule = 'required|max:150|unique:menus,name';

        if ($menu) {
            $uniqueNameRule .= ',' . $menu->id;
        }

        return [
            'category_id' => 'required|exists:menu_categories,id',
            'name' => $uniqueNameRule,
            'description' => 'nullable|string',
            'sort_order' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ];
    }

    private function menuCategoryOptions()
    {
        return Cache::remember(
            AdminCache::key('catalog', 'menu_categories:list'),
            now()->addMinutes(2),
            fn () => MenuCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    private function applyMenuSearch(Builder $query, string $search): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            $query->where('name', 'ILIKE', "%{$search}%");
            return;
        }

        $query->where('name', 'like', '%' . $search . '%');
    }

}
