<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $role = strtolower((string) optional($user->role)->name);
        $isPrivileged = in_array($role, ['owner', 'admin'], true);

        $query = Menu::query()
            ->with([
                'category:id,name',
                'variants' => function ($variantQuery) use ($isPrivileged) {
                    $variantQuery
                        ->select('id', 'menu_id', 'name', 'price', 'is_available', 'sort_order')
                        ->orderBy('sort_order');

                    if (! $isPrivileged) {
                        $variantQuery->where('is_available', true);
                    }
                },
            ])
            ->whereNull('deleted_at')
            ->select('id', 'category_id', 'name', 'description', 'image_path', 'is_active', 'sort_order')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        if (! $isPrivileged) {
            $query->where('is_active', true);
        }

        $menus = $query->get()->map(function (Menu $menu) use ($isPrivileged) {
            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'description' => $menu->description,
                'image_url' => $menu->image_path ? asset('storage/' . $menu->image_path) : null,
                'is_active' => (bool) $menu->is_active,
                'sort_order' => (int) $menu->sort_order,
                'category' => $menu->category ? [
                    'id' => $menu->category->id,
                    'name' => $menu->category->name,
                ] : null,
                'variants' => $menu->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => (float) $variant->price,
                    'is_available' => (bool) $variant->is_available,
                    'sort_order' => (int) $variant->sort_order,
                ])->values(),
                'can_edit' => $isPrivileged,
                'can_sell' => (bool) $menu->is_active,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar menu berhasil diambil.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $role ?: null,
                    'is_privileged' => $isPrivileged,
                ],
                'filters' => [
                    'search' => $request->input('search'),
                    'category_id' => $request->input('category_id'),
                ],
                'menus' => $menus,
            ],
        ]);
    }
}
