<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionCodeSequenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_generates_daily_sequential_transaction_code_and_resets_next_day(): void
    {
        [$cashier, $token] = $this->createUserWithToken('kasir');
        [$variant, $ingredient] = $this->createSellableVariant(price: 10000, requiredQty: 1);
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);

        try {
            Carbon::setTestNow(Carbon::parse('2026-07-10 09:00:00', 'Asia/Jakarta'));
            $this->openSession($cashier->id, '2026-07-10', $ingredient->id);

            $first = $this->checkout($token, $payment->id, $variant->id);
            $second = $this->checkout($token, $payment->id, $variant->id);

            $first->assertCreated()->assertJsonPath('data.transaction_code', 'TRX-20260710-001');
            $second->assertCreated()->assertJsonPath('data.transaction_code', 'TRX-20260710-002');

            Carbon::setTestNow(Carbon::parse('2026-07-11 09:00:00', 'Asia/Jakarta'));
            $this->openSession($cashier->id, '2026-07-11', $ingredient->id);

            $third = $this->checkout($token, $payment->id, $variant->id);
            $third->assertCreated()->assertJsonPath('data.transaction_code', 'TRX-20260711-001');

            $this->assertDatabaseHas('transaction_sequences', [
                'sequence_date' => '2026-07-10',
                'last_number' => 2,
            ]);
            $this->assertDatabaseHas('transaction_sequences', [
                'sequence_date' => '2026-07-11',
                'last_number' => 1,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    private function checkout(string $token, int $paymentMethodId, int $variantId)
    {
        return $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/transactions', [
                'payment_method_id' => $paymentMethodId,
                'paid_amount' => 10000,
                'items' => [
                    [
                        'variant_id' => $variantId,
                        'qty' => 1,
                    ],
                ],
            ]);
    }

    /**
     * @return array{User,string}
     */
    private function createUserWithToken(string $roleName): array
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $plainToken = 'tok_' . bin2hex(random_bytes(12));
        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'test-token',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(7),
        ]);

        return [$user, $plainToken];
    }

    /**
     * @return array{MenuVariant,Ingredient}
     */
    private function createSellableVariant(float $price, float $requiredQty): array
    {
        $menu = Menu::query()->create([
            'name' => 'Kebab Mini',
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Mini',
            'price' => $price,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $ingredient = Ingredient::query()->create([
            'name' => 'Bahan ' . uniqid(),
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 100,
            'minimum_stock' => 0,
            'selling_price' => 1000,
        ]);

        DB::table('menu_variant_ingredients')->insert([
            'menu_variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => $requiredQty,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$variant, $ingredient];
    }

    private function openSession(int $cashierId, string $sessionDate, int $ingredientId): DailyStockSession
    {
        $session = DailyStockSession::query()->create([
            'session_date' => $sessionDate,
            'cashier_id' => $cashierId,
            'opened_by' => $cashierId,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredientId,
            'opening_qty' => 10,
            'remaining_qty' => 10,
            'used_qty' => 0,
            'returned_qty' => 0,
        ]);

        return $session;
    }
}
