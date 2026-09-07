<?php

use Illuminate\Support\Facades\Route;

$uuidPattern = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
foreach (['activity', 'commission', 'company', 'payment', 'product', 'sales', 'subscription', 'target', 'team'] as $parameter) {
    Route::pattern($parameter, $uuidPattern);
}

Route::prefix('v1')->name('api.v1.')->middleware('throttle:120,1')->group(function (): void {
    require __DIR__.'/api/public.php';
    require __DIR__.'/api/account.php';

    Route::middleware(['auth:sanctum', 'user.active', 'tenant'])->group(function (): void {
        if (file_exists(__DIR__.'/api/reporting.php')) {
            require __DIR__.'/api/reporting.php';
        }

        require __DIR__.'/api/organization.php';
        require __DIR__.'/api/sales_activity.php';

        if (file_exists(__DIR__.'/api/commission.php')) {
            require __DIR__.'/api/commission.php';
        }

    });

    if (file_exists(__DIR__.'/api/admin.php')) {
        require __DIR__.'/api/admin.php';
    }
});
