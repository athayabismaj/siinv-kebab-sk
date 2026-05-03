<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Services\VariantAvailabilityService;
use App\Support\AdminCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    public function __construct(
        private readonly VariantAvailabilityService $variantAvailabilityService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $role = strtolower((string) optional($user->role)->name);
        $isPrivileged = in_array($role, ['owner', 'admin'], true);

        $search = $request->filled('search')
            ? mb_substr(trim((string) $request->input('search')), 0, 100)
            : null;
        $categoryId = $request->filled('category_id')
            ? (int) $request->input('category_id')
            : null;

        $cacheKey = AdminCache::key('catalog', 'api:menus:' . md5(json_encode([
            'user_id' => (int) $user->id,
            'role' => $role,
            'privileged' => $isPrivileged,
            'search' => $search,
            'category_id' => $categoryId,
        ])));

        $menus = Cache::remember($cacheKey, now()->addSeconds(120), function () use ($isPrivileged, $search, $categoryId, $user) {
            $query = Menu::query()
                ->with([
                    'category:id,name',
                    'variants' => function ($variantQuery) use ($isPrivileged) {
                        $variantQuery
                            ->select('id', 'menu_id', 'name', 'price', 'is_available', 'sort_order')
                            ->with(['ingredients:id,name'])
                            ->orderBy('sort_order');
                    },
                ])
                ->whereNull('deleted_at')
                ->select('id', 'category_id', 'name', 'description', 'is_active', 'sort_order')
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($search !== null && $search !== '') {
                $query->where('name', 'like', "%{$search}%");
            }

            if ($categoryId !== null) {
                $query->where('category_id', $categoryId);
            }

            if (! $isPrivileged) {
                $query->where('is_active', true);
            }

            $menus = $query->get();
            $allVariants = $menus->flatMap(fn (Menu $menu) => $menu->variants)->values();
            $availabilityMap = $this->variantAvailabilityService->evaluateForCashier(
                $allVariants,
                (int) $user->id
            );

            return $menus->map(function (Menu $menu) use ($isPrivileged, $availabilityMap) {
                $variants = $menu->variants->map(function ($variant) use ($availabilityMap) {
                    $availability = $availabilityMap[(int) $variant->id] ?? [
                        'is_available' => false,
                        'unavailable_reason' => VariantAvailabilityService::REASON_NO_SESSION,
                        'required_ingredients' => [],
                    ];

                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'price' => (float) $variant->price,
                        'is_available' => (bool) $availability['is_available'],
                        'unavailable_reason' => $availability['unavailable_reason'],
                        'required_ingredients' => $availability['required_ingredients'],
                        'sort_order' => (int) $variant->sort_order,
                    ];
                })->values();

                if (! $isPrivileged) {
                    $variants = $variants->where('is_available', true)->values();
                }

                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'description' => $menu->description,
                    'is_active' => (bool) $menu->is_active,
                    'sort_order' => (int) $menu->sort_order,
                    'category' => $menu->category ? [
                        'id' => $menu->category->id,
                        'name' => $menu->category->name,
                    ] : null,
                    'variants' => $variants,
                    'can_edit' => $isPrivileged,
                    'can_sell' => (bool) $menu->is_active,
                ];
            })->filter(function (array $menu) use ($isPrivileged) {
                return $isPrivileged || count($menu['variants']) > 0;
            })->values();
        });

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

    public function unavailableVariants(Request $request)
    {
        $user = $request->user();
        $role = strtolower((string) optional($user->role)->name);
        $isPrivileged = in_array($role, ['owner', 'admin'], true);

        if (! $isPrivileged) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $variants = MenuVariant::query()
            ->with([
                'menu:id,name,is_active',
                'ingredients:id,name',
            ])
            ->orderBy('menu_id')
            ->orderBy('sort_order')
            ->get(['id', 'menu_id', 'name', 'price', 'is_available', 'sort_order']);

        $availabilityMap = $this->variantAvailabilityService->evaluateForCashier(
            $variants,
            (int) $request->input('cashier_id', $user->id)
        );

        $rows = $variants
            ->map(function (MenuVariant $variant) use ($availabilityMap) {
                $availability = $availabilityMap[(int) $variant->id] ?? null;

                return [
                    'variant_id' => (int) $variant->id,
                    'variant_name' => (string) $variant->name,
                    'menu_id' => (int) $variant->menu_id,
                    'menu_name' => (string) optional($variant->menu)->name,
                    'is_available' => (bool) ($availability['is_available'] ?? false),
                    'unavailable_reason' => $availability['unavailable_reason'] ?? VariantAvailabilityService::REASON_NO_SESSION,
                    'required_ingredients' => $availability['required_ingredients'] ?? [],
                ];
            })
            ->where('is_available', false)
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Daftar varian tidak tersedia berhasil diambil.',
            'data' => [
                'count' => $rows->count(),
                'rows' => $rows,
            ],
        ]);
    }
}
