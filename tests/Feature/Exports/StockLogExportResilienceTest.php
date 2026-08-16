<?php

namespace Tests\Feature\Exports;

use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\Role;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLogExportResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_stock_log_export_keeps_the_selected_branch_scope(): void
    {
        $owner = $this->createOwner();
        $firstBranch = $this->defaultBranch();
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

    public function test_large_owner_stock_log_excel_export_downloads_directly(): void
    {
        $owner = $this->createOwner();
        $branch = $this->defaultBranch();
        $ingredient = Ingredient::query()->create(['name' => 'Tortilla', 'stock' => 100, 'minimum_stock' => 10, 'base_unit' => 'pcs', 'display_unit' => 'pcs']);

        foreach (range(1, 101) as $number) {
            $this->createLog($branch, $ingredient, 'Log ' . $number);
        }

        $response = $this->actingAs($owner)
            ->get(route('owner.stock-logs.export', [
                'format' => 'excel',
                'period' => 'daily',
                'date' => now()->toDateString(),
                'branch_id' => $branch->id,
            ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));
        $this->assertDatabaseMissing('generated_exports', ['type' => 'stock_log']);
    }

    public function test_admin_stock_log_html_export_supports_more_than_one_hundred_rows(): void
    {
        $branch = $this->defaultBranch();
        $admin = $this->createAdmin($branch);
        $ingredient = Ingredient::query()->create([
            'name' => 'Bahan Riwayat Admin',
            'stock' => 200,
            'minimum_stock' => 10,
            'base_unit' => 'pcs',
            'display_unit' => 'pcs',
        ]);

        foreach (range(1, 107) as $number) {
            $this->createLog($branch, $ingredient, 'Riwayat Admin '.$number);
        }

        $this->actingAs($admin)
            ->get(route('admin.stocks.logs.export', [
                'format' => 'html',
                'period' => 'daily',
                'date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Riwayat Admin 107');
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

    private function defaultBranch(): Branch
    {
        return Branch::query()->firstOrCreate(
            ['code' => 'default'],
            ['name' => 'Kebab SK', 'is_active' => true],
        );
    }

    private function createAdmin(Branch $branch): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'admin']);

        return User::query()->create([
            'name' => 'Admin Export',
            'username' => 'admin_export',
            'email' => 'admin-export@example.test',
            'password' => 'secret123',
            'role_id' => $role->id,
            'branch_id' => $branch->id,
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
