<?php

namespace App\Services;

use App\Models\DailyStockSession;
use App\Models\MenuVariant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VariantAvailabilityService
{
    public const REASON_NO_SESSION = 'NO_SESSION';
    public const REASON_NO_RECIPE = 'NO_RECIPE';
    public const REASON_INGREDIENT_NOT_TRANSFERRED = 'INGREDIENT_NOT_TRANSFERRED';
    public const REASON_INSUFFICIENT_STOCK = 'INSUFFICIENT_STOCK';
    public const REASON_MANUAL_DISABLED = 'MANUAL_DISABLED';

    /**
     * @param Collection<int, MenuVariant> $variants
     * @return array<int, array<string, mixed>>
     */
    public function evaluateForCashier(Collection $variants, int $cashierId, ?Carbon $businessTime = null): array
    {
        $context = $this->buildSessionContext($cashierId, $businessTime);
        $results = [];

        foreach ($variants as $variant) {
            $results[(int) $variant->id] = $this->evaluateVariantWithContext($variant, $context, 1.0);
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluateSingleForCheckout(MenuVariant $variant, int $cashierId, float $qty, ?Carbon $businessTime = null): array
    {
        $context = $this->buildSessionContext($cashierId, $businessTime);

        return $this->evaluateVariantWithContext($variant, $context, $qty);
    }

    /**
     * @return array{date:string,session:?DailyStockSession,stock_by_ingredient:array<int, float>}
     */
    private function buildSessionContext(int $cashierId, ?Carbon $businessTime = null): array
    {
        $clock = ($businessTime ? $businessTime->copy() : now('Asia/Jakarta'))
            ->setTimezone('Asia/Jakarta')
            ->startOfDay();
        $sessionDate = $clock->toDateString();

        $session = null;
        if ($cashierId > 0) {
            $session = DailyStockSession::query()
                ->with('items:daily_stock_session_id,ingredient_id,remaining_qty')
                ->where('cashier_id', $cashierId)
                ->whereDate('session_date', $sessionDate)
                ->whereRaw("LOWER(TRIM(status)) = 'open'")
                ->first();
        }

        $stockByIngredient = [];
        foreach ($session?->items ?? [] as $item) {
            $stockByIngredient[(int) $item->ingredient_id] = (float) $item->remaining_qty;
        }

        return [
            'date' => $sessionDate,
            'session' => $session,
            'stock_by_ingredient' => $stockByIngredient,
        ];
    }

    /**
     * @param array{date:string,session:?DailyStockSession,stock_by_ingredient:array<int, float>} $context
     * @return array<string, mixed>
     */
    private function evaluateVariantWithContext(MenuVariant $variant, array $context, float $qty): array
    {
        $orderQty = max(0.0, (float) $qty);
        $ingredients = $variant->relationLoaded('ingredients')
            ? $variant->ingredients
            : $variant->ingredients()->select('ingredients.id', 'ingredients.name')->get();

        $requiredIngredients = [];

        foreach ($ingredients as $ingredient) {
            $required = max(0.0, ((float) $ingredient->pivot->quantity) * $orderQty);
            if ($required <= 0) {
                continue;
            }

            $remaining = (float) ($context['stock_by_ingredient'][(int) $ingredient->id] ?? 0);
            $requiredIngredients[] = [
                'ingredient_id' => (int) $ingredient->id,
                'ingredient_name' => (string) $ingredient->name,
                'required_qty' => round($required, 2),
                'remaining_qty' => round($remaining, 2),
            ];
        }

        if (! $variant->is_available) {
            return [
                'is_available' => false,
                'unavailable_reason' => self::REASON_MANUAL_DISABLED,
                'required_ingredients' => $requiredIngredients,
            ];
        }

        if ($ingredients->count() === 0 || count($requiredIngredients) === 0) {
            return [
                'is_available' => false,
                'unavailable_reason' => self::REASON_NO_RECIPE,
                'required_ingredients' => $requiredIngredients,
            ];
        }

        if (! $context['session']) {
            return [
                'is_available' => false,
                'unavailable_reason' => self::REASON_NO_SESSION,
                'required_ingredients' => $requiredIngredients,
            ];
        }

        foreach ($requiredIngredients as $required) {
            if (! array_key_exists((int) $required['ingredient_id'], $context['stock_by_ingredient'])) {
                return [
                    'is_available' => false,
                    'unavailable_reason' => self::REASON_INGREDIENT_NOT_TRANSFERRED,
                    'required_ingredients' => $requiredIngredients,
                ];
            }

            if ((float) $required['remaining_qty'] < (float) $required['required_qty']) {
                return [
                    'is_available' => false,
                    'unavailable_reason' => self::REASON_INSUFFICIENT_STOCK,
                    'required_ingredients' => $requiredIngredients,
                ];
            }
        }

        return [
            'is_available' => true,
            'unavailable_reason' => null,
            'required_ingredients' => $requiredIngredients,
        ];
    }
}

