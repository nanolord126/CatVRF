<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api', 'correlation-id'])->group(function (): void {
    // Marketplace routes with caching
    Route::prefix('marketplace')->middleware(['b2c-b2b-cache', 'response-cache'])->group(function (): void {
        Route::get('/products', [\App\Http\Controllers\Api\V1\MarketplaceController::class, 'products']);
        Route::get('/categories', [\App\Http\Controllers\Api\V1\MarketplaceController::class, 'categories']);
    });

    // Authenticated user routes with taste profile caching
    Route::middleware(['auth:sanctum', 'user-taste-cache'])->group(function (): void {
        Route::get('/profile', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'show']);
        Route::get('/recommendations', [\App\Http\Controllers\Api\V1\RecommendationController::class, 'index']);
    });

    // B2B routes with mode caching
    Route::middleware(['auth:sanctum', 'b2c-b2b-cache'])->group(function (): void {
        Route::get('/b2b/orders', [\App\Http\Controllers\Api\V1\B2BOrderController::class, 'index']);
        Route::post('/b2b/orders', [\App\Http\Controllers\Api\V1\B2BOrderController::class, 'store']);
    });

    // Public marketplace with response caching
    Route::middleware('response-cache')->group(function (): void {
        Route::get('/categories', [\App\Http\Controllers\Api\V1\CategoryController::class, 'index']);
        Route::get('/verticals', [\App\Http\Controllers\Api\V1\VerticalController::class, 'index']);
    });
});
