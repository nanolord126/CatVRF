<?php

declare(strict_types=1);

use App\Http\Controllers\API\WMS\InventoryController;
use App\Http\Controllers\API\WMS\StockMovementController;
use App\Http\Controllers\API\WMS\BatchController;
use App\Http\Controllers\API\WMS\ColdChainController;
use App\Http\Controllers\API\WMS\BarcodeController;
use App\Http\Controllers\API\WMS\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:wms'])->prefix('wms')->group(function () {
    // Inventory endpoints
    Route::prefix('inventory')->group(function () {
        Route::get('/stats', [InventoryController::class, 'getStats']);
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::post('/', [InventoryController::class, 'store'])->middleware('can:create inventory');
        Route::put('/{id}', [InventoryController::class, 'update'])->middleware('can:update inventory');
        Route::delete('/{id}', [InventoryController::class, 'delete'])->middleware('can:delete inventory');
        Route::get('/low-stock', [InventoryController::class, 'getLowStock']);
        Route::get('/reorder/{tenantId}', [InventoryController::class, 'getReorderRecommendations']);
    });

    // Stock movement endpoints
    Route::prefix('stock-movements')->group(function () {
        Route::get('/', [StockMovementController::class, 'index']);
        Route::get('/{id}', [StockMovementController::class, 'show']);
        Route::post('/', [StockMovementController::class, 'store'])->middleware('can:create stock_movement');
        Route::put('/{id}', [StockMovementController::class, 'update'])->middleware('can:update stock_movement');
        Route::delete('/{id}', [StockMovementController::class, 'delete'])->middleware('can:delete stock_movement');
        Route::post('/{id}/approve', [StockMovementController::class, 'approve'])->middleware('can:approve stock_movement');
        Route::post('/{id}/reverse', [StockMovementController::class, 'reverse'])->middleware('can:reverse stock_movement');
        Route::post('/bulk/adjust', [StockMovementController::class, 'bulkAdjust'])->middleware('can:create stock_movement');
        Route::post('/bulk/transfer', [StockMovementController::class, 'bulkTransfer'])->middleware('can:create stock_movement');
        Route::post('/bulk/receipt', [StockMovementController::class, 'bulkReceipt'])->middleware('can:create stock_movement');
        Route::post('/bulk/writeoff', [StockMovementController::class, 'bulkWriteOff'])->middleware('can:create stock_movement');
    });

    // Batch endpoints
    Route::prefix('batches')->group(function () {
        Route::get('/', [BatchController::class, 'index']);
        Route::get('/{id}', [BatchController::class, 'show']);
        Route::post('/', [BatchController::class, 'store'])->middleware('can:create batch');
        Route::put('/{id}', [BatchController::class, 'update'])->middleware('can:update batch');
        Route::delete('/{id}', [BatchController::class, 'delete'])->middleware('can:delete batch');
        Route::post('/fefo', [BatchController::class, 'selectFEFO']);
        Route::post('/{id}/quarantine', [BatchController::class, 'placeOnQuarantine'])->middleware('can:update batch');
        Route::post('/{id}/release', [BatchController::class, 'releaseFromQuarantine'])->middleware('can:update batch');
        Route::post('/{id}/hold', [BatchController::class, 'placeOnHold'])->middleware('can:update batch');
        Route::post('/{id}/recall', [BatchController::class, 'createRecall'])->middleware('can:create batch');
    });

    // Cold chain monitoring endpoints (ФЗ-323)
    Route::prefix('cold-chain')->group(function () {
        Route::post('/record-temperature', [ColdChainController::class, 'recordTemperature']);
        Route::get('/readings/{warehouseId?}', [ColdChainController::class, 'getReadings']);
        Route::get('/alerts/{warehouseId?}', [ColdChainController::class, 'getAlerts']);
        Route::get('/compliance/{warehouseId?}', [ColdChainController::class, 'getComplianceReport']);
        Route::post('/alerts/{id}/resolve', [ColdChainController::class, 'resolveAlert']);
        Route::post('/alerts/{id}/escalate', [ColdChainController::class, 'escalateAlert']);
    });

    // Barcode scanning endpoints
    Route::prefix('barcode')->group(function () {
        Route::get('/lookup/{barcode}', [BarcodeController::class, 'lookup']);
        Route::post('/validate', [BarcodeController::class, 'validate']);
        Route::post('/chestny-znak/validate', [BarcodeController::class, 'validateChestnyZnak']);
        Route::post('/batch/lookup', [BarcodeController::class, 'lookupBatch']);
        Route::post('/bulk-lookup', [BarcodeController::class, 'bulkLookup']);
        
        // Scanner-specific endpoints with stock adjustment integration
        Route::post('/scan/adjust', [BarcodeController::class, 'scanAndAdjust'])->middleware('can:create stock_movement');
        Route::post('/scan/bulk-adjust', [BarcodeController::class, 'bulkScanAndAdjust'])->middleware('can:create stock_movement');
        Route::get('/scan/history', [BarcodeController::class, 'getScannerHistory']);
        Route::post('/scan/preflight', [BarcodeController::class, 'preFlightScan']);
    });

    // Reporting endpoints
    Route::prefix('reports')->group(function () {
        Route::get('/turnover/{tenantId}', [ReportController::class, 'getTurnoverReport']);
        Route::get('/abc-xyz/{tenantId}', [ReportController::class, 'getABCXYZReport']);
        Route::get('/expiry/{warehouseId}', [ReportController::class, 'getExpiryReport']);
        Route::get('/audit/{tenantId}', [ReportController::class, 'getAuditTrail']);
        Route::get('/compliance/152-fz/{tenantId}', [ReportController::class, 'get152FZReport']);
        Route::get('/compliance/fz-323/{tenantId}', [ReportController::class, 'getFZ323Report']);
        Route::get('/compliance/fz-61/{tenantId}', [ReportController::class, 'getFZ61Report']);
    });

    // Warehouse endpoints
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [InventoryController::class, 'getWarehouses']);
        Route::get('/{id}', [InventoryController::class, 'getWarehouse']);
        Route::get('/{id}/config', [InventoryController::class, 'getWarehouseConfig']);
    });
});
