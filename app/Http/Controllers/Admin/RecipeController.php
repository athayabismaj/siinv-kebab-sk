<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\Menu;
use App\Models\MenuCategory;
use App\Models\MenuVariant;
use App\Support\AdminCache;
use App\Support\IngredientUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecipeController extends Controller
{
    /**
     * ================= INDEX =================
     */
    public function index(Request $request)
    {
        $query = MenuVariant::query()
            ->select(['id', 'menu_id', 'name', 'is_available', 'sort_order'])
            ->with([
                'menu:id,category_id,name,is_active',
                'menu.category:id,name',
            ])
            ->withCount('ingredients')
            ->whereHas('menu', fn ($menuQuery) => $menuQuery->whereNull('menus.deleted_at'));

        // Filter kategori
        if ($request->filled('category')) {
            $query->whereHas('menu', fn ($menuQuery) => $menuQuery
                ->where('category_id', (int) $request->input('category'))
                ->whereNull('menus.deleted_at'));
        }

        // Search
        if ($request->filled('search')) {
            $search = strtolower($request->search);

            $query->where(function ($variantQuery) use ($search) {
                $variantQuery->whereRaw('LOWER(menu_variants.name) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('menu', function ($menuQuery) use ($search) {
                        $menuQuery->whereRaw('LOWER(menus.name) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereHas('menu.category', function ($categoryQuery) use ($search) {
                        $categoryQuery->whereRaw('LOWER(menu_categories.name) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        $variants = $query
            ->orderBy(Menu::query()
                ->select('name')
                ->whereColumn('menus.id', 'menu_variants.menu_id'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $categories = MenuCategory::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return view('admin.recipes.index', compact('variants', 'categories'));
    }

    public function details(MenuVariant $variant)
    {
        $recipe = Cache::remember(
            AdminCache::key('catalog', 'recipe-detail:'.$variant->id),
            now()->addMinutes(5),
            function () use ($variant): array {
                $loadedVariant = MenuVariant::query()
                    ->select(['id', 'menu_id', 'name'])
                    ->with([
                        'menu:id,name',
                        'ingredients' => fn ($ingredientQuery) => $ingredientQuery
                            ->select(['ingredients.id', 'ingredients.name', 'ingredients.base_unit'])
                            ->orderBy('ingredients.name'),
                    ])
                    ->findOrFail($variant->id);

                return [
                    'id' => (int) $loadedVariant->id,
                    'menu_name' => (string) $loadedVariant->menu?->name,
                    'variant_name' => (string) $loadedVariant->name,
                    'ingredients_count' => $loadedVariant->ingredients->count(),
                    'ingredients' => $loadedVariant->ingredients->map(fn ($ingredient) => [
                        'id' => (int) $ingredient->id,
                        'name' => (string) $ingredient->name,
                        'quantity' => (float) $ingredient->pivot->quantity,
                        'unit' => strtoupper((string) $ingredient->base_unit),
                    ])->values()->all(),
                ];
            },
        );
        $recipe['edit_url'] = route('admin.recipes.edit', $variant);

        return response()->json([
            'success' => true,
            'data' => $recipe,
        ]);
    }

    /**
     * ================= EDIT =================
     */
    public function edit(Request $request, MenuVariant $variant)
    {
        $variant->load('menu.category:id,name');

        $ingredientQuery = Ingredient::query()
            ->select(['id', 'category_id', 'name', 'display_unit', 'base_unit'])
            ->with('category:id,name')
            ->orderBy('name')
            ->orderBy('id');

        if ($request->filled('category')) {
            $ingredientQuery->where('category_id', (int) $request->input('category'));
        }

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->input('search')));
            $ingredientQuery->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
        }

        $ingredients = $ingredientQuery
            ->paginate(10)
            ->withQueryString();

        $quantities = DB::table('menu_variant_ingredients')
            ->where('menu_variant_id', $variant->id)
            ->whereIn('ingredient_id', $ingredients->getCollection()->pluck('id'))
            ->pluck('quantity', 'ingredient_id');

        $recipeIngredientCount = DB::table('menu_variant_ingredients')
            ->where('menu_variant_id', $variant->id)
            ->where('quantity', '>', 0)
            ->count();

        $allCategories = IngredientCategory::query()
            ->select(['id', 'name'])
            ->whereHas('ingredients')
            ->orderBy('name')
            ->get();

        return view('admin.recipes.edit', compact(
            'variant',
            'ingredients',
            'quantities',
            'recipeIngredientCount',
            'allCategories',
        ));
    }

    /**
     * ================= UPDATE =================
     */
    public function update(Request $request, MenuVariant $variant)
    {
        try {

            $validated = $request->validate([
                'ingredients' => 'required|array',
                'ingredients.*' => 'nullable|numeric|min:0',
                'visible_ingredients' => 'nullable|array',
                'visible_ingredients.*' => 'integer',
                'return_to' => 'nullable|in:index,edit',
                'return_search' => 'nullable|string|max:100',
                'return_category' => 'nullable|integer|min:1',
                'return_page' => 'nullable|integer|min:1',
            ]);

            $submittedIngredients = $request->input('ingredients', []);
            $submittedIngredientIds = array_map('intval', array_keys($submittedIngredients));
            $visibleIngredientIds = $request->filled('visible_ingredients')
                ? array_map('intval', $request->input('visible_ingredients', []))
                : $submittedIngredientIds;

            $visibleIngredientIds = array_values(array_unique(array_filter($visibleIngredientIds, fn ($id) => $id > 0)));
            $ingredientIds = array_values(array_unique(array_merge($submittedIngredientIds, $visibleIngredientIds)));

            if (empty($visibleIngredientIds)) {
                return back()
                    ->withErrors(['ingredients' => 'Tidak ada bahan yang dapat diperbarui pada form ini.'])
                    ->withInput();
            }

            $validIngredients = Ingredient::query()
                ->whereIn('id', $ingredientIds)
                ->get(['id', 'name', 'base_unit'])
                ->keyBy('id');

            if ($validIngredients->count() !== count($ingredientIds)) {
                return back()
                    ->withErrors(['ingredients' => 'Data bahan tidak valid.'])
                    ->withInput();
            }

            $submittedOutsideVisible = array_diff($submittedIngredientIds, $visibleIngredientIds);

            if (! empty($submittedOutsideVisible)) {
                return back()
                    ->withErrors(['ingredients' => 'Data bahan tidak sesuai dengan form resep yang dibuka.'])
                    ->withInput();
            }

            $fractionalPcsErrors = [];
            foreach ($submittedIngredients as $ingredientId => $quantity) {
                $ingredient = $validIngredients->get((int) $ingredientId);
                $numericQuantity = (float) $quantity;

                if ($ingredient
                    && IngredientUnit::requiresWholeQuantity((string) $ingredient->base_unit)
                    && $numericQuantity > 0
                    && ! IngredientUnit::isValidBaseQuantity('pcs', $numericQuantity)) {
                    $fractionalPcsErrors["ingredients.{$ingredientId}"] =
                        "Jumlah {$ingredient->name} harus berupa PCS utuh tanpa pecahan.";
                }
            }

            if (! empty($fractionalPcsErrors)) {
                throw ValidationException::withMessages($fractionalPcsErrors);
            }

            $syncData = [];
            $visibleIngredientLookup = array_flip($visibleIngredientIds);

            foreach ($submittedIngredients as $ingredientId => $quantity) {
                $ingredientId = (int) $ingredientId;

                if (! isset($visibleIngredientLookup[$ingredientId])) {
                    continue;
                }

                if ((float) $quantity > 0) {
                    $syncData[$ingredientId] = [
                        'quantity' => (float) $quantity,
                    ];
                }
            }

            $hasPreservedIngredients = DB::table('menu_variant_ingredients')
                ->where('menu_variant_id', $variant->id)
                ->whereNotIn('ingredient_id', $visibleIngredientIds)
                ->exists();

            if (empty($syncData) && ! $hasPreservedIngredients) {
                return back()
                    ->withErrors(['ingredients' => 'Minimal satu bahan harus memiliki jumlah lebih dari 0.'])
                    ->withInput();
            }

            DB::transaction(function () use ($variant, $visibleIngredientIds, $syncData) {
                DB::table('menu_variant_ingredients')
                    ->where('menu_variant_id', $variant->id)
                    ->whereIn('ingredient_id', $visibleIngredientIds)
                    ->delete();

                if (! empty($syncData)) {
                    $variant->ingredients()->attach($syncData);
                }
            });

            AdminCache::bumpCatalog();
            AdminCache::bumpDailyStock();

            if (($validated['return_to'] ?? 'index') === 'edit') {
                return redirect()
                    ->route('admin.recipes.edit', array_filter([
                        'variant' => $variant->id,
                        'search' => $validated['return_search'] ?? null,
                        'category' => $validated['return_category'] ?? null,
                        'page' => $validated['return_page'] ?? null,
                    ], fn ($value) => $value !== null && $value !== ''))
                    ->with('success', 'Semua perubahan resep berhasil disimpan.');
            }

            return redirect()
                ->route('admin.recipes.index')
                ->with('success', 'Resep "'.$variant->name.'" pada menu "'.$variant->menu->name.'" berhasil diperbarui.'
                );

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui resep', [
                'variant_id' => $variant->id,
                'menu_id' => $variant->menu_id,
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.recipes.index')
                ->with('error', 'Terjadi kesalahan saat menyimpan resep.');
        }
    }
}
