<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\MenuCategory;
use App\Models\IngredientCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecipeController extends Controller
{
    /**
     * ================= INDEX =================
     */
    public function index(Request $request)
    {
        $query = Menu::with([
            'category',
            'variants.ingredients'
        ]);

        // Filter kategori
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $search = strtolower($request->search);

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('category', function ($q2) use ($search) {
                      $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                  })
                  ->orWhereHas('variants', function ($q3) use ($search) {
                      $q3->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                  });
            });
        }

        $menus = $query->orderBy('name')
                       ->paginate(5)
                       ->withQueryString();

        $categories = MenuCategory::orderBy('name')->get();

        return view('admin.recipes.index', compact('menus', 'categories'));
    }


    /**
     * ================= EDIT =================
     */
    public function edit(Request $request, MenuVariant $variant)
    {
        $variant->load([
            'menu.category',
            'ingredients'
        ]);

        $query = IngredientCategory::with([
            'ingredients' => function ($q) {
                $q->orderBy('name');
            }
        ])->orderBy('name');

        if ($request->filled('category')) {
            $query->where('id', $request->category);
        }

        $ingredientCategories = $query->get();
        $allCategories = IngredientCategory::orderBy('name')->get();

        return view('admin.recipes.edit', compact(
            'variant',
            'ingredientCategories',
            'allCategories'
        ));
    }


    /**
     * ================= UPDATE =================
     */
    public function update(Request $request, MenuVariant $variant)
    {
        try {

            $request->validate([
                'ingredients' => 'required|array',
                'ingredients.*' => 'nullable|numeric|min:0',
            ]);

            $ingredientIds = array_map('intval', array_keys($request->input('ingredients', [])));
            $validIngredientCount = Ingredient::whereIn('id', $ingredientIds)->count();

            if ($validIngredientCount !== count($ingredientIds)) {
                return back()
                    ->withErrors(['ingredients' => 'Data bahan tidak valid.'])
                    ->withInput();
            }

            $syncData = [];

            foreach ($request->ingredients as $ingredientId => $quantity) {
                if ($quantity > 0) {
                    $syncData[$ingredientId] = [
                        'quantity' => $quantity
                    ];
                }
            }

            if (empty($syncData)) {
                return back()
                    ->withErrors(['ingredients' => 'Minimal satu bahan harus memiliki jumlah lebih dari 0.'])
                    ->withInput();
            }

            DB::transaction(function () use ($variant, $syncData) {
                $variant->ingredients()->sync($syncData);
            });

            return redirect()
                ->route('admin.recipes.index')
                ->with('success','Resep "' . $variant->name .'" pada menu "' . $variant->menu->name .'" berhasil diperbarui.'
);

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
