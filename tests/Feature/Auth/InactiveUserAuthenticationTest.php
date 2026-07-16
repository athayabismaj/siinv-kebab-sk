<?php

namespace Tests\Feature\Auth;

use App\Models\ApiToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InactiveUserAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_login_through_web_or_api(): void
    {
        $user = $this->createAdmin('inactive_login');
        $user->delete();

        $this->from(route('login'))
            ->post(route('login.process'), [
                'username' => $user->username,
                'password' => 'secret123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'secret123',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun Anda telah dinonaktifkan.');
    }

    public function test_existing_web_session_is_rejected_after_user_is_soft_deleted(): void
    {
        $user = $this->createAdmin('inactive_session');

        $this->actingAs($user)->get(route('admin.panel'))->assertOk();

        $user->delete();

        $this->get(route('admin.panel'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_token_middleware_rejects_soft_deleted_user_even_before_token_cleanup(): void
    {
        $user = $this->createAdmin('inactive_token');
        $plainToken = 'inactive_token_'.bin2hex(random_bytes(8));

        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'inactive-test',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDay(),
        ]);
        $user->delete();

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    private function createAdmin(string $username): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'admin']);

        return User::factory()->create([
            'name' => ucfirst($username),
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => Hash::make('secret123'),
            'role_id' => $role->id,
        ]);
    }
}
