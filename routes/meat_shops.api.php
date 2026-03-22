<?php

declare(strict_types=1);

use App\Domains\MeatShops\Http\Controllers\MeatProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('meat')->group(function (): void {
    Route::get('/',             [MeatProductController::class, 'index']);
    Route::get('{id}',          [MeatProductController::class, 'show']);

    Route::middleware('auth')->group(function (): void {
        Route::post('orders',   [MeatProductController::class, 'order']);
        Route::get('my-orders', [MeatProductController::class, 'myOrders']);
    });
});
