<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CashflowController;
use App\Http\Controllers\API\DailyStockController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\PaymentMethodController;
use App\Http\Controllers\API\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class , 'login'])
        ->middleware('throttle:api-auth');
    Route::post('/forgot-password', [AuthController::class , 'forgotPassword'])
        ->middleware('throttle:auth-forgot-password');
    Route::post('/verify-reset-code', [AuthController::class , 'verifyResetCode'])
        ->middleware('throttle:auth-reset-password');
    Route::post('/reset-password', [AuthController::class , 'resetPassword'])
        ->middleware('throttle:auth-reset-password');

    Route::middleware('api.token')->group(function () {
            Route::get('/me', [AuthController::class , 'me']);
            Route::put('/profile', [AuthController::class , 'updateProfile'])
                ->middleware('throttle:20,1');
            Route::post('/change-password', [AuthController::class , 'changePassword'])
                ->middleware('throttle:20,1');
            Route::post('/logout', [AuthController::class , 'logout']);
        }
        );    });

Route::get('/revenue/summary', [TransactionController::class , 'revenueSummary'])
    ->middleware(['api.token', 'throttle:api-read-role-aware']);
Route::get('/revenue/trend', [TransactionController::class , 'revenueTrend'])
    ->middleware(['api.token', 'throttle:api-read-role-aware']);
Route::get('/transactions', [TransactionController::class , 'index'])
    ->middleware(['api.token', 'throttle:api-read-role-aware']);
Route::post('/transactions', [TransactionController::class , 'store'])
    ->middleware(['api.token', 'throttle:api-checkout-role-aware']);
Route::post('/transactions/{transactionId}/void', [\App\Http\Controllers\API\VoidTransactionController::class, 'voidTransaction'])
    ->middleware(['api.token', 'throttle:api-checkout-role-aware']);

Route::get('/menus', [MenuController::class , 'index'])
    ->middleware(['api.token', 'throttle:api-read-role-aware']);
Route::get('/menus/unavailable-variants', [MenuController::class , 'unavailableVariants'])
    ->middleware(['api.token', 'throttle:api-read-role-aware']);

Route::get('/payment-methods', [PaymentMethodController::class , 'index'])
    ->middleware(['api.token', 'throttle:api-read-role-aware']);

Route::get('/daily-stock-items', [DailyStockController::class, 'index'])
    ->middleware(['api.token', 'throttle:api-read-role-aware']);

Route::post('/daily-stock-sessions/close', [DailyStockController::class, 'closeSession'])
    ->middleware(['api.token', 'throttle:api-checkout-role-aware']);

Route::post('/cashflow/expenses', [CashflowController::class, 'storeExpense'])
    ->middleware(['api.token', 'throttle:api-checkout-role-aware']);

Route::get('/sessions/current-status', [\App\Http\Controllers\API\SessionStatusController::class, 'currentStatus'])
    ->middleware(['api.token', 'throttle:api-read-role-aware']);
