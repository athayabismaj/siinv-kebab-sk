<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchContextAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_switch_to_an_unassigned_branch_from_the_request(): void
    {
        $adminRole = Role::query()->create(['name' => 'admin']);
        $branchA = Branch::query()->where('code', 'default')->firstOrFail();
        $branchB = Branch::query()->create([
            'name' => 'Kebab SK Jepara',
            'code' => 'jpr',
            'is_active' => true,
        ]);
        $admin = User::query()->create([
            'name' => 'Admin Cabang A',
            'username' => 'admin_cabang_a',
            'email' => 'admin-cabang-a@example.test',
            'password' => 'secret123',
            'role_id' => $adminRole->id,
            'branch_id' => $branchA->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.panel'))
            ->post(route('admin.branch-context.switch'), ['branch_id' => $branchB->id])
            ->assertRedirect(route('admin.panel'))
            ->assertSessionHas('error', 'Cabang tidak tersedia untuk akun admin ini.');

        $this->assertNotSame($branchB->id, session('active_branch_id'));
    }
}
