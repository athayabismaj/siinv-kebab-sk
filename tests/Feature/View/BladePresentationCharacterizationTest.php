<?php

namespace Tests\Feature\View;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\Transaction;
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

    public function test_transaction_views_keep_shared_status_payment_and_void_semantics(): void
    {
        $branch = Branch::query()->create([
            'name' => 'Cabang Karakterisasi',
            'code' => 'KAR',
            'address' => null,
            'is_active' => true,
        ]);
        $admin = $this->userForRole('admin', $branch->id);
        $owner = $this->userForRole('owner');
        $cashier = $this->userForRole('kasir', $branch->id);
        $admin->assignedBranches()->attach($branch->id);

        $paymentMethod = PaymentMethod::query()->create(['name' => ' CASH ']);
        $success = $this->transaction(
            branch: $branch,
            cashier: $cashier,
            paymentMethod: $paymentMethod,
            code: 'TRX-KAR-SUCCESS',
            status: 'SUCCESS'
        );
        $void = $this->transaction(
            branch: $branch,
            cashier: $cashier,
            paymentMethod: $paymentMethod,
            code: 'TRX-KAR-VOID',
            status: 'VOID',
            voidReason: ' kembali_stok '
        );

        $period = [
            'type' => 'daily',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ];

        foreach ([
            [$admin, route('admin.transactions.index', $period)],
            [$owner, route('owner.transactions.index', $period)],
        ] as [$user, $url]) {
            $response = $this->actingAs($user)->get($url);

            $response->assertOk()
                ->assertSee('Berhasil')
                ->assertSee('Dibatalkan')
                ->assertSee('Kembali ke Stok')
                ->assertSee('Tunai')
                ->assertSee('data-period-filter', false)
                ->assertSee('data-period-type="daily"', false);

            $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'Kembali ke Stok'));
            $this->assertGreaterThanOrEqual(4, substr_count($response->getContent(), 'Tunai'));
        }

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $void))
            ->assertOk()
            ->assertSee('Dibatalkan')
            ->assertSee('Kembali ke Stok')
            ->assertSee('Tunai')
            ->assertSee('data-print-page', false);

        $this->actingAs($owner)
            ->get(route('owner.transactions.show', $void))
            ->assertOk()
            ->assertSee('Dibatalkan')
            ->assertSee('Kembali ke Stok')
            ->assertSee('Tunai')
            ->assertSee('data-print-page', false);

        $this->actingAs($owner)
            ->get(route('owner.reports.sales', [
                'type' => 'daily',
                'date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Laporan Penjualan')
            ->assertSee('Berhasil')
            ->assertSee('Tunai')
            ->assertSee($success->transaction_code);
    }

    public function test_flash_alerts_keep_variants_validation_errors_and_auto_dismiss_delay(): void
    {
        session()->flash('success', 'Operasi berhasil.');
        session()->flash('warning', 'Data perlu diperiksa.');
        session()->flash('error', 'Operasi gagal.');

        $errors = (new ViewErrorBag)->put('default', new MessageBag([
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
        $closingSource = (string) file_get_contents(resource_path('views/owner/reports/closing_index.blade.php'));
        $salesSource = (string) file_get_contents(resource_path('views/owner/reports/sales_unified.blade.php'));
        $closeSessionSource = (string) file_get_contents(resource_path('views/admin/daily_stocks/close.blade.php'));
        $analyticsSource = (string) file_get_contents(resource_path('views/owner/analytics/menu.blade.php'));
        $adminTransactionDetailSource = (string) file_get_contents(resource_path('views/admin/transactions/show.blade.php'));

        $this->assertStringContainsString('data-password-toggle', $passwordSource);
        $this->assertStringNotContainsString('onclick="togglePassword(', $passwordSource);
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

    public function test_transaction_views_do_not_reintroduce_inline_presentation_mappers(): void
    {
        $presentationSources = [
            resource_path('views/admin/transactions/index.blade.php'),
            resource_path('views/admin/transactions/partials/index/groups.blade.php'),
            resource_path('views/admin/transactions/show.blade.php'),
            resource_path('views/owner/transactions/index.blade.php'),
            resource_path('views/owner/transactions/show.blade.php'),
            resource_path('views/owner/reports/sales_unified.blade.php'),
        ];
        $forbiddenFragments = [
            '= function (',
            '@php(',
            '$transactionPaymentLabel',
            '$transactionVoidReasonLabel',
            '$transactionStatusLabel',
            '$statusRaw',
            '$badgeClass',
            '$statusDotClass',
            '$isPaid',
        ];

        foreach ($presentationSources as $file) {
            $source = (string) file_get_contents($file);

            foreach ($forbiddenFragments as $fragment) {
                $this->assertStringNotContainsString($fragment, $source, "{$fragment} tidak boleh kembali ke {$file}.");
            }
        }

        $this->assertFileExists(app_path('View/Presenters/TransactionPresenter.php'));
        $this->assertFileExists(resource_path('views/components/transaction/status-badge.blade.php'));
        $this->assertFileExists(resource_path('views/components/transaction/void-reason.blade.php'));
    }

    private function userForRole(string $roleName, ?int $branchId = null): User
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        return User::factory()->create([
            'role_id' => $role->id,
            'branch_id' => $branchId,
        ]);
    }

    private function transaction(
        Branch $branch,
        User $cashier,
        PaymentMethod $paymentMethod,
        string $code,
        string $status,
        ?string $voidReason = null
    ): Transaction {
        return Transaction::query()->create([
            'transaction_code' => $code,
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'payment_method_id' => $paymentMethod->id,
            'total_amount' => 15000,
            'paid_amount' => 20000,
            'change_amount' => 5000,
            'status' => $status,
            'void_reason' => $voidReason,
            'voided_by' => $voidReason === null ? null : $cashier->id,
            'voided_at' => $voidReason === null ? null : now(),
        ]);
    }
}
