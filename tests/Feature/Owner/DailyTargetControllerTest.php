<?php

namespace Tests\Feature\Owner;

use App\Models\DailyTarget;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyTargetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_daily_target_with_leading_zero_numbers(): void
    {
        $owner = $this->createOwnerUser();

        $response = $this->actingAs($owner)->post(route('owner.targets.store'), [
            'target_date' => '2026-07-05',
            'target_revenue' => '020000',
            'target_transactions' => '010',
            'notes' => 'Target harian',
        ]);

        $response->assertRedirect(route('owner.targets.index', ['date' => '2026-07-05']));
        $response->assertSessionHasNoErrors();

        $target = DailyTarget::query()->whereDate('target_date', '2026-07-05')->firstOrFail();

        $this->assertSame('20000.00', (string) $target->target_revenue);
        $this->assertSame(10, $target->target_transactions);
        $this->assertSame($owner->id, $target->set_by_user_id);
    }

    public function test_owner_gets_readable_message_for_invalid_transaction_target(): void
    {
        $owner = $this->createOwnerUser();

        $response = $this->actingAs($owner)
            ->from(route('owner.targets.index', ['date' => '2026-07-05']))
            ->post(route('owner.targets.store'), [
                'target_date' => '2026-07-05',
                'target_revenue' => '20000',
                'target_transactions' => '10.5',
            ]);

        $response->assertRedirect(route('owner.targets.index', ['date' => '2026-07-05']));
        $response->assertSessionHasErrors([
            'target_transactions' => 'Target jumlah transaksi harus berupa angka bulat.',
        ]);
    }

    private function createOwnerUser(): User
    {
        $role = Role::query()->create(['name' => 'owner']);

        return User::query()->create([
            'name' => 'Owner',
            'username' => 'owner',
            'email' => 'owner@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
        ]);
    }
}
