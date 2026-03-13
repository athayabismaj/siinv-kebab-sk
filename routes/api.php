<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\PaymentMethodController;
use App\Http\Controllers\API\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class , 'login'])
        ->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class , 'forgotPassword'])
        ->middleware('throttle:3,1');
    Route::post('/verify-reset-code', [AuthController::class , 'verifyResetCode'])
        ->middleware('throttle:10,1');
    Route::post('/reset-password', [AuthController::class , 'resetPassword'])
        ->middleware('throttle:5,1');

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
    ->middleware(['api.token', 'throttle:60,1']);
Route::get('/revenue/trend', [TransactionController::class , 'revenueTrend'])
    ->middleware(['api.token', 'throttle:60,1']);
Route::get('/transactions', [TransactionController::class , 'index'])
    ->middleware(['api.token', 'throttle:60,1']);
Route::post('/transactions', [TransactionController::class , 'store'])
    ->middleware(['api.token', 'throttle:30,1']);

Route::get('/menus', [MenuController::class , 'index'])
    ->middleware(['api.token', 'throttle:60,1']);

Route::get('/payment-methods', [PaymentMethodController::class , 'index'])
    ->middleware(['api.token', 'throttle:60,1']);
