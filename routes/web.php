<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Owner\UserManagementController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\SalesReportController;
use App\Http\Controllers\Owner\TransactionHistoryController;
use App\Http\Controllers\Owner\StockMonitoringController;
use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Admin\IngredientCategoryController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuCategoryController;
use App\Http\Controllers\Admin\MenuVariantController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UsageReportController;





Route::get('/', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login.process');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

Route::get('/forgot-password', function () {
    return view('auth.forgot');
})->name('password.request');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])
    ->middleware('throttle:3,1')
    ->name('password.sendOtp');

Route::get('/verify-otp', function () {
    if (!session('otp_email')) {
        return redirect()->route('password.request');
    }
    return view('auth.verify_otp');
})->name('password.verify.form');

Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
    ->middleware('throttle:10,1')
    ->name('password.verifyOtp');

Route::get('/reset-password', function () {
    if (!session('password_reset_user_id')) {
        return redirect()->route('password.request');
    }
    return view('auth.reset_password');
})->name('password.reset.form');

Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
    ->middleware('throttle:5,1')
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

Route::middleware(['auth', 'role:owner'])->prefix('owner')->name('owner.')->group(function () {
        // ================= PANEL =================
        Route::get('/panel', [OwnerDashboardController::class, 'index'])->name('panel');

        // ================= USER MANAGEMENT =================
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

        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', [TransactionHistoryController::class, 'index'])->name('index');
            Route::get('/export', [TransactionHistoryController::class, 'export'])->name('export');
            Route::get('/{transaction}', [TransactionHistoryController::class, 'show'])->name('show');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales', [SalesReportController::class, 'index'])->name('sales');
            Route::get('/sales/daily', [SalesReportController::class, 'daily'])->name('sales.daily');
            Route::get('/sales/daily/export', [SalesReportController::class, 'exportDaily'])->name('sales.daily.export');
            Route::get('/sales/monthly', [SalesReportController::class, 'monthly'])->name('sales.monthly');
            Route::get('/sales/monthly/export', [SalesReportController::class, 'exportMonthly'])->name('sales.monthly.export');
            Route::get('/sales/export', [SalesReportController::class, 'export'])->name('sales.export');
            Route::get('/usage', [UsageReportController::class, 'index'])->name('usage');
        });

        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/menu', [SalesReportController::class, 'menuAnalysis'])->name('menu');
        });



});


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

        // ===== PANEL =====
        Route::get('/panel', [DashboardController::class, 'index'])->name('panel');

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
            Route::get('/{ingredient}/restock', [StockController::class,'restockForm'])->name('restock.form');
            Route::post('/{ingredient}/restock', [StockController::class,'restock'])->name('restock');
            Route::get('/{ingredient}/adjust', [StockController::class,'adjustForm'])->name('adjust.form');
            Route::post('/{ingredient}/adjust', [StockController::class,'adjust'])->name('adjust');
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
        });


});






















