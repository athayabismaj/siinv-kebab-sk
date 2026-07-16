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
        $recordStatus = $this->recordStatus($request);
        $query = Menu::withTrashed()
            ->select([
                'id',
                'category_id',
                'name',
                'description',
                'is_active',
                'sort_order',
                'created_at',
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

        $activeCount = (clone $query)->whereNull('menus.deleted_at')->count();
        $archivedCount = (clone $query)->whereNotNull('menus.deleted_at')->count();
        $allCount = $activeCount + $archivedCount;

        $this->applyRecordStatus($query, $recordStatus);
        $this->applyLifecycleSorting($query, $recordStatus);

        $menus = $query
            ->paginate(10)
            ->withQueryString();

        $categories = $this->menuCategoryOptions();
        $hasNonLifecycleFilters = $request->filled('search') || $request->filled('category');

        return view('admin.menus.index', compact(
            'menus',
            'categories',
            'recordStatus',
            'activeCount',
            'archivedCount',
            'allCount',
            'hasNonLifecycleFilters',
        ));
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
        return redirect()->route('admin.menus.index', array_merge(
            $request->only(['search', 'category']),
            ['record_status' => 'archived'],
        ));
    }

    public function restore($id)
    {
        $menu = Menu::onlyTrashed()->findOrFail($id);
        $menu->restore();
        AdminCache::bumpDashboard();
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.menus.index', ['record_status' => 'active'])
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

    private function recordStatus(Request $request): string
    {
        $status = (string) $request->input('record_status', 'active');

        return in_array($status, ['active', 'archived', 'all'], true) ? $status : 'active';
    }

    private function applyRecordStatus(Builder $query, string $recordStatus): void
    {
        if ($recordStatus === 'active') {
            $query->whereNull('menus.deleted_at');
        } elseif ($recordStatus === 'archived') {
            $query->whereNotNull('menus.deleted_at');
        }
    }

    private function applyLifecycleSorting(Builder $query, string $recordStatus): void
    {
        if ($recordStatus === 'active') {
            $query->orderBy('menus.sort_order')->orderByDesc('menus.created_at')->orderByDesc('menus.id');
            return;
        }

        if ($recordStatus === 'archived') {
            $query->orderByDesc('menus.deleted_at')->orderByDesc('menus.id');
            return;
        }

        $query
            ->orderByRaw('CASE WHEN menus.deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN menus.deleted_at IS NULL THEN menus.sort_order END ASC')
            ->orderByRaw('CASE WHEN menus.deleted_at IS NULL THEN menus.created_at END DESC')
            ->orderByRaw('CASE WHEN menus.deleted_at IS NOT NULL THEN menus.deleted_at END DESC')
            ->orderByDesc('menus.id');
    }

}
