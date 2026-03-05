<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Login');
    }

    public function test_admin_can_login_and_is_redirected_to_admin_panel(): void
    {
        $adminRole = Role::create(['name' => 'admin']);

        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'admin_test',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $adminRole->id,
        ]);

        $response = $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.panel'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_owner_can_login_and_is_redirected_to_owner_panel(): void
    {
        $ownerRole = Role::create(['name' => 'owner']);

        $user = User::create([
            'name' => 'Owner Test',
            'username' => 'owner_test',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $ownerRole->id,
        ]);

        $response = $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('owner.panel'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_for_soft_deleted_user(): void
    {
        $adminRole = Role::create(['name' => 'admin']);

        $user = User::create([
            'name' => 'Disabled User',
            'username' => 'disabled_user',
            'email' => 'disabled@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $adminRole->id,
        ]);

        $user->delete();

        $response = $this->from(route('login'))->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'username' => 'Akun Anda telah dinonaktifkan.',
        ]);
        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $adminRole = Role::create(['name' => 'admin']);

        $user = User::create([
            'name' => 'Admin Test',
            'username' => 'wrong_pass_user',
            'email' => 'wrongpass@example.com',
            'password' => Hash::make('secret123'),
            'role_id' => $adminRole->id,
        ]);

        $response = $this->from(route('login'))->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'not-correct-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'username' => 'Username atau password salah.',
        ]);
        $this->assertGuest();
    }
}
