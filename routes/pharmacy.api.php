<?php

declare(strict_types=1);

use App\Domains\Pharmacy\Http\Controllers\PharmacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('pharmacy')->group(function (): void {
    Route::get('/',             [PharmacyController::class, 'index']);
    Route::get('{id}',          [PharmacyController::class, 'show']);

    Route::middleware('auth')->group(function (): void {
        Route::post('orders',   [PharmacyController::class, 'order']);
        Route::get('my-orders', [PharmacyController::class, 'myOrders']);
    });
});
