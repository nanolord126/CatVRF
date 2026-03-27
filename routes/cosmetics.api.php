<?php

declare(strict_types=1);

use App\Domains\Beauty\Cosmetics\Http\Controllers\CosmeticProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('cosmetics')->group(function (): void {
    Route::get('/',                 [CosmeticProductController::class, 'index']);
    Route::get('{id}',              [CosmeticProductController::class, 'show']);
    Route::post('{id}/try-on',      [CosmeticProductController::class, 'tryOn']);

    Route::middleware('auth')->group(function (): void {
        Route::post('orders',       [CosmeticProductController::class, 'order']);
        Route::get('my-orders',     [CosmeticProductController::class, 'myOrders']);
    });
});
