<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Cart\Presentation\Http\Controllers\CartController;

Route::prefix('api')
    ->middleware(['api', 'auth:sanctum'])
    ->group(function () {
        Route::prefix('cart')->group(function () {
            Route::post('/items', [CartController::class, 'addItem']);
            Route::post('/{cartId}/prices', [CartController::class, 'refreshPrices']);
            Route::delete('/{cartId}/items/{productId}', [CartController::class, 'removeItem']);
            Route::post('/{cartId}/clear', [CartController::class, 'clear']);
            Route::get('/user', [CartController::class, 'getUserCarts']);
            Route::get('/{cartId}/total', [CartController::class, 'getTotal']);
        });
    });
