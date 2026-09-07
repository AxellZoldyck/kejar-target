<?php

use App\Http\Controllers\Api\V1\Account\AccountController;
use App\Http\Controllers\Api\V1\Account\CompanyController;
use App\Http\Controllers\Api\V1\Account\SubscriptionController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'user.active'])->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/me', [AccountController::class, 'show'])->name('me');

    Route::middleware(['tenant', 'role:spv'])->group(function (): void {
        Route::get('/company', [CompanyController::class, 'show'])->name('company.show');
        Route::patch('/company', [CompanyController::class, 'update'])
            ->middleware('subscription.writable')
            ->name('company.update');

        Route::get('/subscription', [SubscriptionController::class, 'show'])
            ->name('subscription.show');
        Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])
            ->middleware('throttle:5,1')
            ->name('subscription.checkout');
    });
});
