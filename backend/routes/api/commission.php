<?php

use App\Http\Controllers\Api\V1\Commission\CommissionCalculationController;
use App\Http\Controllers\Api\V1\Commission\CommissionController;
use App\Http\Controllers\Api\V1\Commission\CommissionMultiplierRuleController;
use App\Http\Controllers\Api\V1\Commission\CommissionProductFeeController;
use App\Http\Controllers\Api\V1\Commission\CommissionProgressiveRuleController;
use App\Http\Controllers\Api\V1\Commission\CommissionSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:spv')->group(function (): void {
    Route::get('commission-settings', [CommissionSettingController::class, 'show'])->name('commission-settings.show');
    Route::get('commission-product-fees', [CommissionProductFeeController::class, 'index'])->name('commission-product-fees.index');
    Route::get('commission-multiplier-rules', [CommissionMultiplierRuleController::class, 'index'])->name('commission-multiplier-rules.index');
    Route::get('commission-progressive-rules', [CommissionProgressiveRuleController::class, 'index'])->name('commission-progressive-rules.index');
});

Route::middleware(['role:spv', 'subscription.write'])->group(function (): void {
    Route::put('commission-settings', [CommissionSettingController::class, 'update'])->name('commission-settings.update');
    Route::put('commission-product-fees', [CommissionProductFeeController::class, 'update'])->name('commission-product-fees.update');
    Route::put('commission-multiplier-rules', [CommissionMultiplierRuleController::class, 'update'])->name('commission-multiplier-rules.update');
    Route::put('commission-progressive-rules', [CommissionProgressiveRuleController::class, 'update'])->name('commission-progressive-rules.update');
    Route::post('commissions/calculate', CommissionCalculationController::class)->name('commissions.calculate');
});

Route::middleware('role:spv,sales')->group(function (): void {
    Route::get('commissions', [CommissionController::class, 'index'])->name('commissions.index');
    Route::get('commissions/{commission}', [CommissionController::class, 'show'])->name('commissions.show');
});
