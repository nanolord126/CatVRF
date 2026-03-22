<?php

declare(strict_types=1);

use App\Domains\ToysKids\Http\Controllers\ToyProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('toys')->group(function (): void {
    Route::get('/',             [ToyProductController::class, 'index']);
    Route::get('{id}',          [ToyProductController::class, 'show']);

    Route::middleware('auth')->group(function (): void {
        Route::post('wishlist',  [ToyProductController::class, 'wishlist']);
        Route::post('orders',    [ToyProductController::class, 'order']);
        Route::get('my-orders',  [ToyProductController::class, 'myOrders']);
    });
});
