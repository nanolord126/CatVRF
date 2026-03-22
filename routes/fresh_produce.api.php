<?php

declare(strict_types=1);

use App\Domains\FreshProduce\Http\Controllers\FreshProductController;
use App\Domains\FreshProduce\Http\Controllers\ProduceOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'tenant'])->prefix('fresh-produce')->group(function (): void {
    Route::get('products',       [FreshProductController::class, 'index']);
    Route::get('products/{id}',  [FreshProductController::class, 'show']);
    Route::get('boxes',          [FreshProductController::class, 'boxes']);

    Route::middleware('auth')->group(function (): void {
        Route::get('orders',              [ProduceOrderController::class, 'index']);
        Route::post('orders',             [ProduceOrderController::class, 'store']);
        Route::get('orders/{id}',         [ProduceOrderController::class, 'show']);
        Route::post('orders/{id}/cancel', [ProduceOrderController::class, 'cancel']);
        Route::get('subscriptions',       [ProduceOrderController::class, 'subscriptions']);
        Route::post('subscriptions',      [ProduceOrderController::class, 'subscribe']);
        Route::delete('subscriptions/{id}', [ProduceOrderController::class, 'unsubscribe']);
    });
});
