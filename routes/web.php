<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Owner\UserManagementController;



Route::get('/', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.process');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/forgot-password', function () {
    return view('auth.forgot');
})->name('password.request');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])
    ->name('password.sendOtp');

Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
    ->name('password.verifyOtp');



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

Route::middleware(['auth', 'role:owner'])->group(function () {

    Route::get('/owner/panel', function () {
        return view('owner.panel_owner');
    })->name('owner.panel');


    Route::prefix('owner/users')
        ->name('owner.users.')
        ->group(function () {

        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::get('/{user}/reset-password', [UserManagementController::class, 'showResetForm'])->name('reset.form');
        Route::post('/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('resetPassword');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::get('/archive', [UserManagementController::class, 'archive'])->name('archive');
        Route::patch('/{id}/restore', [UserManagementController::class, 'restore'])->name('restore');


    });

});


/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/admin/panel', function () {
        return view('admin.panel_admin');
    })->name('admin.panel');

});