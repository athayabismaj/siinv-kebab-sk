<?php

namespace Tests\Feature\Owner;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\DailyStockSession;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_managed_users_and_normalizes_invalid_status(): void
    {
        $owner = $this->createUser('owner', 'owner_filter');
        $activeAdmin = $this->createUser('admin', 'admin_active');
        $inactiveCashier = $this->createUser('kasir', 'cashier_inactive');
        $this->createUser('developer', 'developer_hidden');
        $inactiveCashier->delete();

        $default = $this->actingAs($owner)->get(route('owner.users.index'));
        $this->assertUserList($default, 'active', [$activeAdmin->id], 1, 1, 2);

        $active = $this->get(route('owner.users.index', ['status' => 'active']));
        $this->assertUserList($active, 'active', [$activeAdmin->id], 1, 1, 2);

        $inactive = $this->get(route('owner.users.index', ['status' => 'inactive']));
        $this->assertUserList($inactive, 'inactive', [$inactiveCashier->id], 1, 1, 2);

        $all = $this->get(route('owner.users.index', ['status' => 'all']));
        $this->assertUserList($all, 'all', [$activeAdmin->id, $inactiveCashier->id], 1, 1, 2);

        $invalid = $this->get(route('owner.users.index', ['status' => 'unexpected']));
        $this->assertUserList($invalid, 'active', [$activeAdmin->id], 1, 1, 2);
    }

    public function test_inactive_pagination_keeps_status_and_eager_loads_branch_relations(): void
    {
        $owner = $this->createUser('owner', 'owner_pagination');
        $branch = $this->createBranch('PAG');

        foreach (range(1, 11) as $index) {
            $user = $this->createUser('kasir', 'inactive_'.$index, $branch);
            $user->delete();
        }

        $response = $this->actingAs($owner)->get(route('owner.users.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $paginator = $response->viewData('users');

        $this->assertStringContainsString('status=inactive', (string) $paginator->nextPageUrl());
        $this->assertTrue($paginator->getCollection()->every(
            fn (User $user): bool => $user->relationLoaded('role')
                && $user->relationLoaded('branch')
                && $user->relationLoaded('assignedBranches')
        ));
    }

    public function test_archive_route_redirects_to_unified_inactive_filter_and_navigation_has_no_archive_menu(): void
    {
        $owner = $this->createUser('owner', 'owner_archive');

        $this->actingAs($owner)
            ->get(route('owner.users.archive'))
            ->assertRedirect(route('owner.users.index', ['status' => 'inactive']));

        $this->get(route('owner.users.index'))
            ->assertOk()
            ->assertSee('Aktif')
            ->assertSee('Nonaktif')
            ->assertSee('Semua')
            ->assertDontSee('Arsip Pengguna');
    }

    public function test_inactive_rows_only_render_restore_action_on_mobile_and_desktop(): void
    {
        $owner = $this->createUser('owner', 'owner_view');
        $inactive = $this->createUser('kasir', 'inactive_view');
        $inactive->delete();

        $response = $this->actingAs($owner)->get(route('owner.users.index', [
            'status' => 'inactive',
        ]));

        $response->assertOk()
            ->assertSee('Nonaktif')
            ->assertSee('Aktifkan Kembali')
            ->assertSee('AKTIFKAN')
            ->assertDontSee(route('owner.users.edit', $inactive->id), false)
            ->assertDontSee(route('owner.users.reset.form', $inactive->id), false)
            ->assertDontSee("openUserDestroy('".route('owner.users.destroy', $inactive->id)."'", false);
    }

    public function test_deactivation_requires_exact_confirmation(): void
    {
        $owner = $this->createUser('owner', 'owner_confirmation');

        foreach ([null, 'nonaktif', 'SALAH'] as $index => $confirmation) {
            $user = $this->createUser('kasir', 'confirm_'.$index);
            $payload = $confirmation === null ? [] : ['destroy_confirmation' => $confirmation];

            $this->actingAs($owner)
                ->from(route('owner.users.index'))
                ->delete(route('owner.users.destroy', $user), $payload)
                ->assertRedirect(route('owner.users.index'))
                ->assertSessionHasErrors('destroy_confirmation');

            $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        }
    }

    public function test_deactivation_is_atomic_revokes_tokens_and_preserves_history_and_branches(): void
    {
        $owner = $this->createUser('owner', 'owner_deactivate');
        $branch = $this->createBranch('HIS');
        $cashier = $this->createUser('kasir', 'history_cashier', $branch);
        $cashier->assignedBranches()->sync([$branch->id]);
        $token = $this->createApiToken($cashier);
        [$transaction, $session] = $this->createHistoricalRows($cashier, $owner, $branch);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk();

        $this->actingAs($owner)
            ->delete(route('owner.users.destroy', $cashier), [
                'destroy_confirmation' => 'NONAKTIF',
            ])
            ->assertRedirect(route('owner.users.index'))
            ->assertSessionHas('success', 'Pengguna berhasil dinonaktifkan.');

        $this->assertSoftDeleted('users', ['id' => $cashier->id]);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $cashier->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'user_id' => $cashier->id]);
        $this->assertDatabaseHas('daily_stock_sessions', ['id' => $session->id, 'cashier_id' => $cashier->id]);
        $this->assertDatabaseHas('branch_user', ['user_id' => $cashier->id, 'branch_id' => $branch->id]);
        $this->assertSame($cashier->id, $transaction->fresh()->user?->id);
        $this->assertSame($cashier->name, $session->fresh()->cashier?->name);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_restore_requires_exact_confirmation_and_rejects_active_user(): void
    {
        $owner = $this->createUser('owner', 'owner_restore_validation');

        foreach ([null, 'aktifkan', 'SALAH'] as $index => $confirmation) {
            $user = $this->createUser('admin', 'restore_confirm_'.$index);
            $user->delete();
            $payload = $confirmation === null ? [] : ['restore_confirmation' => $confirmation];

            $this->actingAs($owner)
                ->from(route('owner.users.index', ['status' => 'inactive']))
                ->patch(route('owner.users.restore', $user->id), $payload)
                ->assertRedirect(route('owner.users.index', ['status' => 'inactive']))
                ->assertSessionHasErrors('restore_confirmation');

            $this->assertSoftDeleted('users', ['id' => $user->id]);
        }

        $active = $this->createUser('admin', 'already_active');

        $this->actingAs($owner)
            ->patch(route('owner.users.restore', $active->id), [
                'restore_confirmation' => 'AKTIFKAN',
            ])
            ->assertNotFound();
    }

    public function test_restore_preserves_password_branches_and_history_without_recreating_tokens(): void
    {
        $owner = $this->createUser('owner', 'owner_restore');
        $branch = $this->createBranch('RST');
        $cashier = $this->createUser('kasir', 'restore_cashier', $branch);
        $passwordHash = $cashier->password;
        $cashier->assignedBranches()->sync([$branch->id]);
        [$transaction, $session] = $this->createHistoricalRows($cashier, $owner, $branch);
        $cashier->delete();

        $this->actingAs($owner)
            ->patch(route('owner.users.restore', $cashier->id), [
                'restore_confirmation' => 'AKTIFKAN',
            ])
            ->assertRedirect(route('owner.users.index', ['status' => 'active']))
            ->assertSessionHas('success', 'Pengguna berhasil diaktifkan kembali.');

        $restored = User::query()->findOrFail($cashier->id);
        $this->assertSame($passwordHash, $restored->password);
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $cashier->id]);
        $this->assertDatabaseHas('branch_user', ['user_id' => $cashier->id, 'branch_id' => $branch->id]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'user_id' => $cashier->id]);
        $this->assertDatabaseHas('daily_stock_sessions', ['id' => $session->id, 'cashier_id' => $cashier->id]);
    }

    public function test_owner_and_developer_accounts_cannot_be_deactivated_or_restored_directly(): void
    {
        $actor = $this->createUser('owner', 'owner_actor');

        foreach (['owner', 'developer'] as $role) {
            $target = $this->createUser($role, 'protected_'.$role);

            $this->actingAs($actor)
                ->delete(route('owner.users.destroy', $target), [
                    'destroy_confirmation' => 'NONAKTIF',
                ])
                ->assertForbidden();

            $target->delete();

            $this->actingAs($actor)
                ->patch(route('owner.users.restore', $target->id), [
                    'restore_confirmation' => 'AKTIFKAN',
                ])
                ->assertForbidden();
        }
    }

    public function test_soft_deleted_username_and_email_remain_reserved(): void
    {
        $owner = $this->createUser('owner', 'owner_unique');
        $inactive = $this->createUser('kasir', 'reserved_identity');
        $inactive->delete();

        $this->actingAs($owner)
            ->post(route('owner.users.store'), [
                'name' => 'Duplicate Identity',
                'username' => $inactive->username,
                'email' => $inactive->email,
                'password' => 'secret123',
                'role_id' => $this->role('kasir')->id,
            ])
            ->assertSessionHasErrors(['username', 'email']);

        $this->assertSame(1, User::withTrashed()->where('username', $inactive->username)->count());
        $this->assertSame(1, User::withTrashed()->where('email', $inactive->email)->count());
    }

    public function test_user_index_query_growth_stays_flat(): void
    {
        $owner = $this->createUser('owner', 'owner_queries');
        $branch = $this->createBranch('QRY');
        $this->createUser('kasir', 'query_one', $branch);

        $oneUserQueries = $this->indexQueryCount($owner);

        foreach (range(2, 10) as $index) {
            $this->createUser('kasir', 'query_'.$index, $branch);
        }

        $tenUserQueries = $this->indexQueryCount($owner);

        $this->assertLessThanOrEqual($oneUserQueries + 1, $tenUserQueries);
    }

    private function assertUserList($response, string $status, array $expectedIds, int $active, int $inactive, int $all): void
    {
        $response->assertOk()
            ->assertViewHas('status', $status)
            ->assertViewHas('activeCount', $active)
            ->assertViewHas('inactiveCount', $inactive)
            ->assertViewHas('allCount', $all);

        $actualIds = collect($response->viewData('users')->items())
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        sort($expectedIds);
        $this->assertSame($expectedIds, $actualIds);
    }

    private function createHistoricalRows(User $cashier, User $owner, Branch $branch): array
    {
        $payment = PaymentMethod::query()->firstOrCreate(['name' => 'Cash']);
        $transaction = Transaction::query()->create([
            'transaction_code' => 'TRX-'.$branch->code.'-'.strtoupper($cashier->username),
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'total_amount' => 10000,
            'payment_method_id' => $payment->id,
            'paid_amount' => 10000,
            'change_amount' => 0,
            'status' => 'SUCCESS',
        ]);
        $session = DailyStockSession::query()->create([
            'session_date' => now()->toDateString(),
            'branch_id' => $branch->id,
            'cashier_id' => $cashier->id,
            'opened_by' => $owner->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return [$transaction, $session];
    }

    private function createApiToken(User $user): string
    {
        $plainToken = 'user_lifecycle_'.bin2hex(random_bytes(8));

        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'lifecycle-test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return $plainToken;
    }

    private function createUser(string $roleName, string $username, ?Branch $branch = null): User
    {
        return User::factory()->create([
            'name' => str_replace('_', ' ', ucfirst($username)),
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => Hash::make('secret123'),
            'role_id' => $this->role($roleName)->id,
            'branch_id' => $branch?->id,
        ]);
    }

    private function role(string $name): Role
    {
        return Role::query()->firstOrCreate(['name' => $name]);
    }

    private function createBranch(string $code): Branch
    {
        return Branch::query()->create([
            'name' => 'Cabang '.$code,
            'code' => $code,
            'address' => 'Alamat '.$code,
            'is_active' => true,
        ]);
    }

    private function indexQueryCount(User $owner): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($owner)->get(route('owner.users.index'))->assertOk();
        $count = count(DB::getQueryLog());

        DB::disableQueryLog();
        DB::flushQueryLog();

        return $count;
    }
}
