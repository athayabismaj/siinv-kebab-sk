<?php

namespace Tests\Feature\API\Android;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AndroidAuthenticationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_typed_user_and_branch_contract(): void
    {
        [$user, $branch] = $this->createCashier();

        $response = $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'secret123',
            'device_name' => 'android-contract-test',
        ]);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->whereType('message', 'string')
                ->whereType('data.token', 'string')
                ->where('data.token_type', 'Bearer')
                ->whereType('data.expires_at', 'string')
                ->whereType('data.user.id', 'integer')
                ->where('data.user.username', $user->username)
                ->where('data.user.role', 'kasir')
                ->where('data.user.branch.id', $branch->id)
                ->where('data.user.branch.name', $branch->name)
                ->where('data.user.branch.code', $branch->code)
                ->etc());
    }

    public function test_login_validation_and_invalid_credentials_keep_error_contract(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Data yang dikirim tidak valid.')
            ->assertJsonPath('data.errors.username.0', 'validation.required')
            ->assertJsonPath('data.errors.password.0', 'validation.required');

        [$user] = $this->createCashier();

        $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Username atau password salah.');
    }

    public function test_profile_returns_nullable_branch_contract(): void
    {
        [$user, $branch, $token] = $this->createCashier(withToken: true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name)
            ->assertJsonPath('data.branch.code', $branch->code);

        $user->update(['branch_id' => null]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.branch', null);
    }

    public function test_profile_update_preserves_branch_contract(): void
    {
        [$user, $branch, $token] = $this->createCashier(withToken: true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/auth/profile', [
                'name' => 'Kasir Kontrak',
                'username' => 'kasir_contract_updated',
                'email' => 'kasir.contract.updated@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('data.role', 'kasir')
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name)
            ->assertJsonPath('data.branch.code', $branch->code);
    }

    public function test_logout_revokes_current_token_and_old_token_is_rejected(): void
    {
        [$user, , $token] = $this->createCashier(withToken: true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout berhasil.');

        $this->assertDatabaseMissing('api_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();

        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }

    /**
     * @return array{0: User, 1: Branch, 2?: string}
     */
    private function createCashier(bool $withToken = false): array
    {
        $branch = Branch::query()->create([
            'name' => 'Cabang Fixture',
            'code' => 'FIX',
            'address' => 'Alamat pengujian',
            'is_active' => true,
        ]);
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $user = User::factory()->create([
            'name' => 'Kasir Fixture',
            'username' => 'kasir_fixture',
            'email' => 'kasir.fixture@example.test',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);

        if (! $withToken) {
            return [$user, $branch];
        }

        $plainToken = 'contract_token_'.bin2hex(random_bytes(8));
        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'android-contract-test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);

        return [$user, $branch, $plainToken];
    }
}
