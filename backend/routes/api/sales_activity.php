<?php

use App\Http\Controllers\Api\V1\RejectSalesActivityController;
use App\Http\Controllers\Api\V1\SalesActivityController;
use App\Http\Controllers\Api\V1\SalesActivityHistoryController;
use App\Http\Controllers\Api\V1\SubmitSalesActivityController;
use App\Http\Controllers\Api\V1\ValidateSalesActivityController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:spv,sales')->group(function (): void {
    Route::get('sales-activities', [SalesActivityController::class, 'index'])->name('sales-activities.index');
    Route::get('sales-activities/{activity}', [SalesActivityController::class, 'show'])->name('sales-activities.show');
    Route::get('sales-activities/{activity}/history', SalesActivityHistoryController::class)
        ->name('sales-activities.history');
    Route::get('sales-activities/{activity}/evidence', [SalesActivityController::class, 'evidence'])
        ->name('sales-activities.evidence');
});

Route::middleware(['role:sales', 'subscription.write'])->group(function (): void {
    Route::post('sales-activities', [SalesActivityController::class, 'store'])->name('sales-activities.store');
    Route::patch('sales-activities/{activity}', [SalesActivityController::class, 'update'])->name('sales-activities.update');
    Route::delete('sales-activities/{activity}', [SalesActivityController::class, 'destroy'])->name('sales-activities.destroy');
    Route::post('sales-activities/{activity}/submit', SubmitSalesActivityController::class)
        ->name('sales-activities.submit');
});

Route::middleware(['role:spv', 'subscription.write'])->group(function (): void {
    Route::post('sales-activities/{activity}/validate', ValidateSalesActivityController::class)
        ->name('sales-activities.validate');
    Route::post('sales-activities/{activity}/reject', RejectSalesActivityController::class)
        ->name('sales-activities.reject');
});
