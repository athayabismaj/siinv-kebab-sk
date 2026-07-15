<?php

namespace Tests\Feature\View;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class BladePresentationCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_selects_the_expected_sidebar_for_each_web_role(): void
    {
        $admin = $this->userForRole('admin');
        $owner = $this->userForRole('owner');
        $developer = $this->userForRole('developer');

        $this->actingAs($admin)
            ->get(route('admin.panel'))
            ->assertOk()
            ->assertSee('Admin Panel')
            ->assertSee('Manajemen Resep')
            ->assertDontSee('Analisis Menu');

        $this->actingAs($owner)
            ->get(route('owner.panel'))
            ->assertOk()
            ->assertSee('Owner Panel')
            ->assertSee('Analisis Menu')
            ->assertDontSee('Manajemen Resep');

        $this->actingAs($developer)
            ->get(route('developer.panel'))
            ->assertOk()
            ->assertSee('Super Admin')
            ->assertSee('Manajemen Backup')
            ->assertDontSee('Analisis Menu');
    }

    public function test_admin_dashboard_renders_its_primary_presentation_contract(): void
    {
        $response = $this->actingAs($this->userForRole('admin'))
            ->get(route('admin.panel'));

        $response->assertOk()
            ->assertSee('Dashboard Admin')
            ->assertSee('Performa Penjualan')
            ->assertSee('Status Sesi Stok')
            ->assertSee('Menu Terlaris')
            ->assertSee('Aktivitas Hari Ini')
            ->assertSee(route('admin.transactions.index'), false)
            ->assertSee(route('admin.daily-stocks.index'), false);
    }

    public function test_flash_alerts_keep_variants_validation_errors_and_auto_dismiss_delay(): void
    {
        session()->flash('success', 'Operasi berhasil.');
        session()->flash('warning', 'Data perlu diperiksa.');
        session()->flash('error', 'Operasi gagal.');

        $errors = (new ViewErrorBag())->put('default', new MessageBag([
            'name' => ['Nama wajib diisi.'],
        ]));

        $html = view('partials.flash_alerts', [
            'includeErrors' => true,
            'errors' => $errors,
        ])->render();

        $this->assertStringContainsString('Berhasil', $html);
        $this->assertStringContainsString('Perlu Dicek', $html);
        $this->assertStringContainsString('Gagal', $html);
        $this->assertStringContainsString('Input Belum Valid', $html);
        $this->assertStringContainsString('Nama wajib diisi.', $html);

        session()->forget(['warning', 'error']);
        $autoDismissHtml = view('partials.flash_alerts')->render();
        $this->assertStringContainsString('6000', $autoDismissHtml);
    }

    public function test_primary_sidebar_routes_and_confirmation_messages_remain_available(): void
    {
        foreach ([
            'admin.panel',
            'admin.transactions.index',
            'admin.daily-stocks.index',
            'owner.panel',
            'owner.transactions.index',
            'owner.analytics.menu',
            'developer.panel',
            'developer.backups.index',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Route {$routeName} harus tetap tersedia.");
        }

        $confirmationSources = [
            resource_path('views/admin/ingredient_categories/index.blade.php') => 'Yakin ingin menghapus kategori ini?',
            resource_path('views/admin/menu_categories/index.blade.php') => 'Yakin ingin menghapus kategori menu ini?',
            resource_path('views/admin/menu_variants/index.blade.php') => 'Apakah Anda yakin ingin menghapus varian menu ini?',
            resource_path('views/owner/reports/closing_index.blade.php') => 'Data yang sudah ditutup akan menjadi Snapshot permanen.',
        ];

        foreach ($confirmationSources as $file => $message) {
            $source = (string) file_get_contents($file);

            $this->assertStringContainsString($message, $source);
            $this->assertStringContainsString('data-confirm', $source);
            $this->assertStringNotContainsString('onclick="return confirm(', $source);
        }
    }

    public function test_migrated_interactions_use_module_hooks_instead_of_inline_handlers(): void
    {
        $periodSources = [
            resource_path('views/admin/transactions/index.blade.php'),
            resource_path('views/owner/transactions/index.blade.php'),
            resource_path('views/reports/partials/expense_report_content.blade.php'),
            resource_path('views/admin/reports/usage/partials/filters.blade.php'),
            resource_path('views/admin/reports/daily_stock/index.blade.php'),
        ];

        foreach ($periodSources as $file) {
            $source = (string) file_get_contents($file);

            $this->assertStringContainsString('data-period-filter', $source);
            $this->assertStringContainsString('data-period-type="daily"', $source);
            $this->assertStringContainsString('data-period-date', $source);
            $this->assertStringNotContainsString('onclick="changeType(', $source);
            $this->assertStringNotContainsString('onchange="updateDateRange(', $source);
        }

        $passwordSource = (string) file_get_contents(resource_path('views/owner/user_management/partials/form.blade.php'));
        $targetSource = (string) file_get_contents(resource_path('views/owner/targets/daily.blade.php'));
        $closingSource = (string) file_get_contents(resource_path('views/owner/reports/closing_index.blade.php'));
        $salesSource = (string) file_get_contents(resource_path('views/owner/reports/sales_unified.blade.php'));
        $closeSessionSource = (string) file_get_contents(resource_path('views/admin/daily_stocks/close.blade.php'));
        $analyticsSource = (string) file_get_contents(resource_path('views/owner/analytics/menu.blade.php'));
        $adminTransactionDetailSource = (string) file_get_contents(resource_path('views/admin/transactions/show.blade.php'));

        $this->assertStringContainsString('data-password-toggle', $passwordSource);
        $this->assertStringNotContainsString('onclick="togglePassword(', $passwordSource);
        $this->assertStringContainsString('data-clear-zero-input', $targetSource);
        $this->assertStringContainsString('data-closing-cancel', $closingSource);
        $this->assertStringNotContainsString('onsubmit="cancelClosing(', $closingSource);
        $this->assertStringContainsString('data-date-navigation', $salesSource);
        $this->assertStringNotContainsString('onchange="onSalesDateChange(', $salesSource);
        $this->assertStringContainsString('data-daily-stock-close', $closeSessionSource);
        $this->assertStringContainsString('data-unit-map', $closeSessionSource);
        $this->assertStringNotContainsString('<script>', $closeSessionSource);
        $this->assertStringContainsString('data-date-navigation', $analyticsSource);
        $this->assertStringNotContainsString('onchange=', $analyticsSource);
        $this->assertStringContainsString('data-print-page', $adminTransactionDetailSource);
        $this->assertStringNotContainsString('onclick="window.print()', $adminTransactionDetailSource);
    }

    private function userForRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
