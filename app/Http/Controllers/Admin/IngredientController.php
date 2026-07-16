<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Support\AdminCache;
use App\Support\IngredientStockView;
use App\Support\IngredientUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $recordStatus = $this->recordStatus($request);
        $query = Ingredient::withTrashed()
            ->select([
                'id',
                'name',
                'category_id',
                'display_unit',
                'base_unit',
                'pack_size',
                'stock',
                'minimum_stock',
                'selling_price',
                'cost_price',
                'created_at',
                'deleted_at',
            ])
            ->with('category:id,name');

        $this->applyIndexFilters($query, $request);

        $activeCount = (clone $query)->whereNull('ingredients.deleted_at')->count();
        $archivedCount = (clone $query)->whereNotNull('ingredients.deleted_at')->count();
        $allCount = $activeCount + $archivedCount;

        $lowStockCount = (clone $query)
            ->whereNull('ingredients.deleted_at')
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        $this->applyRecordStatus($query, $recordStatus);
        $this->applyLifecycleSorting($query, $recordStatus);

        $ingredients = $query
            ->paginate(10)
            ->withQueryString();

        $ingredients->setCollection(
            $ingredients->getCollection()->map(function (Ingredient $ingredient) {
                $ingredient->stock_meta = IngredientStockView::fromIngredient($ingredient);
                return $ingredient;
            })
        );

        $categories = $this->categoryOptions();
        $hasNonLifecycleFilters = $request->filled('search')
            || $request->filled('category')
            || $request->filled('has_price');

        return view('admin.ingredients.index', compact(
            'ingredients',
            'categories',
            'lowStockCount',
            'recordStatus',
            'activeCount',
            'archivedCount',
            'allCount',
            'hasNonLifecycleFilters',
        ));
    }

    public function create()
    {
        $categories = $this->categoryOptions();

        return view('admin.ingredients.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        Ingredient::create($this->payloadFromValidated($validated));

        AdminCache::bumpDashboard();
        AdminCache::bumpStock();
        AdminCache::bumpDailyStock(); // selling_price mempengaruhi estimasi nilai laporan stok harian
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan berhasil ditambahkan.');
    }

    public function edit(Ingredient $ingredient)
    {
        $categories = $this->categoryOptions();

        return view('admin.ingredients.edit', compact('ingredient', 'categories'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate($this->rules());

        $ingredient->update($this->payloadFromValidated($validated));

        AdminCache::bumpDashboard();
        AdminCache::bumpStock();
        AdminCache::bumpDailyStock(); // selling_price mempengaruhi estimasi nilai laporan stok harian
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan berhasil diperbarui.');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        AdminCache::bumpDashboard();
        AdminCache::bumpStock();
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.ingredients.index')
            ->with('success', 'Bahan dinonaktifkan.');
    }

    public function archive(Request $request)
    {
        return redirect()->route('admin.ingredients.index', array_merge(
            $request->only(['search', 'category', 'has_price']),
            ['record_status' => 'archived'],
        ));
    }

    public function restore($id)
    {
        $ingredient = Ingredient::onlyTrashed()->findOrFail($id);
        $ingredient->restore();
        AdminCache::bumpDashboard();
        AdminCache::bumpStock();
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.ingredients.index', ['record_status' => 'active'])
            ->with('success', 'Bahan berhasil diaktifkan kembali.');
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'category_id' => 'nullable|exists:ingredient_categories,id',
            'display_unit' => 'required|in:g,kg,ml,l,pcs',
            'pack_size' => 'required|integer|min:1',
            'stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
        ];
    }

    private function payloadFromValidated(array $validated): array
    {
        $packSize = (int) $validated['pack_size'];
        $displayUnit = (string) $validated['display_unit'];

        return [
            'name' => $validated['name'],
            'category_id' => $validated['category_id'] ?? null,
            'display_unit' => $displayUnit,
            'base_unit' => IngredientUnit::baseUnit($displayUnit),
            'pack_size' => $packSize,
            'stock' => $this->normalizeStockInput($displayUnit, (float) $validated['stock'], $packSize),
            'minimum_stock' => $this->normalizeStockInput($displayUnit, (float) $validated['minimum_stock'], $packSize),
            'selling_price' => isset($validated['selling_price']) ? (float) $validated['selling_price'] : 0,
            'cost_price' => isset($validated['cost_price']) ? (float) $validated['cost_price'] : 0,
        ];
    }

    private function applyIndexFilters($query, Request $request): void
    {
        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        if ($request->filled('search')) {
            $this->applyIngredientSearch($query, (string) $request->input('search'));
        }

        if ($request->filled('has_price')) {
            if ($request->input('has_price') === '1') {
                $query->where('selling_price', '>', 0);
            } elseif ($request->input('has_price') === '0') {
                $query->where(function ($q) {
                    $q->whereNull('selling_price')->orWhere('selling_price', '<=', 0);
                });
            }
        }
    }

    private function recordStatus(Request $request): string
    {
        $status = (string) $request->input('record_status', 'active');

        return in_array($status, ['active', 'archived', 'all'], true) ? $status : 'active';
    }

    private function applyRecordStatus($query, string $recordStatus): void
    {
        if ($recordStatus === 'active') {
            $query->whereNull('ingredients.deleted_at');
        } elseif ($recordStatus === 'archived') {
            $query->whereNotNull('ingredients.deleted_at');
        }
    }

    private function applyLifecycleSorting($query, string $recordStatus): void
    {
        if ($recordStatus === 'active') {
            $query->orderByDesc('ingredients.created_at')->orderByDesc('ingredients.id');
            return;
        }

        if ($recordStatus === 'archived') {
            $query->orderByDesc('ingredients.deleted_at')->orderByDesc('ingredients.id');
            return;
        }

        $query
            ->orderByRaw('CASE WHEN ingredients.deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN ingredients.deleted_at IS NULL THEN ingredients.created_at END DESC')
            ->orderByRaw('CASE WHEN ingredients.deleted_at IS NOT NULL THEN ingredients.deleted_at END DESC')
            ->orderByDesc('ingredients.id');
    }

    private function applyIngredientSearch($query, string $search): void
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

    private function categoryOptions()
    {
        return Cache::remember(
            AdminCache::key('stock', 'ingredient_categories:list'),
            now()->addMinutes(2),
            fn () => IngredientCategory::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get()
        );
    }

    private function normalizeStockInput(string $displayUnit, float $value, int $packSize): float
    {
        if ($displayUnit === 'pcs') {
            return $value * max(1, $packSize);
        }

        return IngredientUnit::toBase($displayUnit, $value);
    }
}
