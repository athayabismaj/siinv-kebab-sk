<?php

namespace Tests\Feature\Admin;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\IngredientCategory;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DailyStockFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_stock_successfully_reduces_warehouse_stock(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset();

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'transfers' => [
                $ingredient->id => [
                    'quantity' => 20,
                    'transfer_unit' => 'pcs',
                ],
            ],
        ])->assertRedirect();

        $ingredient->refresh();
        $this->assertSame(80.0, (float) $ingredient->stock);

        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 20.00,
            'remaining_qty' => 20.00,
        ]);

        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'transfer_daily',
            'quantity' => -20.00,
            'reference_id' => $session->id,
        ]);
    }

    public function test_transfer_fails_when_warehouse_stock_is_insufficient(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 10);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('admin.daily-stocks.index', [
                'date' => now()->toDateString(),
                'cashier_id' => $cashier->id,
            ]))
            ->post(route('admin.daily-stocks.transfer'), [
                'session_id' => $session->id,
                'transfers' => [
                    $ingredient->id => [
                        'quantity' => 50,
                        'transfer_unit' => 'pcs',
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $ingredient->refresh();
        $this->assertSame(10.0, (float) $ingredient->stock);
        $this->assertDatabaseCount('daily_stock_items', 0);
        $this->assertDatabaseCount('stock_logs', 0);
    }

    public function test_batch_transfer_saves_valid_items_and_warns_for_insufficient_stock(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 30);
        $insufficientIngredient = Ingredient::create([
            'category_id' => $ingredient->category_id,
            'name' => 'Saus Terbatas',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => 5,
            'minimum_stock' => 2,
        ]);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'transfers' => [
                $ingredient->id => [
                    'quantity' => 20,
                    'transfer_unit' => 'pcs',
                ],
                $insufficientIngredient->id => [
                    'quantity' => 10,
                    'transfer_unit' => 'pcs',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('warning');
        $this->assertStringContainsString('Saus Terbatas', session('warning'));

        $ingredient->refresh();
        $insufficientIngredient->refresh();

        $this->assertSame(10.0, (float) $ingredient->stock);
        $this->assertSame(5.0, (float) $insufficientIngredient->stock);

        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 20.00,
            'remaining_qty' => 20.00,
        ]);
        $this->assertDatabaseMissing('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $insufficientIngredient->id,
        ]);
        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'transfer_daily',
            'quantity' => -20.00,
            'reference_id' => $session->id,
        ]);
        $this->assertDatabaseMissing('stock_logs', [
            'ingredient_id' => $insufficientIngredient->id,
            'type' => 'transfer_daily',
            'reference_id' => $session->id,
        ]);
    }

    public function test_transfer_requires_positive_batch_quantity(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset();

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.daily-stocks.transfer'), [
                'session_id' => $session->id,
                'transfers' => [
                    $ingredient->id => [
                        'quantity' => 0,
                        'transfer_unit' => 'pcs',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $ingredient->refresh();
        $this->assertSame(100.0, (float) $ingredient->stock);
        $this->assertDatabaseCount('daily_stock_items', 0);
    }

    public function test_close_session_calculates_usage_correctly(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 100);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'transfers' => [
                $ingredient->id => [
                    'quantity' => 30,
                    'transfer_unit' => 'pcs',
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.daily-stocks.close'), [
            'session_id' => $session->id,
            'remaining' => [
                $ingredient->id => 10,
            ],
        ])->assertRedirect();

        $session->refresh();
        $item = DailyStockItem::query()->firstOrFail();
        $ingredient->refresh();

        $this->assertSame('closed', $session->status);
        $this->assertSame(30.0, (float) $item->opening_qty);
        $this->assertSame(10.0, (float) $item->remaining_qty);
        $this->assertSame(20.0, (float) $item->used_qty);
        $this->assertSame(10.0, (float) $item->returned_qty);

        // 100 - 30 transfer + 10 return = 80
        $this->assertSame(80.0, (float) $ingredient->stock);

        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'daily_usage',
            'quantity' => -20.00,
            'reference_id' => $session->id,
        ]);

        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'daily_return',
            'quantity' => 10.00,
            'reference_id' => $session->id,
        ]);
    }

    public function test_pack_transfer_and_piece_remaining_are_calculated_correctly(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 100);
        $ingredient->update(['pack_size' => 20]);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'transfers' => [
                $ingredient->id => [
                    'quantity' => 2,
                    'transfer_unit' => 'pack',
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.daily-stocks.close'), [
            'session_id' => $session->id,
            'remaining' => [
                $ingredient->id => 10,
            ],
        ])->assertRedirect();

        $item = DailyStockItem::query()->firstOrFail();
        $ingredient->refresh();

        $this->assertSame(40.0, (float) $item->opening_qty);
        $this->assertSame(10.0, (float) $item->remaining_qty);
        $this->assertSame(30.0, (float) $item->used_qty);
        $this->assertSame(10.0, (float) $item->returned_qty);

        // 100 - 40 transfer + 10 return = 70
        $this->assertSame(70.0, (float) $ingredient->stock);

        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'transfer_daily',
            'quantity' => -40.00,
            'reference_id' => $session->id,
        ]);

        $this->assertDatabaseHas('stock_logs', [
            'ingredient_id' => $ingredient->id,
            'type' => 'daily_usage',
            'quantity' => -30.00,
            'reference_id' => $session->id,
        ]);
    }

    public function test_past_date_without_session_is_read_only_for_admin(): void
    {
        [$admin, $cashier] = $this->baseDailyStockDataset();
        $pastDate = now()->subDay()->toDateString();

        $this->actingAs($admin)
            ->get(route('admin.daily-stocks.index', [
                'date' => $pastDate,
                'cashier_id' => $cashier->id,
            ]))
            ->assertOk()
            ->assertSee('Tanggal ini sudah lewat.')
            ->assertDontSee('Buka Sesi Harian Baru');
    }

    public function test_admin_cannot_open_past_daily_stock_session(): void
    {
        [$admin, $cashier] = $this->baseDailyStockDataset();
        $pastDate = now()->subDay()->toDateString();

        $this->actingAs($admin)
            ->from(route('admin.daily-stocks.index', [
                'date' => $pastDate,
                'cashier_id' => $cashier->id,
            ]))
            ->post(route('admin.daily-stocks.open'), [
                'date' => $pastDate,
                'cashier_id' => $cashier->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('daily_stock_sessions', [
            'session_date' => $pastDate,
            'cashier_id' => $cashier->id,
        ]);
    }

    public function test_admin_cannot_open_future_daily_stock_session(): void
    {
        [$admin, $cashier] = $this->baseDailyStockDataset();
        $futureDate = now()->addDay()->toDateString();

        $this->actingAs($admin)
            ->from(route('admin.daily-stocks.index', [
                'date' => $futureDate,
                'cashier_id' => $cashier->id,
            ]))
            ->post(route('admin.daily-stocks.open'), [
                'date' => $futureDate,
                'cashier_id' => $cashier->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('daily_stock_sessions', [
            'session_date' => $futureDate,
            'cashier_id' => $cashier->id,
        ]);
    }

    public function test_admin_cannot_reopen_past_daily_stock_session(): void
    {
        [$admin, $cashier] = $this->baseDailyStockDataset();

        $session = DailyStockSession::query()->create([
            'session_date' => now()->subDay()->toDateString(),
            'cashier_id' => $cashier->id,
            'opened_by' => $admin->id,
            'closed_by' => $admin->id,
            'branch_id' => $cashier->branch_id,
            'status' => 'closed',
            'opened_at' => now()->subDay(),
            'closed_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.daily-stocks.index', [
                'date' => $session->session_date->toDateString(),
                'cashier_id' => $cashier->id,
            ]))
            ->post(route('admin.daily-stocks.reopen'), [
                'session_id' => $session->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('closed', $session->refresh()->status);
    }

    public function test_reopen_reconciles_used_qty_with_transaction_usage_logs(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 500);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'transfers' => [
                $ingredient->id => [
                    'quantity' => 200,
                    'transfer_unit' => 'pcs',
                ],
            ],
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.daily-stocks.close'), [
            'session_id' => $session->id,
            'remaining' => [
                $ingredient->id => 0,
            ],
        ])->assertRedirect();

        $payment = PaymentMethod::create(['name' => 'Tunai']);
        $transaction = Transaction::create([
            'transaction_code' => 'TRX-TEST-0001',
            'user_id' => $cashier->id,
            'total_amount' => 10000,
            'payment_method_id' => $payment->id,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        StockLog::create([
            'ingredient_id' => $ingredient->id,
            'type' => 'daily_usage',
            'quantity' => -25,
            'reference_id' => $transaction->id,
            'note' => 'Pemakaian stok harian dari transaksi drift test',
        ]);

        $itemBefore = DailyStockItem::query()->firstOrFail();
        $this->assertSame(200.0, (float) $itemBefore->used_qty);
        $itemBefore->update([
            'used_qty' => 0,
            'remaining_qty' => 200,
        ]);

        $this->actingAs($admin)->post(route('admin.daily-stocks.reopen'), [
            'session_id' => $session->id,
        ])->assertRedirect();

        $session->refresh();
        $itemAfter = DailyStockItem::query()->firstOrFail();

        $this->assertSame('open', $session->status);
        $this->assertGreaterThanOrEqual(25.0, (float) $itemAfter->used_qty);
        $this->assertGreaterThanOrEqual((float) $itemAfter->used_qty, (float) $itemAfter->opening_qty);
        $this->assertEqualsWithDelta(
            (float) $itemAfter->opening_qty - (float) $itemAfter->used_qty,
            (float) $itemAfter->remaining_qty,
            0.01
        );
    }

    public function test_api_close_session_accepts_list_payload_and_localized_decimal(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 1000);
        $ingredient->update([
            'display_unit' => 'l',
            'base_unit' => 'ml',
        ]);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'transfers' => [
                $ingredient->id => [
                    'quantity' => 0.2,
                    'transfer_unit' => 'l',
                ],
            ],
        ])->assertRedirect();

        $apiToken = $this->createApiTokenForUser($cashier);

        $response = $this->postJson('/api/daily-stock-sessions/close', [
            'remaining' => [
                [
                    'ingredient_id' => $ingredient->id,
                    'remaining_qty' => '0,15',
                ],
            ],
        ], [
            'Authorization' => 'Bearer ' . $apiToken,
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
        ]);

        $session->refresh();
        $item = DailyStockItem::query()->firstOrFail();

        $this->assertSame('closed', $session->status);
        $this->assertSame(200.0, (float) $item->opening_qty);
        $this->assertSame(150.0, (float) $item->remaining_qty);
        $this->assertSame(50.0, (float) $item->used_qty);
        $this->assertSame(150.0, (float) $item->returned_qty);
    }

    public function test_admin_can_trigger_manual_reconcile_action(): void
    {
        [$admin, $cashier, $ingredient] = $this->baseDailyStockDataset(stock: 300);

        $this->actingAs($admin)->post(route('admin.daily-stocks.open'), [
            'date' => now()->toDateString(),
            'cashier_id' => $cashier->id,
        ])->assertRedirect();

        $session = DailyStockSession::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.daily-stocks.transfer'), [
            'session_id' => $session->id,
            'transfers' => [
                $ingredient->id => [
                    'quantity' => 100,
                    'transfer_unit' => 'pcs',
                ],
            ],
        ])->assertRedirect();

        DailyStockItem::query()->where('daily_stock_session_id', $session->id)->update([
            'used_qty' => 0,
            'remaining_qty' => 100,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.daily-stocks.reconcile'), [
            'session_id' => $session->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $item = DailyStockItem::query()->firstOrFail();
        $this->assertGreaterThanOrEqual(0.0, (float) $item->used_qty);
        $this->assertEqualsWithDelta(
            (float) $item->opening_qty - (float) $item->used_qty,
            (float) $item->remaining_qty,
            0.01
        );
    }

    /**
     * @return array{0: User, 1: User, 2: Ingredient}
     */
    private function baseDailyStockDataset(float $stock = 100): array
    {
        $adminRole = Role::create(['name' => 'admin']);
        $cashierRole = Role::create(['name' => 'kasir']);
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'default'],
            ['name' => 'Kebab SK', 'is_active' => true],
        );

        $admin = User::create([
            'name' => 'Admin Uji',
            'username' => 'admin_uji',
            'email' => 'admin-uji@example.test',
            'password' => 'secret123',
            'role_id' => $adminRole->id,
            'branch_id' => $branch->id,
        ]);

        $cashier = User::create([
            'name' => 'Kasir Uji',
            'username' => 'kasir_uji',
            'email' => 'kasir-uji@example.test',
            'password' => 'secret123',
            'role_id' => $cashierRole->id,
            'branch_id' => $branch->id,
        ]);

        $category = IngredientCategory::create(['name' => 'Bahan Uji']);
        $ingredient = Ingredient::create([
            'category_id' => $category->id,
            'name' => 'Tortilla',
            'display_unit' => 'pcs',
            'base_unit' => 'pcs',
            'pack_size' => 1,
            'stock' => $stock,
            'minimum_stock' => 5,
        ]);

        return [$admin, $cashier, $ingredient];
    }

    private function createApiTokenForUser(User $user): string
    {
        $plainToken = 'test-token-' . Str::random(32);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'test-device',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return $plainToken;
    }
}
