<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Services\MenuVariantImageService;
use App\Support\AdminCache;
use Illuminate\Http\Request;
use Throwable;

class MenuVariantController extends Controller {
    public function __construct(
        private readonly MenuVariantImageService $imageService,
    ) {
    }

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
            'cost_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Auto default sort order jika kosong
        $sortOrder = isset($validated['sort_order'])
            ? (int) $validated['sort_order']
            : ($menu->variants()->max('sort_order') + 1);

        $imagePath = $request->hasFile('image')
            ? $this->imageService->store($request->file('image'))
            : null;

        try {
            $menu->variants()->create([
                'name' => $validated['name'],
                'image_path' => $imagePath,
                // price tetap dipakai sistem transaksi lama, disamakan dengan sell_price.
                'price' => $validated['sell_price'],
                'cost_price' => $validated['cost_price'],
                'sell_price' => $validated['sell_price'],
                'sort_order' => $sortOrder ?? 0,
                'is_available' => $request->boolean('is_available'),
            ]);
        } catch (Throwable $exception) {
            $this->imageService->delete($imagePath);
            throw $exception;
        }

        AdminCache::bumpCatalog();

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
            'cost_price' => 'required|numeric|min:0',
            'sell_price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|numeric|min:0',
            'is_available' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_image' => 'nullable|boolean',
        ]);

        $oldImagePath = $menuVariant->image_path;
        $newImagePath = $request->hasFile('image')
            ? $this->imageService->store($request->file('image'))
            : null;
        $imagePath = $newImagePath
            ?? ($request->boolean('remove_image') ? null : $oldImagePath);

        try {
            $menuVariant->update([
                'name' => $validated['name'],
                'image_path' => $imagePath,
                'price' => $validated['sell_price'],
                'cost_price' => $validated['cost_price'],
                'sell_price' => $validated['sell_price'],
                'sort_order' => isset($validated['sort_order'])
                    ? (int) $validated['sort_order']
                    : $menuVariant->sort_order,
                'is_available' => $request->boolean('is_available'),
            ]);
        } catch (Throwable $exception) {
            $this->imageService->delete($newImagePath);
            throw $exception;
        }

        if ($oldImagePath !== $imagePath) {
            $this->imageService->delete($oldImagePath);
        }

        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.menu-variants.index', $menu->id)
            ->with('success', 'Variant berhasil diperbarui.');
    }

    // DESTROY
    public function destroy(Menu $menu, MenuVariant $menuVariant)
    {
        abort_unless($menuVariant->menu_id === $menu->id, 404);

        $imagePath = $menuVariant->image_path;
        $menuVariant->delete();
        $this->imageService->delete($imagePath);
        AdminCache::bumpCatalog();

        return redirect()
            ->route('admin.menu-variants.index', $menu->id)
            ->with('success', 'Variant berhasil dihapus.');
    }

}
