<?php

declare(strict_types=1);

use App\Domains\Electronics\Http\Controllers\ElectronicProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('electronics')->group(function (): void {
    Route::get('/',             [ElectronicProductController::class, 'index']);
    Route::get('compare',       [ElectronicProductController::class, 'compare']);
    Route::get('{id}',          [ElectronicProductController::class, 'show']);

    Route::middleware('auth')->group(function (): void {
        Route::post('orders',   [ElectronicProductController::class, 'order']);
        Route::get('my-orders', [ElectronicProductController::class, 'myOrders']);
    });
});
