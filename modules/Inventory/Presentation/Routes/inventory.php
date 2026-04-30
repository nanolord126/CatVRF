<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Presentation\Http\Controllers\InventoryController;

Route::middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('inventory')->group(function () {
        Route::post('deduct', [InventoryController::class, 'autoDeduct'])
            ->name('inventory.deduct');

        Route::post('validate', [InventoryController::class, 'validateBeforeSale'])
            ->name('inventory.validate');

        Route::post('maintenance', [InventoryController::class, 'dailyMaintenance'])
            ->name('inventory.maintenance');

        Route::prefix('items')->group(function () {
            Route::get('{itemId}', [InventoryController::class, 'getItem'])
                ->name('inventory.items.show');

            Route::get('{itemId}/batches', [InventoryController::class, 'getItemBatches'])
                ->name('inventory.items.batches');

            Route::get('{itemId}/next-expiring', [InventoryController::class, 'getNextExpiringBatch'])
                ->name('inventory.items.next-expiring');
        });

        Route::get('expiring', [InventoryController::class, 'getExpiringItems'])
            ->name('inventory.expiring');
    });
});
