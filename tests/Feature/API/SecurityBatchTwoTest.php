<?php

namespace Tests\Feature\API;

use App\Models\ApiToken;
use App\Models\BackupHistory;
use App\Models\Branch;
use App\Models\DailyStockItem;
use App\Models\DailyStockSession;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityBatchTwoTest extends TestCase
{
    use RefreshDatabase;

    private ?Branch $branch = null;

    public function test_non_developer_cannot_access_backup_routes(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->get('/developer/backups')
            ->assertForbidden();
    }

    public function test_backup_download_requires_developer_role(): void
    {
        $admin = $this->createUser('admin');
        $developer = $this->createUser('developer');
        $backupPath = storage_path('app/private/backups/test-secure.backup');

        File::ensureDirectoryExists(dirname($backupPath), 0750, true);
        File::put($backupPath, 'fake-backup-content');

        $backup = BackupHistory::query()->create([
            'file_name' => 'test-secure.backup',
            'file_path' => $backupPath,
            'file_size' => File::size($backupPath),
            'status' => 'success',
            'user_id' => $developer->id,
        ]);

        $this->actingAs($admin)
            ->get(route('developer.backups.download', $backup->id))
            ->assertForbidden();

        $this->actingAs($developer)
            ->get(route('developer.backups.download', $backup->id))
            ->assertOk();
    }

    public function test_restore_upload_rejects_non_backup_file(): void
    {
        $developer = $this->createUser('developer');

        $response = $this->actingAs($developer)->post(route('developer.backups.restore-upload'), [
            'backup_file' => UploadedFile::fake()->create('not-a-backup.txt', 1, 'text/plain'),
            'restore_confirmation' => 'RESTORE',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'File restore ditolak. Gunakan file backup PostgreSQL dengan ekstensi .backup atau .dump.');
    }

    public function test_invalid_restore_error_is_sanitized(): void
    {
        $developer = $this->createUser('developer');

        $response = $this->actingAs($developer)->post(route('developer.backups.restore-upload'), [
            'backup_file' => UploadedFile::fake()->create('invalid.backup', 1, 'application/octet-stream'),
            'restore_confirmation' => 'RESTORE',
        ]);

        $response->assertRedirect();
        $error = (string) session('error');

        $this->assertSame('File backup tidak valid atau tidak dapat dibaca.', $error);
        $this->assertStringNotContainsString(base_path(), $error);
        $this->assertStringNotContainsString((string) env('DB_USERNAME'), $error);
        $this->assertStringNotContainsString('pg_restore', $error);
    }

    public function test_change_password_revokes_all_existing_api_tokens(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('old-password'),
        ]);

        $tokenA = $this->createApiToken($user);
        $tokenB = $this->createApiToken($user);

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->postJson('/api/auth/change-password', [
                'current_password' => 'old-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('api_tokens', ['user_id' => $user->id]);

        $this->withHeader('Authorization', 'Bearer ' . $tokenB)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_cashier_cannot_void_another_cashiers_transaction(): void
    {
        [$cashierA, $tokenA] = $this->createUserWithToken('kasir');
        [$cashierB] = $this->createUserWithToken('kasir');
        [$transaction, $session] = $this->createVoidableTransaction($cashierB);

        $this->withHeader('Authorization', 'Bearer ' . $tokenA)
            ->withHeader('X-Idempotency-Key', 'void-cross-user')
            ->postJson('/api/transactions/' . $transaction->id . '/void', [
                'current_session_id' => $session->id,
                'reason' => 'restock',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses tidak diizinkan.');

        $this->assertSame('SUCCESS', $transaction->fresh()->status);
    }

    public function test_cashier_cannot_void_transaction_with_wrong_session_id(): void
    {
        [$cashier, $token] = $this->createUserWithToken('kasir');
        [$transaction] = $this->createVoidableTransaction($cashier);
        $wrongSession = $this->openSession($cashier->id, now('Asia/Jakarta')->subDay()->toDateString());

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Idempotency-Key', 'void-wrong-session')
            ->postJson('/api/transactions/' . $transaction->id . '/void', [
                'current_session_id' => $wrongSession->id,
                'reason' => 'restock',
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses tidak diizinkan.');

        $this->assertSame('SUCCESS', $transaction->fresh()->status);
    }

    public function test_already_void_transaction_cannot_be_voided_again(): void
    {
        [$cashier, $token] = $this->createUserWithToken('kasir');
        [$transaction, $session] = $this->createVoidableTransaction($cashier);
        $transaction->update(['status' => 'VOID']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Idempotency-Key', 'void-already-void')
            ->postJson('/api/transactions/' . $transaction->id . '/void', [
                'current_session_id' => $session->id,
                'reason' => 'restock',
            ])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Transaksi sudah dibatalkan sebelumnya.');
    }

    public function test_successful_void_records_actor_reason_and_updates_daily_stock(): void
    {
        [$cashier, $token] = $this->createUserWithToken('kasir');
        [$transaction, $session, $ingredient] = $this->createVoidableTransaction($cashier);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Idempotency-Key', 'void-success-audit')
            ->postJson('/api/transactions/' . $transaction->id . '/void', [
                'current_session_id' => $session->id,
                'reason' => 'restock',
            ])
            ->assertOk();

        $transaction->refresh();
        $this->assertSame('VOID', $transaction->status);
        $this->assertSame($cashier->id, $transaction->voided_by);
        $this->assertSame('restock', $transaction->void_reason);
        $this->assertNotNull($transaction->voided_at);

        $this->assertDatabaseHas('cashflow_entries', [
            'type' => 'expense',
            'source' => 'Transaction Void',
            'amount' => 50000,
            'created_by' => $cashier->id,
        ]);

        $this->assertDatabaseHas('daily_stock_items', [
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'remaining_qty' => 10,
            'used_qty' => 0,
        ]);
    }

    public function test_public_directory_does_not_contain_sensitive_database_or_env_files(): void
    {
        $sensitiveExtensions = ['env', 'log', 'sql', 'dump', 'backup', 'zip'];
        $files = File::allFiles(public_path());

        foreach ($files as $file) {
            $this->assertNotContains(strtolower($file->getExtension()), $sensitiveExtensions, $file->getPathname());
        }
    }

    /**
     * @return array{User,string}
     */
    private function createUserWithToken(string $roleName): array
    {
        $user = $this->createUser($roleName);

        return [$user, $this->createApiToken($user)];
    }

    private function createUser(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $this->testBranch()->id,
        ]);
    }

    private function createApiToken(User $user): string
    {
        $plainToken = 'tok_' . bin2hex(random_bytes(12));
        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'test-token',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return $plainToken;
    }

    /**
     * @return array{Transaction,DailyStockSession,Ingredient}
     */
    private function createVoidableTransaction(User $cashier): array
    {
        $session = $this->openSession($cashier->id);
        $payment = PaymentMethod::query()->create(['name' => 'Cash ' . uniqid()]);
        [$variant, $ingredient] = $this->createSellableVariant(price: 25000, requiredQty: 1);

        DailyStockItem::query()->create([
            'daily_stock_session_id' => $session->id,
            'ingredient_id' => $ingredient->id,
            'opening_qty' => 10,
            'remaining_qty' => 8,
            'used_qty' => 2,
            'returned_qty' => 0,
        ]);

        $transaction = Transaction::query()->create([
            'transaction_code' => 'TRX-' . uniqid(),
            'user_id' => $cashier->id,
            'total_amount' => 50000,
            'payment_method_id' => $payment->id,
            'paid_amount' => 50000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
            'daily_stock_session_id' => $session->id,
            'branch_id' => $session->branch_id,
        ]);

        TransactionDetail::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $variant->menu_id,
            'menu_variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 25000,
            'subtotal' => 50000,
        ]);

        return [$transaction, $session, $ingredient];
    }

    /**
     * @return array{MenuVariant,Ingredient}
     */
    private function createSellableVariant(float $price, float $requiredQty): array
    {
        $menu = Menu::query()->create([
            'name' => 'Menu Recipe ' . uniqid(),
            'description' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $variant = MenuVariant::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Regular',
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
            'cost_price' => 1000,
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

    private function openSession(int $cashierId, ?string $sessionDate = null): DailyStockSession
    {
        return DailyStockSession::query()->create([
            'session_date' => $sessionDate ?? now('Asia/Jakarta')->toDateString(),
            'cashier_id' => $cashierId,
            'opened_by' => $cashierId,
            'branch_id' => $this->testBranch()->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    private function testBranch(): Branch
    {
        return $this->branch ??= Branch::query()->firstOrCreate(
            ['code' => 'default'],
            ['name' => 'Kebab SK', 'is_active' => true],
        );
    }
}
