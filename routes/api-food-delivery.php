<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Food\Http\Controllers\DeliveryOrderController;

Route::middleware(['auth:sanctum'])->prefix('v1/food')->group(function () {
    // Delivery endpoints
    Route::prefix('deliveries')->group(function () {
        Route::get('/', [DeliveryOrderController::class, 'index']);
        Route::get('/{delivery}', [DeliveryOrderController::class, 'show']);
        Route::post('/{delivery}/start', [DeliveryOrderController::class, 'start']);
        Route::get('/{delivery}/track', [DeliveryOrderController::class, 'track']);
    });
    
    // Order-specific delivery endpoint
    Route::get('/orders/{order}/delivery', [DeliveryOrderController::class, 'show']);
});
