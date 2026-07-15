<?php

namespace Tests\Feature\API\Android;

use App\Models\ApiToken;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AndroidReadContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_payment_methods_and_menu_keep_android_envelopes(): void
    {
        [, $token] = $this->createCashierWithToken();
        $payment = PaymentMethod::query()->create(['name' => 'Cash']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/payment-methods')
            ->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('success', true)
                ->whereType('message', 'string')
                ->whereType('data.user.id', 'integer')
                ->whereType('data.payment_methods', 'array')
                ->where('data.payment_methods.0.id', $payment->id)
                ->where('data.payment_methods.0.name', 'Cash')
                ->etc());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/menus')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'kasir')
            ->assertJsonPath('data.menus', []);
    }

    public function test_closed_stock_session_and_empty_history_keep_shapes(): void
    {
        [, $token] = $this->createCashierWithToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/daily-stock-items')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session_id', null)
            ->assertJsonPath('data.items', []);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/sessions/current-status')
            ->assertNotFound()
            ->assertJsonPath('active', false)
            ->assertJsonPath('message', 'Tidak ada sesi aktif untuk user ini.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/transactions?page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.last_page', 1)
            ->assertJsonPath('data.data', []);
    }

    public function test_protected_android_reads_reject_missing_token(): void
    {
        foreach (['/api/menus', '/api/payment-methods', '/api/daily-stock-items', '/api/transactions'] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }
    }

    /**
     * @return array{User, string}
     */
    private function createCashierWithToken(): array
    {
        $branch = Branch::query()->firstOrCreate(
            ['code' => 'FIX'],
            ['name' => 'Cabang Fixture', 'address' => null, 'is_active' => true]
        );
        $role = Role::query()->firstOrCreate(['name' => 'kasir']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
        $token = 'read_contract_'.bin2hex(random_bytes(8));
        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'android-contract-test',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        return [$user, $token];
    }
}
