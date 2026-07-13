<?php

namespace Tests\Feature\Owner;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchCodeSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_code_is_abbreviated_when_owner_leaves_it_empty(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)
            ->post(route('owner.branches.store'), [
                'name' => 'Kudus',
                'code' => '',
                'is_active' => true,
            ])
            ->assertRedirect(route('owner.branches.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branches', [
            'name' => 'Kudus',
            'code' => 'kds',
        ]);
    }

    public function test_brand_words_are_ignored_when_generating_branch_code(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)
            ->post(route('owner.branches.store'), [
                'name' => 'Kebab SK Jepara',
                'code' => '',
                'is_active' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branches', [
            'name' => 'Kebab SK Jepara',
            'code' => 'jpr',
        ]);
    }

    private function createOwner(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'owner']);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
