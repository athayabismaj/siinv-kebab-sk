<?php

namespace Tests\Feature\Qris;

use App\Models\Branch;
use App\Models\QrisConfig;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\QrisTestPayload;
use Tests\TestCase;

class QrisConfigManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_assigned_admin_can_open_qris_settings_but_cashier_is_forbidden(): void
    {
        $branch = $this->branch('A');
        $owner = $this->user('owner', $branch);
        $admin = $this->user('admin', $branch);
        $cashier = $this->user('kasir', $branch);
        $admin->assignedBranches()->attach($branch);

        $this->actingAs($owner)->get(route('owner.qris.index', ['branch_id' => $branch->id]))
            ->assertOk()->assertSee('Pengaturan QRIS');
        $this->actingAs($admin)->get(route('admin.qris.index'))
            ->assertOk()->assertSee($branch->name);
        $this->actingAs($cashier)->get('/admin/qris')->assertForbidden();
        $this->actingAs($cashier)->post('/owner/qris', [
            'branch_id' => $branch->id,
            'qris_payload' => QrisTestPayload::make(),
        ])->assertForbidden();
    }

    public function test_valid_qris_is_encrypted_and_invalid_qris_is_rejected(): void
    {
        $branch = $this->branch('A');
        $owner = $this->user('owner', $branch);
        $payload = QrisTestPayload::make();

        $this->actingAs($owner)->post(route('owner.qris.store'), [
            'branch_id' => $branch->id,
            'qris_payload' => $payload,
        ])->assertRedirect(route('owner.qris.index', ['branch_id' => $branch->id]));

        $config = QrisConfig::query()->firstOrFail();
        $rawPayload = DB::table('qris_configs')->where('id', $config->id)->value('qris_payload');
        $this->assertNotSame($payload, $rawPayload);
        $this->assertSame($payload, $config->qris_payload);
        $this->assertSame('KEBAB SK TEST', $config->merchant_name);
        $this->assertTrue($config->is_active);

        $this->actingAs($owner)->post(route('owner.qris.store'), [
            'branch_id' => $branch->id,
            'qris_payload' => 'invalid',
        ])->assertSessionHasErrors('qris_payload');
        $this->assertSame(1, QrisConfig::query()->count());
    }

    public function test_replacement_keeps_history_and_only_one_config_active(): void
    {
        $branch = $this->branch('A');
        $owner = $this->user('owner', $branch);

        foreach ([
            QrisTestPayload::make('MERCHANT LAMA', 'KUDUS', 'OLDMERCHANT001'),
            QrisTestPayload::make('MERCHANT BARU', 'KUDUS', 'NEWMERCHANT001'),
        ] as $payload) {
            $this->actingAs($owner)->post(route('owner.qris.store'), [
                'branch_id' => $branch->id,
                'qris_payload' => $payload,
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(2, QrisConfig::query()->where('branch_id', $branch->id)->count());
        $this->assertSame(1, QrisConfig::query()->where('branch_id', $branch->id)->active()->count());
        $this->assertDatabaseHas('qris_configs', [
            'branch_id' => $branch->id,
            'merchant_name' => 'MERCHANT LAMA',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('qris_configs', [
            'branch_id' => $branch->id,
            'merchant_name' => 'MERCHANT BARU',
            'is_active' => true,
        ]);
    }

    public function test_truncated_payload_name_is_completed_automatically_from_branch_name(): void
    {
        $branch = $this->branch('A');
        $branch->update(['name' => 'Pekeng']);
        $owner = $this->user('owner', $branch);
        $payloadName = 'SK KEBAB BARAT BRI P...';

        $this->actingAs($owner)->post(route('owner.qris.store'), [
            'branch_id' => $branch->id,
            'qris_payload' => QrisTestPayload::make($payloadName),
        ])->assertSessionHasNoErrors();

        $config = QrisConfig::query()->firstOrFail();
        $this->assertSame($payloadName, $config->merchant_name);
        $this->assertSame('SK KEBAB BARAT BRI Pekeng', $config->merchant_display_name);

        $this->actingAs($owner)
            ->get(route('owner.qris.index', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee('SK KEBAB BARAT BRI Pekeng')
            ->assertDontSee('Lengkapi nama merchant');
    }

    public function test_admin_cannot_use_request_branch_id_to_write_an_unassigned_branch(): void
    {
        $branchA = $this->branch('A');
        $branchB = $this->branch('B');
        $admin = $this->user('admin', $branchA);
        $admin->assignedBranches()->attach($branchA);

        $this->actingAs($admin)->post(route('admin.qris.store'), [
            'branch_id' => $branchB->id,
            'qris_payload' => QrisTestPayload::make(),
        ])->assertForbidden();

        $this->assertDatabaseMissing('qris_configs', ['branch_id' => $branchA->id]);
        $this->assertDatabaseMissing('qris_configs', ['branch_id' => $branchB->id]);

        $foreignConfig = QrisConfig::query()->create([
            'branch_id' => $branchB->id,
            'merchant_name' => 'FOREIGN MERCHANT',
            'merchant_city' => 'KUDUS',
            'qris_payload' => QrisTestPayload::make('FOREIGN MERCHANT', 'KUDUS', 'FOREIGN000001'),
            'is_active' => true,
            'activated_at' => now(),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.qris.deactivate', $foreignConfig))
            ->assertForbidden();
        $this->assertTrue($foreignConfig->fresh()->is_active);
    }

    private function branch(string $suffix): Branch
    {
        return Branch::query()->create([
            'name' => 'Cabang '.$suffix,
            'code' => 'QR-'.$suffix,
            'is_active' => true,
        ]);
    }

    private function user(string $roleName, Branch $branch): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branch->id,
        ]);
    }
}
