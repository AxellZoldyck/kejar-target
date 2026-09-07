<?php

use App\Http\Controllers\Api\V1\Dashboard\SalesDashboardController;
use App\Http\Controllers\Api\V1\Dashboard\SpvDashboardController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:spv')->get('spv/dashboard', SpvDashboardController::class)->name('spv.dashboard');
Route::middleware('role:sales')->get('sales/dashboard', SalesDashboardController::class)->name('sales.dashboard');
Route::middleware('role:spv,sales')->get('leaderboard', LeaderboardController::class)->name('leaderboard');
