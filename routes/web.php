<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Owner\BranchController;
use App\Http\Controllers\Owner\UserManagementController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\SalesReportController;
use App\Http\Controllers\Owner\TransactionHistoryController;
use App\Http\Controllers\Owner\GeneratedExportController;
use App\Http\Controllers\Owner\StockMonitoringController;
use App\Http\Controllers\Owner\StockLogController as OwnerStockLogController;
use App\Http\Controllers\Owner\CashflowController as OwnerCashflowController;
use App\Http\Controllers\Owner\OwnerBranchContextController;
use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Admin\IngredientCategoryController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuCategoryController;
use App\Http\Controllers\Admin\MenuVariantController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\DailyStockController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UsageReportController;
use App\Http\Controllers\Admin\CashflowController as AdminCashflowController;
use App\Http\Controllers\Admin\DailyStockReportController;
use App\Http\Controllers\Admin\BranchContextController;
use App\Http\Controllers\System\ReadinessController;

Route::get('/', [LoginController::class, 'showLogin'])->name('login');
Route::get('/health/ready', ReadinessController::class)->name('health.ready');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:auth-login')
    ->name('login.process');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

Route::get('/forgot-password', [ForgotPasswordController::class, 'showRequestForm'])->name('password.request');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])
    ->middleware('throttle:auth-forgot-password')
    ->name('password.sendOtp');

Route::get('/verify-otp', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify.form');

Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
    ->middleware('throttle:auth-reset-password')
    ->name('password.verifyOtp');

Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');

Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
    ->middleware('throttle:auth-reset-password')
    ->name('password.reset');



/*
|--------------------------------------------------------------------------
| USER PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/change-password', [ProfileController::class, 'showChangePassword'])->name('profile.password.form');
    Route::put('/change-password', [ProfileController::class, 'changePassword'])->name('profile.password.update');
});


/*
|--------------------------------------------------------------------------
| OWNER
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:owner', 'perf.log'])->prefix('owner')->name('owner.')->group(function () {
        // ================= PANEL =================
        Route::get('/panel', [OwnerDashboardController::class, 'index'])->name('panel');
        Route::post('/branch-context', [OwnerBranchContextController::class, 'switch'])->name('branch-context.switch');

        // ================= USER MANAGEMENT =================
        Route::prefix('branches')->name('branches.')->group(function () {
            Route::get('/', [BranchController::class, 'index'])->name('index');
            Route::get('/create', [BranchController::class, 'create'])->name('create');
            Route::post('/', [BranchController::class, 'store'])->name('store');
            Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit');
            Route::put('/{branch}', [BranchController::class, 'update'])->name('update');
            Route::patch('/{branch}/toggle', [BranchController::class, 'toggle'])->name('toggle');
        });

        Route::prefix('users')->name('users.')->group(function () {

            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/create', [UserManagementController::class, 'create'])->name('create');
            Route::post('/', [UserManagementController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
            Route::get('/{user}/reset-password',[UserManagementController::class, 'showResetForm'])->name('reset.form');
            Route::post('/{user}/reset-password',[UserManagementController::class, 'resetPassword'])->name('resetPassword');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
            Route::get('/archive',[UserManagementController::class, 'archive'])->name('archive');
            Route::patch('/{id}/restore', [UserManagementController::class, 'restore'])->name('restore');
        });
        Route::prefix('stocks')->name('stocks.')->group(function () {
            Route::get('/', [StockMonitoringController::class, 'index'])->name('index');
        });

        Route::prefix('stock-logs')->name('stock-logs.')->group(function () {
            Route::get('/', [OwnerStockLogController::class, 'index'])->name('index');
            Route::get('/export', [OwnerStockLogController::class, 'export'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('export');
        });

        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', [TransactionHistoryController::class, 'index'])->name('index');
            Route::get('/export', [TransactionHistoryController::class, 'export'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('export');
            Route::get('/{transaction}', [TransactionHistoryController::class, 'show'])->name('show');
        });

        Route::prefix('exports')->name('generated-exports.')->group(function () {
            Route::get('/{generatedExport}', [GeneratedExportController::class, 'show'])->name('show');
            Route::get('/{generatedExport}/download', [GeneratedExportController::class, 'download'])->name('download');
            Route::post('/{generatedExport}/retry', [GeneratedExportController::class, 'retry'])->name('retry');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales', [SalesReportController::class, 'index'])->name('sales');
            Route::get('/sales/export', [SalesReportController::class, 'export'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('sales.export');
            
            // Tutup Buku (Closing)
            Route::get('/closing', [SalesReportController::class, 'closingIndex'])->name('closing.index');
            Route::post('/closing', [SalesReportController::class, 'closePeriod'])->name('closing.store');
            Route::delete('/closing/{closing}', [SalesReportController::class, 'cancelClosing'])->name('closing.cancel');

            Route::get('/usage', [UsageReportController::class, 'index'])->name('usage');
            Route::get('/usage/export', [UsageReportController::class, 'export'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('usage.export');
            Route::get('/cashflow', [OwnerCashflowController::class, 'index'])->name('cashflow');
            Route::get('/cashflow/export', [OwnerCashflowController::class, 'export'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('cashflow.export');
        });

        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/menu', [SalesReportController::class, 'menuAnalysis'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('menu');
        });



});


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin', 'perf.log'])->prefix('admin')->name('admin.')->group(function () {

        Route::prefix('exports')->name('generated-exports.')->group(function () {
            Route::get('/{generatedExport}', [GeneratedExportController::class, 'show'])->name('show');
            Route::get('/{generatedExport}/download', [GeneratedExportController::class, 'download'])->name('download');
            Route::post('/{generatedExport}/retry', [GeneratedExportController::class, 'retry'])->name('retry');
        });

        // ===== PANEL =====
        Route::get('/panel', [DashboardController::class, 'index'])->name('panel');
        Route::post('/branch-context', [BranchContextController::class, 'switch'])->name('branch-context.switch');

        // ===== INGREDIENT CATEGORIES =====
        Route::prefix('ingredient-categories')->name('ingredient-categories.')->group(function () {
                Route::get('/', [IngredientCategoryController::class, 'index'])->name('index');
                Route::get('/create',[IngredientCategoryController::class, 'create'])->name('create');
                Route::post('/',[IngredientCategoryController::class, 'store'])->name('store');
                Route::get('/{ingredientCategory}/edit',[IngredientCategoryController::class, 'edit'])->name('edit');
                Route::put('/{ingredientCategory}',[IngredientCategoryController::class, 'update'])->name('update');
                Route::delete('/{ingredientCategory}',[IngredientCategoryController::class, 'destroy'])->name('destroy');
            });

        // ===== INGREDIENTS =====
        Route::prefix('ingredients')->name('ingredients.')->group(function () {
                Route::get('/', [IngredientController::class, 'index'])->name('index');
                Route::get('/create', [IngredientController::class, 'create'])->name('create');
                Route::post('/', [IngredientController::class, 'store'])->name('store');
                Route::get('/{ingredient}/edit', [IngredientController::class, 'edit'])->name('edit');
                Route::put('/{ingredient}', [IngredientController::class, 'update'])->name('update');
                Route::delete('/{ingredient}', [IngredientController::class, 'destroy'])->name('destroy');
                Route::get('/archive', [IngredientController::class, 'archive'])->name('archive');
                Route::patch('/{id}/restore', [IngredientController::class, 'restore'])->name('restore');
        });

        // ===== MENUS =====
        Route::prefix('menus')->name('menus.')->group(function () {
                Route::get('/', [MenuController::class, 'index'])->name('index');
                Route::get('/create', [MenuController::class, 'create'])->name('create');
                Route::post('/', [MenuController::class, 'store'])->name('store');
                Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
                Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
                Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
                Route::get('/archive', [MenuController::class, 'archive'])->name('archive');
                Route::patch('/{id}/restore', [MenuController::class, 'restore'])->name('restore');
            });

        // ===== MENU CATEGORIES =====
        Route::prefix('menu-categories')->name('menu-categories.')->group(function () {
            Route::get('/',[MenuCategoryController::class, 'index'])->name('index');
            Route::get('/create',[MenuCategoryController::class, 'create'])->name('create');
            Route::post('/',[MenuCategoryController::class, 'store'])->name('store');
            Route::get('/{menuCategory}/edit',[MenuCategoryController::class, 'edit'])->name('edit');
            Route::put('/{menuCategory}',[MenuCategoryController::class, 'update'])->name('update');
            Route::delete('/{menuCategory}',[MenuCategoryController::class, 'destroy'])->name('destroy');
        });

        // ===== MENU VARIANTS =====
        Route::prefix('menus/{menu}')->name('menu-variants.')->group(function () {
                Route::get('variants', [MenuVariantController::class, 'index'])->name('index');
                Route::get('variants/create', [MenuVariantController::class, 'create'])->name('create');
                Route::post('variants', [MenuVariantController::class, 'store'])->name('store');
                Route::get('variants/{menuVariant}/edit', [MenuVariantController::class, 'edit'])->name('edit');
                Route::put('variants/{menuVariant}', [MenuVariantController::class, 'update'])->name('update');
                Route::delete('variants/{menuVariant}', [MenuVariantController::class, 'destroy'])->name('destroy');
            });



        Route::prefix('stocks')->name('stocks.')->group(function () {
            Route::get('/', [StockController::class,'index'])->name('index');
            Route::get('/logs', [StockController::class,'logs'])->name('logs');
            Route::get('/logs/export', [StockController::class,'exportLogs'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('logs.export');
            Route::get('/{ingredient}/restock', [StockController::class,'restockForm'])->name('restock.form');
            Route::post('/{ingredient}/restock', [StockController::class,'restock'])->name('restock');
            Route::get('/{ingredient}/adjust', [StockController::class,'adjustForm'])->name('adjust.form');
            Route::post('/{ingredient}/adjust', [StockController::class,'adjust'])->name('adjust');
        });

        Route::prefix('daily-stocks')->name('daily-stocks.')->group(function () {
            Route::get('/', [DailyStockController::class, 'index'])
                ->middleware('can:viewAny,App\Models\DailyStockSession')
                ->name('index');
            Route::get('/transfer', [DailyStockController::class, 'transferForm'])
                ->name('transfer.form');
            Route::get('/close', [DailyStockController::class, 'closeForm'])
                ->name('close.form');
            Route::post('/open', [DailyStockController::class, 'open'])
                ->middleware('can:open,App\Models\DailyStockSession')
                ->name('open');
            Route::post('/transfer', [DailyStockController::class, 'transfer'])
                ->name('transfer');
            Route::post('/close', [DailyStockController::class, 'close'])
                ->name('close');
            Route::post('/reopen', [DailyStockController::class, 'reopen'])
                ->name('reopen');
            Route::post('/reconcile', [DailyStockController::class, 'reconcile'])
                ->name('reconcile');
        });



        Route::prefix('recipes')->name('recipes.')->group(function () {
                Route::get('/', [RecipeController::class, 'index'])->name('index');
                Route::get('/{variant}/edit', [RecipeController::class, 'edit'])->name('edit');
                Route::put('/{variant}', [RecipeController::class, 'update'])->name('update');
        });

        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', [TransactionController::class, 'index'])->name('index');
            Route::get('/{transaction}', [TransactionController::class, 'show'])->name('show');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/usage', [UsageReportController::class, 'index'])->name('usage');
            Route::get('/usage/export', [UsageReportController::class, 'export'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('usage.export');
            Route::get('/daily-stock', [DailyStockReportController::class, 'index'])
                ->middleware('can:viewReport,App\Models\DailyStockSession')
                ->name('daily-stock');
            Route::get('/daily-stock/export', [DailyStockReportController::class, 'export'])
                ->middleware('can:viewReport,App\Models\DailyStockSession')
                ->middleware('throttle:web-heavy-role-aware')
                ->name('daily-stock.export');
            Route::get('/cashflow', [AdminCashflowController::class, 'index'])->name('cashflow');
            Route::get('/cashflow/input', [AdminCashflowController::class, 'create'])->name('cashflow.create');
            Route::post('/cashflow/input', [AdminCashflowController::class, 'store'])->name('cashflow.store');
            Route::get('/cashflow/export', [AdminCashflowController::class, 'export'])
                ->middleware('throttle:web-heavy-role-aware')
                ->name('cashflow.export');
        });


});

/*
|--------------------------------------------------------------------------
| DEVELOPER
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:developer'])->prefix('developer')->name('developer.')->group(function () {
    Route::get('/panel', [\App\Http\Controllers\Developer\DashboardController::class, 'index'])->name('panel');
    Route::post('/clear-cache', [\App\Http\Controllers\Developer\DashboardController::class, 'clearCache'])->name('clear-cache');
    
    // Manajemen Owner
    Route::get('/owners', [\App\Http\Controllers\Developer\OwnerController::class, 'index'])->name('owners.index');
    Route::get('/owners/create', [\App\Http\Controllers\Developer\OwnerController::class, 'create'])->name('owners.create');
    Route::post('/owners', [\App\Http\Controllers\Developer\OwnerController::class, 'store'])->name('owners.store');
    Route::delete('/owners/{user}', [\App\Http\Controllers\Developer\OwnerController::class, 'destroy'])->name('owners.destroy');
    Route::patch('/owners/{id}/restore', [\App\Http\Controllers\Developer\OwnerController::class, 'restore'])->name('owners.restore');

    // Manajemen Backup
    Route::get('/backups', [\App\Http\Controllers\Developer\BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [\App\Http\Controllers\Developer\BackupController::class, 'create'])->name('backups.create');
    Route::get('/backups/{id}/download', [\App\Http\Controllers\Developer\BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{id}/restore', [\App\Http\Controllers\Developer\BackupController::class, 'restore'])->name('backups.restore');
    Route::post('/backups/restore-upload', [\App\Http\Controllers\Developer\BackupController::class, 'restoreUpload'])->name('backups.restore-upload');
});
