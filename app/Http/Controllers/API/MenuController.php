<?php

namespace App\Http\Controllers\API;

use App\DTOs\CashierOperationalContext;
use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuVariant;
use App\Services\Api\CashierOperationalContextResolver;
use App\Services\VariantAvailabilityService;
use App\Support\AdminCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    public function __construct(
        private readonly VariantAvailabilityService $variantAvailabilityService,
        private readonly CashierOperationalContextResolver $operationalContextResolver,
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
            ? max(1, (int) $request->input('category_id'))
            : null;
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(
            self::MAX_PER_PAGE,
            max(1, (int) $request->input('per_page', self::DEFAULT_PER_PAGE))
        );
        $operationalContext = $isPrivileged
            ? null
            : $this->operationalContextResolver->resolve($user);

        $cacheKey = AdminCache::key('catalog', 'api:menus:'.md5(json_encode([
            'user_id' => (int) $user->id,
            'role' => $role,
            'privileged' => $isPrivileged,
            'search' => $search,
            'category_id' => $categoryId,
            'page' => $page,
            'per_page' => $perPage,
            'session_id' => $operationalContext?->sessionId(),
            'session_ambiguous' => $operationalContext?->ambiguous,
        ])));

        $catalog = Cache::remember(
            $cacheKey,
            now()->addSeconds(120),
            function () use (
                $isPrivileged,
                $search,
                $categoryId,
                $page,
                $perPage,
                $user,
                $operationalContext,
            ): array {
                $query = $this->variantCatalogQuery(
                    $isPrivileged,
                    $search,
                    $categoryId,
                    $operationalContext,
                );
                $paginator = $query->paginate(
                    $perPage,
                    ['menu_variants.*'],
                    'page',
                    $page,
                );
                $variants = collect($paginator->items());
                $availabilityMap = $this->variantAvailabilityService->evaluateForCashier(
                    $variants,
                    (int) $user->id,
                    operationalContext: $operationalContext,
                );

                return [
                    'menus' => $this->mapMenus($variants, $availabilityMap, $isPrivileged),
                    'categories' => $this->catalogCategories($isPrivileged),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'has_more' => $paginator->hasMorePages(),
                    ],
                ];
            }
        );

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
                    'search' => $search,
                    'category_id' => $categoryId,
                ],
                'categories' => $catalog['categories'],
                'menus' => $catalog['menus'],
                'pagination' => $catalog['pagination'],
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

    private function variantCatalogQuery(
        bool $isPrivileged,
        ?string $search,
        ?int $categoryId,
        ?CashierOperationalContext $operationalContext,
    ): Builder {
        $query = MenuVariant::query()
            ->join('menus', 'menus.id', '=', 'menu_variants.menu_id')
            ->with([
                'menu' => fn ($menuQuery) => $menuQuery
                    ->select('id', 'category_id', 'name', 'description', 'is_active', 'sort_order')
                    ->with('category:id,name'),
                'ingredients:id,name',
            ])
            ->whereNull('menus.deleted_at');

        if (! $isPrivileged) {
            $query
                ->where('menus.is_active', true)
                ->where('menu_variants.is_available', true);

            $sessionId = $operationalContext?->sessionId();
            if (! $sessionId || $operationalContext?->ambiguous) {
                $query->whereRaw('1 = 0');
            } else {
                $this->applyCashierAvailabilityFilter($query, $sessionId);
            }
        }

        if ($search !== null && $search !== '') {
            $operator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $pattern = '%'.$search.'%';
            $query->where(function (Builder $searchQuery) use ($operator, $pattern): void {
                $searchQuery
                    ->where('menus.name', $operator, $pattern)
                    ->orWhere('menu_variants.name', $operator, $pattern);
            });
        }

        if ($categoryId !== null) {
            $query->where('menus.category_id', $categoryId);
        }

        return $query
            ->orderBy('menus.sort_order')
            ->orderBy('menus.name')
            ->orderBy('menu_variants.sort_order')
            ->orderBy('menu_variants.id');
    }

    private function applyCashierAvailabilityFilter(Builder $query, int $sessionId): void
    {
        $query
            ->whereExists(function ($recipeQuery): void {
                $recipeQuery
                    ->selectRaw('1')
                    ->from('menu_variant_ingredients as available_recipe')
                    ->whereColumn('available_recipe.menu_variant_id', 'menu_variants.id')
                    ->where('available_recipe.quantity', '>', 0);
            })
            ->whereNotExists(function ($shortageQuery) use ($sessionId): void {
                $shortageQuery
                    ->selectRaw('1')
                    ->from('menu_variant_ingredients as required_recipe')
                    ->whereColumn('required_recipe.menu_variant_id', 'menu_variants.id')
                    ->where('required_recipe.quantity', '>', 0)
                    ->whereNotExists(function ($stockQuery) use ($sessionId): void {
                        $stockQuery
                            ->selectRaw('1')
                            ->from('daily_stock_items as available_stock')
                            ->where('available_stock.daily_stock_session_id', $sessionId)
                            ->whereColumn('available_stock.ingredient_id', 'required_recipe.ingredient_id')
                            ->whereColumn('available_stock.remaining_qty', '>=', 'required_recipe.quantity');
                    });
            });
    }

    /**
     * @param  Collection<int, MenuVariant>  $variants
     * @param  array<int, array<string, mixed>>  $availabilityMap
     * @return Collection<int, array<string, mixed>>
     */
    private function mapMenus(Collection $variants, array $availabilityMap, bool $isPrivileged): Collection
    {
        return $variants
            ->groupBy('menu_id')
            ->map(function (Collection $menuVariants) use ($availabilityMap, $isPrivileged): array {
                /** @var MenuVariant $firstVariant */
                $firstVariant = $menuVariants->first();
                $menu = $firstVariant->menu;
                $variants = $menuVariants->map(function (MenuVariant $variant) use ($availabilityMap): array {
                    $availability = $availabilityMap[(int) $variant->id] ?? [
                        'is_available' => false,
                        'unavailable_reason' => VariantAvailabilityService::REASON_NO_SESSION,
                    ];

                    return [
                        'id' => (int) $variant->id,
                        'name' => (string) $variant->name,
                        'image_url' => $variant->image_url,
                        'price' => (float) $variant->price,
                        'is_available' => (bool) $availability['is_available'],
                        'unavailable_reason' => $availability['unavailable_reason'],
                        'sort_order' => (int) $variant->sort_order,
                    ];
                })->values();

                return [
                    'id' => (int) $menu->id,
                    'name' => (string) $menu->name,
                    'description' => $menu->description,
                    'is_active' => (bool) $menu->is_active,
                    'sort_order' => (int) $menu->sort_order,
                    'category' => $menu->category ? [
                        'id' => (int) $menu->category->id,
                        'name' => (string) $menu->category->name,
                    ] : null,
                    'variants' => $variants,
                    'can_edit' => $isPrivileged,
                    'can_sell' => (bool) $menu->is_active,
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array{id:int,name:string}>
     */
    private function catalogCategories(bool $isPrivileged): Collection
    {
        return MenuCategory::query()
            ->select('id', 'name')
            ->whereHas('menus', function (Builder $menuQuery) use ($isPrivileged): void {
                $menuQuery
                    ->whereNull('menus.deleted_at')
                    ->whereHas('variants', function (Builder $variantQuery) use ($isPrivileged): void {
                        if (! $isPrivileged) {
                            $variantQuery->where('is_available', true);
                        }
                    });

                if (! $isPrivileged) {
                    $menuQuery->where('is_active', true);
                }
            })
            ->orderBy('name')
            ->get()
            ->map(fn (MenuCategory $category): array => [
                'id' => (int) $category->id,
                'name' => (string) $category->name,
            ]);
    }
}
