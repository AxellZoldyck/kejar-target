<?php

use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\TargetController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:spv')->group(function (): void {
    Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::get('sales', [SalesController::class, 'index'])->name('sales.index');
    Route::get('sales/{sales}', [SalesController::class, 'show'])->name('sales.show');
});

Route::middleware(['role:spv', 'subscription.write'])->group(function (): void {
    Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
    Route::match(['put', 'patch'], 'teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::post('teams/{team}/members', [TeamMemberController::class, 'store'])->name('teams.members.store');
    Route::post('sales', [SalesController::class, 'store'])->name('sales.store');
    Route::match(['put', 'patch'], 'sales/{sales}', [SalesController::class, 'update'])->name('sales.update');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::patch('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('targets', [TargetController::class, 'store'])->name('targets.store');
    Route::patch('targets/{target}', [TargetController::class, 'update'])->name('targets.update');
});

Route::middleware('role:spv,sales')->group(function (): void {
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('targets', [TargetController::class, 'index'])->name('targets.index');
});
