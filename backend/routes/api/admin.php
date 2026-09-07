<?php

use App\Http\Controllers\Api\V1\Admin\CompanyController;
use App\Http\Controllers\Api\V1\Admin\PaymentController;
use App\Http\Controllers\Api\V1\Admin\SubscriptionController;
use App\Http\Controllers\Api\V1\Dashboard\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'user.active', 'role:super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/{company}', [CompanyController::class, 'show'])->name('companies.show');
        Route::patch('companies/{company}', [CompanyController::class, 'update'])->name('companies.update');
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    });
