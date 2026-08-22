<?php

namespace Tests\Feature\Owner;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrivilegedPasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_promoting_cashier_to_admin_requires_a_new_strong_password(): void
    {
        $ownerRole = Role::query()->firstOrCreate(['name' => 'owner']);
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);
        $cashierRole = Role::query()->firstOrCreate(['name' => 'kasir']);
        $branch = Branch::query()->create([
            'name' => 'Cabang Password',
            'code' => 'PWD',
            'is_active' => true,
        ]);
        $owner = User::factory()->create(['role_id' => $ownerRole->id, 'branch_id' => $branch->id]);
        $cashier = User::factory()->create(['role_id' => $cashierRole->id, 'branch_id' => $branch->id]);
        $payload = [
            'name' => $cashier->name,
            'username' => $cashier->username,
            'email' => $cashier->email,
            'role_id' => $adminRole->id,
            'branch_id' => $branch->id,
            'branch_ids' => [$branch->id],
        ];

        $this->actingAs($owner)
            ->put(route('owner.users.update', $cashier), $payload)
            ->assertSessionHasErrors('password');
        $this->assertSame($cashierRole->id, (int) $cashier->fresh()->role_id);

        $this->actingAs($owner)
            ->put(route('owner.users.update', $cashier), $payload + ['password' => 'KebabSecure12'])
            ->assertRedirect(route('owner.users.index'));

        $cashier->refresh();
        $this->assertSame($adminRole->id, (int) $cashier->role_id);
        $this->assertTrue(Hash::check('KebabSecure12', $cashier->password));
    }
}
