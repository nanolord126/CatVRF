<?php

declare(strict_types=1);

use App\Domains\Furniture\Http\Controllers\FurnitureController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('furniture')->group(function (): void {
    Route::get('/',             [FurnitureController::class, 'index']);
    Route::get('{id}',          [FurnitureController::class, 'show']);
    Route::get('{id}/3d',       [FurnitureController::class, 'view3D']);

    Route::middleware('auth')->group(function (): void {
        Route::post('orders',   [FurnitureController::class, 'order']);
        Route::get('my-orders', [FurnitureController::class, 'myOrders']);
    });
});
