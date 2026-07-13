<?php

namespace Tests\Feature\Exports;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StockLogExportResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_stock_log_export_keeps_the_selected_branch_scope(): void
    {
        $owner = $this->createOwner();
        $firstBranch = Branch::query()->where('code', 'default')->firstOrFail();
        $secondBranch = Branch::query()->create(['name' => 'Kebab SK Jepara', 'code' => 'jpr', 'is_active' => true]);
        $ingredient = Ingredient::query()->create(['name' => 'Tortilla', 'stock' => 100, 'minimum_stock' => 10, 'base_unit' => 'pcs', 'display_unit' => 'pcs']);

        $this->createLog($firstBranch, $ingredient, 'Log Cabang Utama');
        $this->createLog($secondBranch, $ingredient, 'Log Cabang Jepara');

        $response = $this->actingAs($owner)->get(route('owner.stock-logs.export', [
            'format' => 'html',
            'period' => 'daily',
            'date' => now()->toDateString(),
            'branch_id' => $firstBranch->id,
        ]));

        $response->assertOk();
        $response->assertSee('Log Cabang Utama');
        $response->assertDontSee('Log Cabang Jepara');
    }

    public function test_large_owner_stock_log_excel_export_is_queued(): void
    {
        Queue::fake();

        $owner = $this->createOwner();
        $branch = Branch::query()->where('code', 'default')->firstOrFail();
        $ingredient = Ingredient::query()->create(['name' => 'Tortilla', 'stock' => 100, 'minimum_stock' => 10, 'base_unit' => 'pcs', 'display_unit' => 'pcs']);

        foreach (range(1, 101) as $number) {
            $this->createLog($branch, $ingredient, 'Log ' . $number);
        }

        $this->actingAs($owner)
            ->get(route('owner.stock-logs.export', [
                'format' => 'excel',
                'period' => 'daily',
                'date' => now()->toDateString(),
                'branch_id' => $branch->id,
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    private function createOwner(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'owner']);

        return User::query()->create([
            'name' => 'Owner Export',
            'username' => 'owner_export',
            'email' => 'owner-export@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
        ]);
    }

    private function createLog(Branch $branch, Ingredient $ingredient, string $note): void
    {
        StockLog::query()->create([
            'branch_id' => $branch->id,
            'ingredient_id' => $ingredient->id,
            'type' => 'in',
            'quantity' => 1,
            'note' => $note,
        ]);
    }
}
